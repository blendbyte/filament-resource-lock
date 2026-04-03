<?php

declare(strict_types=1);

use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Blendbyte\FilamentResourceLock\Resources\LockResource\ManageResourceLocks;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

it('writes a locked audit record when a resource is locked', function () {
    config(['filament-resource-lock.audit.enabled' => true]);

    $user = createUser();
    actingAs($user);
    $post = createPost();

    $post->lock();

    assertDatabaseCount(ResourceLockAudit::class, 1);

    $audit = ResourceLockAudit::first();
    expect($audit->action)->toBe(ResourceLockAudit::ACTION_LOCKED)
        ->and($audit->lockable_type)->toBe(get_class($post))
        ->and($audit->lockable_id)->toBe($post->id)
        ->and($audit->user_id)->toBe($user->id)
        ->and($audit->actor_user_id)->toBeNull();
});

it('writes an unlocked audit record when a resource is naturally unlocked', function () {
    config(['filament-resource-lock.audit.enabled' => true]);

    $user = createUser();
    actingAs($user);
    $post = createPost();
    $post->lock();

    $post->refresh()->unlock();

    $audit = ResourceLockAudit::where('action', ResourceLockAudit::ACTION_UNLOCKED)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->lockable_id)->toBe($post->id)
        ->and($audit->user_id)->toBe($user->id)
        ->and($audit->actor_user_id)->toBeNull();
});

it('writes a force_unlocked audit record with actor when force-unlocked', function () {
    config(['filament-resource-lock.audit.enabled' => true]);

    $owner = createUser();
    $actor = createUser();
    actingAs($owner);
    $post = createPost();
    $post->lock();

    actingAs($actor);
    $post->refresh()->unlock(force: true);

    $audit = ResourceLockAudit::where('action', ResourceLockAudit::ACTION_FORCE_UNLOCKED)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->lockable_id)->toBe($post->id)
        ->and($audit->user_id)->toBe($owner->id)
        ->and($audit->actor_user_id)->toBe($actor->id);
});

it('writes an expired audit record then a locked record when locking over an expired lock', function () {
    config(['filament-resource-lock.audit.enabled' => true]);

    $originalOwner = createUser();
    $newUser = createUser();
    actingAs($originalOwner);
    $post = createPost();
    createExpiredResourceLock($originalOwner, $post);

    actingAs($newUser);
    $post->lock();

    $expiredAudit = ResourceLockAudit::where('action', ResourceLockAudit::ACTION_EXPIRED)->first();
    expect($expiredAudit)->not->toBeNull()
        ->and($expiredAudit->user_id)->toBe($originalOwner->id);

    $lockedAudit = ResourceLockAudit::where('action', ResourceLockAudit::ACTION_LOCKED)->first();
    expect($lockedAudit)->not->toBeNull()
        ->and($lockedAudit->user_id)->toBe($newUser->id);
});

it('writes no audit records when audit is disabled', function () {
    config(['filament-resource-lock.audit.enabled' => false]);

    $user = createUser();
    actingAs($user);
    $post = createPost();

    $post->lock();
    $post->refresh()->unlock();

    assertDatabaseCount(ResourceLockAudit::class, 0);
});

it('writes no audit records when events are disabled', function () {
    config(['filament-resource-lock.audit.enabled' => true]);
    config(['filament-resource-lock.events.enabled' => false]);

    $user = createUser();
    actingAs($user);
    $post = createPost();

    $post->lock();
    $post->refresh()->unlock();

    assertDatabaseCount(ResourceLockAudit::class, 0);
});

it('writes a force_unlocked audit record for each lock on Unlock All', function () {
    config(['filament-resource-lock.audit.enabled' => true]);

    $lockOwner = createUser();
    $actor = createUser();
    actingAs($actor);

    $post1 = createPost();
    $post2 = createPost();
    createActiveResourceLock($lockOwner, $post1);
    createActiveResourceLock($lockOwner, $post2);

    Livewire::test(ManageResourceLocks::class)
        ->callAction('Unlock all resources');

    assertDatabaseCount(ResourceLockAudit::class, 2);
    expect(ResourceLockAudit::where('action', ResourceLockAudit::ACTION_FORCE_UNLOCKED)->count())->toBe(2);
});
