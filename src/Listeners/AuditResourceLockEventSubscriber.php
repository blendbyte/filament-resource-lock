<?php

namespace Blendbyte\FilamentResourceLock\Listeners;

use Blendbyte\FilamentResourceLock\Events\ResourceLockExpired;
use Blendbyte\FilamentResourceLock\Events\ResourceLockForceUnlocked;
use Blendbyte\FilamentResourceLock\Events\ResourceLocked;
use Blendbyte\FilamentResourceLock\Events\ResourceUnlocked;
use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Illuminate\Events\Dispatcher;

class AuditResourceLockEventSubscriber
{
    public function handleLocked(ResourceLocked $event): void
    {
        if (! config('filament-resource-lock.audit.enabled', false)) {
            return;
        }

        ResourceLockAudit::create([
            'action' => ResourceLockAudit::ACTION_LOCKED,
            'lockable_type' => get_class($event->lockable),
            'lockable_id' => $event->lockable->getKey(),
            'user_id' => $event->userId,
            'actor_user_id' => null,
        ]);
    }

    public function handleUnlocked(ResourceUnlocked $event): void
    {
        if (! config('filament-resource-lock.audit.enabled', false)) {
            return;
        }

        ResourceLockAudit::create([
            'action' => ResourceLockAudit::ACTION_UNLOCKED,
            'lockable_type' => get_class($event->lockable),
            'lockable_id' => $event->lockable->getKey(),
            'user_id' => $event->userId,
            'actor_user_id' => null,
        ]);
    }

    public function handleExpired(ResourceLockExpired $event): void
    {
        if (! config('filament-resource-lock.audit.enabled', false)) {
            return;
        }

        ResourceLockAudit::create([
            'action' => ResourceLockAudit::ACTION_EXPIRED,
            'lockable_type' => get_class($event->lockable),
            'lockable_id' => $event->lockable->getKey(),
            'user_id' => $event->originalUserId,
            'actor_user_id' => null,
        ]);
    }

    public function handleForceUnlocked(ResourceLockForceUnlocked $event): void
    {
        if (! config('filament-resource-lock.audit.enabled', false)) {
            return;
        }

        ResourceLockAudit::create([
            'action' => ResourceLockAudit::ACTION_FORCE_UNLOCKED,
            'lockable_type' => get_class($event->lockable),
            'lockable_id' => $event->lockable->getKey(),
            'user_id' => $event->originalUserId,
            'actor_user_id' => $event->actorUserId,
        ]);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ResourceLocked::class => 'handleLocked',
            ResourceUnlocked::class => 'handleUnlocked',
            ResourceLockExpired::class => 'handleExpired',
            ResourceLockForceUnlocked::class => 'handleForceUnlocked',
        ];
    }
}
