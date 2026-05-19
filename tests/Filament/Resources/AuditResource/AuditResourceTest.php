<?php

declare(strict_types=1);

use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Blendbyte\FilamentResourceLock\Resources\AuditResource;
use Blendbyte\FilamentResourceLock\Resources\AuditResource\ListAuditLogs;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe('Gate access control', function () {
    it('allows access when limited_access is disabled', function () {
        $user = createUser();
        actingAs($user);

        config()->set('filament-resource-lock.audit.limited_access', false);

        expect(AuditResource::canViewAny())->toBeTrue();
    });

    it('denies access when limited_access is enabled but no gate is configured', function () {
        $user = createUser();
        actingAs($user);

        config()->set('filament-resource-lock.audit.limited_access', true);
        config()->set('filament-resource-lock.audit.gate', null);

        expect(AuditResource::canViewAny())->toBeFalse();
    });

    it('denies access when limited_access is enabled and gate fails', function () {
        $user = createUser();
        actingAs($user);

        Gate::define('view-audit-log', fn () => false);

        config()->set('filament-resource-lock.audit.limited_access', true);
        config()->set('filament-resource-lock.audit.gate', 'view-audit-log');

        expect(AuditResource::canViewAny())->toBeFalse();
    });

    it('allows access when limited_access is enabled and gate passes', function () {
        $user = createUser();
        actingAs($user);

        Gate::define('view-audit-log', fn () => true);

        config()->set('filament-resource-lock.audit.limited_access', true);
        config()->set('filament-resource-lock.audit.gate', 'view-audit-log');

        expect(AuditResource::canViewAny())->toBeTrue();
    });

    it('renders the audit log page when the gate passes', function () {
        $user = createUser();
        actingAs($user);

        Gate::define('view-audit-log', fn () => true);

        config()->set('filament-resource-lock.audit.limited_access', true);
        config()->set('filament-resource-lock.audit.gate', 'view-audit-log');

        Livewire::test(ListAuditLogs::class)
            ->assertSuccessful();
    });

    it('returns forbidden when gate denies access', function () {
        $user = createUser();
        actingAs($user);

        Gate::define('view-audit-log', fn () => false);

        config()->set('filament-resource-lock.audit.limited_access', true);
        config()->set('filament-resource-lock.audit.gate', 'view-audit-log');

        Livewire::test(ListAuditLogs::class)
            ->assertForbidden();
    });
});

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
