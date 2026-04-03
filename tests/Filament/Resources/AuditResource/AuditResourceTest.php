<?php

declare(strict_types=1);

use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Blendbyte\FilamentResourceLock\Resources\AuditResource\ListAuditLogs;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('can render audit log index page', function () {
    Livewire::test(ListAuditLogs::class)
        ->assertSuccessful();
});

it('displays audit records in the table', function () {
    $user = createUser();
    actingAs($user);

    ResourceLockAudit::create([
        'action' => ResourceLockAudit::ACTION_LOCKED,
        'lockable_type' => 'App\Models\Post',
        'lockable_id' => 1,
        'user_id' => $user->id,
        'actor_user_id' => null,
    ]);

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords(ResourceLockAudit::all());
});

it('can filter audit records by action', function () {
    $user = createUser();
    actingAs($user);

    ResourceLockAudit::create([
        'action' => ResourceLockAudit::ACTION_LOCKED,
        'lockable_type' => 'App\Models\Post',
        'lockable_id' => 1,
        'user_id' => $user->id,
        'actor_user_id' => null,
    ]);

    ResourceLockAudit::create([
        'action' => ResourceLockAudit::ACTION_FORCE_UNLOCKED,
        'lockable_type' => 'App\Models\Post',
        'lockable_id' => 1,
        'user_id' => $user->id,
        'actor_user_id' => $user->id,
    ]);

    Livewire::test(ListAuditLogs::class)
        ->filterTable('action', ResourceLockAudit::ACTION_LOCKED)
        ->assertCanSeeTableRecords(ResourceLockAudit::where('action', ResourceLockAudit::ACTION_LOCKED)->get())
        ->assertCanNotSeeTableRecords(ResourceLockAudit::where('action', ResourceLockAudit::ACTION_FORCE_UNLOCKED)->get());
});
