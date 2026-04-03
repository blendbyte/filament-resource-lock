<?php

use Blendbyte\FilamentResourceLock\Events\ResourceLockExpired;
use Blendbyte\FilamentResourceLock\Events\ResourceLockForceUnlocked;
use Blendbyte\FilamentResourceLock\Events\ResourceLocked;
use Blendbyte\FilamentResourceLock\Events\ResourceUnlocked;
use Blendbyte\FilamentResourceLock\Models\ResourceLock;
use Blendbyte\FilamentResourceLock\Tests\Resources\Models\PostWithShortTimeout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

describe('Resource Locking', function () {
    it('can lock a resource', function () {
        // Arrange
        $user = createUser();
        actingAs($user);
        $post = createPost();

        // Act
        $post->lock();
        $post->refresh();

        // Assert
        expect($post->resourceLock->lockable_id)
            ->toBe($post->id)
            ->and($post->resourceLock->user_id)
            ->toBe($user->id);
        assertDatabaseCount(ResourceLock::class, 1);
        expect($post->isLockedByCurrentUser())->toBeTrue();
        expect($post->isLocked())->toBeTrue();
    });
});

describe('Resource Unlocking', function () {
    it('can unlock a resource', function () {
        // Arrange
        $user = createUser();
        actingAs($user);
        $post = createPost();
        $post->lock();

        // Act
        $post->refresh();
        $post->unlock();
        $post->refresh();

        // Assert
        expect($post->resourceLock)->toBeNull();
        assertDatabaseCount(ResourceLock::class, 0);
        expect($post->isLockedByCurrentUser())->toBeFalse();
        expect($post->isLocked())->toBeFalse();
    });

    it('can unlock a resource by force', function () {
        // Arrange
        $user = createUser();
        actingAs($user);
        $post = createPost();
        $post->lock();
        $admin = createUser();
        actingAs($admin);

        // Act
        $post->refresh();
        $forceLockResult = $post->unlock(force: true);
        $post->refresh();

        // Assert
        assertDatabaseCount(ResourceLock::class, 0);
        expect($post->resourceLock)->toBeNull();
        expect($forceLockResult)->toBeTrue();
    });
});

describe('Lock Status Checks', function () {
    it('can check if a lock has been expired', function () {
        // Arrange
        $user = createUser();
        actingAs($user);
        $post = createPost();
        createExpiredResourceLock($user, $post);

        // Act
        // (No explicit act step, as the check is the assertion)

        // Assert
        expect($post->hasExpiredLock())->toBeTrue();
    });
});

describe('Lock Timestamp Updates', function () {
    it('updates timestamp when lock is refreshed by current user', function () {
        // Arrange
        $user = createUser();
        actingAs($user);
        $post = createPost();

        $post->lock();
        $post->refresh();
        $initialTimestamp = $post->resourceLock->updated_at;

        // Act
        sleep(1);
        $result = $post->lock();
        $post->refresh();

        // Assert
        expect($result)->toBeTrue();
        expect($post->resourceLock->updated_at)->toBeGreaterThan($initialTimestamp);
        assertDatabaseCount(ResourceLock::class, 1);
    });
});

it('detects lock when another user tries to edit a locked resource', function () {
    // Arrange
    $user1 = createUser();
    $post = createPost();

    actingAs($user1);
    $post->lock();

    $user2 = createUser();
    actingAs($user2);

    // Act & Assert
    $post->refresh();
    expect($post->isLocked())->toBeTrue()
        ->and($post->isLockedByCurrentUser())->toBeFalse();
});

it('automatically considers locks expired after timeout period', function () {
    // Arrange
    $user = createUser();
    actingAs($user);
    $post = createPost();
    $post->lock();

    // Act
    ResourceLock::where('lockable_id', $post->id)->update([
        'updated_at' => Carbon::now()->subMinutes(30),
    ]);
    $post->refresh();

    // Assert
    expect($post->hasExpiredLock())->toBeTrue();
    expect($post->isLocked())->toBeFalse();
});

it('prevents unlocking by a different user without force', function () {
    // Arrange
    $user1 = createUser();
    actingAs($user1);
    $post = createPost();
    $post->lock();

    $user2 = createUser();
    actingAs($user2);

    // Act
    $post->refresh();
    $unlockResult = $post->unlock(force: false);
    $post->refresh();

    // Assert
    expect($unlockResult)->toBeFalse();
    expect($post->isLocked())->toBeTrue();
    assertDatabaseCount(ResourceLock::class, 1);
});

it('prevents locking a resource that is already locked by another user', function () {
    // Arrange
    $user1 = createUser();
    actingAs($user1);
    $post = createPost();
    $post->lock();

    $user2 = createUser();
    actingAs($user2);

    // Act
    $post->refresh();
    $lockResult = $post->lock();

    // Assert
    expect($lockResult)->toBeFalse();
    expect($post->isLocked())->toBeTrue();
    expect($post->isLockedByCurrentUser())->toBeFalse();
    assertDatabaseCount(ResourceLock::class, 1);
});

it('prevents multiple users from locking when expired locks exist', function () {
    // Arrange
    $user1 = createUser();
    $user2 = createUser();
    $post = createPost();

    // Create multiple expired locks for the same resource
    createExpiredResourceLock($user1, $post);
    createExpiredResourceLock($user2, $post);

    // Act & Assert with user1
    actingAs($user1);
    $post->refresh();
    expect($post->isUnlocked())
        ->toBeTrue()
        ->and($post->lock())
        ->toBeTrue(); // This should be true because locks are expired
    // User1 can lock because resource appears unlocked

    // Act & Assert with user2
    actingAs($user2);
    $post->refresh();
    expect($post->isUnlocked())
        ->toBeFalse()
        ->and($post->lock())
        ->toBeFalse();
});

describe('Per-Model Lock Timeout', function () {
    it('returns the model lockTimeout property when declared', function () {
        $post = new PostWithShortTimeout;

        expect($post->getLockTimeout())->toBe(10);
    });

    it('falls back to the global config timeout when no property is declared', function () {
        $post = createPost();
        config(['filament-resource-lock.lock_timeout' => 300]);

        expect($post->getLockTimeout())->toBe(300);
    });

    it('considers a lock expired based on the model timeout, not the global timeout', function () {
        $user = createUser();
        actingAs($user);

        $post = (new PostWithShortTimeout)->forceFill([
            'title' => fake()->paragraph,
            'slug' => fake()->slug,
            'body' => fake()->text,
        ]);
        $post->save();

        // Create a lock 15 seconds old — expired for 10s model timeout, active for global 600s
        $resourceLock = (new ResourceLock)->forceFill([
            'updated_at' => Carbon::now()->subSeconds(15),
            'user_id' => $user->id,
            'lockable_type' => PostWithShortTimeout::class,
            'lockable_id' => $post->id,
        ]);
        $resourceLock->save();
        $post->refresh();

        expect($post->isLocked())->toBeFalse()
            ->and($post->hasExpiredLock())->toBeTrue();
    });

    it('considers a lock active when within the model timeout window', function () {
        $user = createUser();
        actingAs($user);

        $post = (new PostWithShortTimeout)->forceFill([
            'title' => fake()->paragraph,
            'slug' => fake()->slug,
            'body' => fake()->text,
        ]);
        $post->save();

        // Create a lock 5 seconds old — active for 10s model timeout
        $resourceLock = (new ResourceLock)->forceFill([
            'updated_at' => Carbon::now()->subSeconds(5),
            'user_id' => $user->id,
            'lockable_type' => PostWithShortTimeout::class,
            'lockable_id' => $post->id,
        ]);
        $resourceLock->save();
        $post->refresh();

        expect($post->isLocked())->toBeTrue();
    });

    it('uses model timeout when locking over an expired lock', function () {
        $user1 = createUser();
        $user2 = createUser();

        $post = (new PostWithShortTimeout)->forceFill([
            'title' => fake()->paragraph,
            'slug' => fake()->slug,
            'body' => fake()->text,
        ]);
        $post->save();

        // Create a lock 15 seconds old — expired by model's 10s timeout
        (new ResourceLock)->forceFill([
            'updated_at' => Carbon::now()->subSeconds(15),
            'user_id' => $user1->id,
            'lockable_type' => PostWithShortTimeout::class,
            'lockable_id' => $post->id,
        ])->save();

        actingAs($user2);
        $post->refresh();

        // user2 should be able to acquire the lock since it's expired by model timeout
        expect($post->lock())->toBeTrue()
            ->and($post->refresh()->isLockedByCurrentUser())->toBeTrue();
    });
});

describe('Lock Events', function () {
    it('dispatches ResourceLocked when a new lock is acquired', function () {
        Event::fake();

        $user = createUser();
        actingAs($user);
        $post = createPost();

        $post->lock();

        Event::assertDispatched(ResourceLocked::class, function ($event) use ($post, $user) {
            return $event->lockable->id === $post->id
                && $event->userId === $user->id;
        });
    });

    it('does not dispatch ResourceLocked on keepalive touch', function () {
        Event::fake();

        $user = createUser();
        actingAs($user);
        $post = createPost();

        $post->lock();
        $post->refresh();
        sleep(1);
        $post->lock(); // keepalive

        Event::assertDispatchedTimes(ResourceLocked::class, 1);
    });

    it('dispatches ResourceLockExpired then ResourceLocked when a new lock overwrites an expired one', function () {
        Event::fake();

        $user1 = createUser();
        $user2 = createUser();
        $post = createPost();
        createExpiredResourceLock($user1, $post);

        actingAs($user2);
        $post->refresh();
        $post->lock();

        Event::assertDispatched(ResourceLockExpired::class, function ($event) use ($post, $user1) {
            return $event->lockable->id === $post->id
                && $event->originalUserId === $user1->id;
        });

        Event::assertDispatched(ResourceLocked::class, function ($event) use ($post, $user2) {
            return $event->lockable->id === $post->id
                && $event->userId === $user2->id;
        });
    });

    it('dispatches ResourceUnlocked on natural unlock by owner', function () {
        Event::fake();

        $user = createUser();
        actingAs($user);
        $post = createPost();
        $post->lock();
        $post->refresh();

        $post->unlock();

        Event::assertDispatched(ResourceUnlocked::class, function ($event) use ($post, $user) {
            return $event->lockable->id === $post->id
                && $event->userId === $user->id;
        });
        Event::assertNotDispatched(ResourceLockForceUnlocked::class);
    });

    it('does not dispatch ResourceUnlocked when unlock is rejected', function () {
        Event::fake();

        $user1 = createUser();
        actingAs($user1);
        $post = createPost();
        $post->lock();

        $user2 = createUser();
        actingAs($user2);
        $post->refresh();
        $post->unlock(force: false);

        Event::assertNotDispatched(ResourceUnlocked::class);
        Event::assertNotDispatched(ResourceLockForceUnlocked::class);
    });

    it('dispatches ResourceLockForceUnlocked with correct user IDs on force unlock', function () {
        Event::fake();

        $user1 = createUser();
        actingAs($user1);
        $post = createPost();
        $post->lock();

        $user2 = createUser();
        actingAs($user2);
        $post->refresh();
        $post->unlock(force: true);

        Event::assertDispatched(ResourceLockForceUnlocked::class, function ($event) use ($post, $user1, $user2) {
            return $event->lockable->id === $post->id
                && $event->originalUserId === $user1->id
                && $event->actorUserId === $user2->id;
        });
        Event::assertNotDispatched(ResourceUnlocked::class);
    });

    it('dispatches no events when events are disabled via config', function () {
        Event::fake([ResourceLocked::class, ResourceUnlocked::class, ResourceLockExpired::class, ResourceLockForceUnlocked::class]);
        config(['filament-resource-lock.events.enabled' => false]);

        $user = createUser();
        actingAs($user);
        $post = createPost();
        $post->lock();
        $post->refresh();
        $post->unlock();

        Event::assertNothingDispatched();
    });
});
