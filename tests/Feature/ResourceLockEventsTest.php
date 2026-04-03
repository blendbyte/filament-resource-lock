<?php

declare(strict_types=1);

use Blendbyte\FilamentResourceLock\Events\ResourceLockForceUnlocked;
use Blendbyte\FilamentResourceLock\Resources\LockResource\ManageResourceLocks;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

$lockEvents = [ResourceLockForceUnlocked::class];

it('dispatches ResourceLockForceUnlocked for each lock on Unlock All', function () {
    Event::fake([ResourceLockForceUnlocked::class]);

    $user = createUser();
    actingAs($user);

    $post1 = createPost();
    $post2 = createPost();
    createActiveResourceLock($user, $post1);
    createActiveResourceLock($user, $post2);

    Livewire::test(ManageResourceLocks::class)
        ->callAction('Unlock all resources');

    Event::assertDispatchedTimes(ResourceLockForceUnlocked::class, 2);
});

it('dispatches no events on Unlock All when events are disabled', function () {
    Event::fake([ResourceLockForceUnlocked::class]);
    config(['filament-resource-lock.events.enabled' => false]);

    $user = createUser();
    actingAs($user);

    $post = createPost();
    createActiveResourceLock($user, $post);

    Livewire::test(ManageResourceLocks::class)
        ->callAction('Unlock all resources');

    Event::assertNothingDispatched();
});

it('dispatches ResourceLockForceUnlocked with correct lock owner on Unlock All', function () {
    Event::fake([ResourceLockForceUnlocked::class]);

    $lockOwner = createUser();
    $actor = createUser();
    actingAs($actor);

    $post = createPost();
    createActiveResourceLock($lockOwner, $post);

    Livewire::test(ManageResourceLocks::class)
        ->callAction('Unlock all resources');

    Event::assertDispatched(ResourceLockForceUnlocked::class, function ($event) use ($post, $lockOwner, $actor) {
        return $event->lockable->id === $post->id
            && $event->originalUserId === $lockOwner->id
            && $event->actorUserId === $actor->id;
    });
});

it('dispatches ResourceLockForceUnlocked when a row is deleted in the Lock Manager', function () {
    Event::fake([ResourceLockForceUnlocked::class]);

    $lockOwner = createUser();
    $actor = createUser();
    actingAs($actor);

    $post = createPost();
    $lock = createActiveResourceLock($lockOwner, $post);

    Livewire::test(ManageResourceLocks::class)
        ->callTableAction('delete', $lock);

    Event::assertDispatched(ResourceLockForceUnlocked::class, function ($event) use ($post, $lockOwner, $actor) {
        return $event->lockable->id === $post->id
            && $event->originalUserId === $lockOwner->id
            && $event->actorUserId === $actor->id;
    });
});

it('dispatches ResourceLockForceUnlocked for each row on bulk delete in the Lock Manager', function () {
    Event::fake([ResourceLockForceUnlocked::class]);

    $lockOwner = createUser();
    $actor = createUser();
    actingAs($actor);

    $post1 = createPost();
    $post2 = createPost();
    $lock1 = createActiveResourceLock($lockOwner, $post1);
    $lock2 = createActiveResourceLock($lockOwner, $post2);

    Livewire::test(ManageResourceLocks::class)
        ->callTableBulkAction('delete', [$lock1, $lock2]);

    Event::assertDispatchedTimes(ResourceLockForceUnlocked::class, 2);
});
