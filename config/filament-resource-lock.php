<?php

use App\Models\User;
use Blendbyte\FilamentResourceLock\Actions\GetResourceLockOwnerAction;
use Blendbyte\FilamentResourceLock\Resources\LockResource;

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | The models configuration specifies the classes that represent your application's
    | data objects. This configuration is used by the framework to interact with
    | the application's data models. You can even implement your own ResourceLock model.
    |
    */

    'models' => [
        'User' => User::class,
        // 'ResourceLock' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Resource
    |--------------------------------------------------------------------------
    |
    | The resource lock filament resource displays all the current locks in place.
    | You are able to replace the resource Lock with your own resource class.
    |
    */
    'resource' => [
        'class' => LockResource::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Unlocker Button
    |--------------------------------------------------------------------------
    |
    | The unlocker configuration specifies whether limited access is enabled for
    | the resource unlock button. If limited access is enabled, only specific
    | users or roles will be able to unlock locked resources directly from
    | the modal.
    |
    */

    'unlocker' => [
        'limited_access' => false,
        // 'gate' => ''
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Notice
    |--------------------------------------------------------------------------
    |
    | The lock notice contains several configuration options for the modal
    | that is display when a resource is locked.
    |
    */

    'lock_notice' => [
        'display_resource_lock_owner' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Read-Only Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, locked resources will be displayed in read-only mode
    | instead of showing the lock modal. Users will be able to view the
    | resource but will not be able to make any changes.
    |
    */

    'read_only_mode' => [
        'enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Lock Manager
    |--------------------------------------------------------------------------
    |
    | The resource lock manager provides a simple way to view all resource locks
    | of your application. It provides several ways to quickly unlock all or
    | specific resources within your app.
    |
    */

    'manager' => [
        'navigation_badge' => false,
        'navigation_icon' => 'heroicon-o-lock-closed',
        'navigation_label' => 'Resource Lock Manager',
        'plural_label' => 'Resource Locks',
        'navigation_group' => 'Settings',
        'navigation_sort' => 1,
        'limited_access' => false,
        'should_register_navigation' => true,
        //        'gate' => ''
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock timeout (in minutes)
    |--------------------------------------------------------------------------
    |
    | The lock_timeout configuration specifies the time interval, in seconds,
    | after which a lock on a resource will expire if it has not been manually
    | unlocked or released by the user.
    |
    */

    'lock_timeout' => 600,

    /*
    |--------------------------------------------------------------------------
    | Check Locks before saving
    |--------------------------------------------------------------------------
    |
    | The check_locks_before_saving configuration specifies whether a lock of a resource will be checked
    | before saving a resource if a tech-savvy user is able to bypass the locked
    | resource modal and attempt to save the resource. In some cases you may want to turns this off.
    | It's recommended to keep this on.
    |
    */

    'check_locks_before_saving' => true,

    /*
   |--------------------------------------------------------------------------
   | Actions
   |--------------------------------------------------------------------------
   |
   | Action classes are simple classes that execute some logic within the package.
   | If you want to add your own custom logic you are able to extend your own
   | class with class your overwriting.
   | Learn more about action classes: https://freek.dev/2442-strategies-for-making-laravel-packages-customizable
   |
   */

    'actions' => [
        'get_resource_lock_owner_action' => GetResourceLockOwnerAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | When enabled, the package dispatches Laravel events for every lock
    | lifecycle transition: ResourceLocked, ResourceUnlocked,
    | ResourceLockExpired, and ResourceLockForceUnlocked.
    | Set to false to disable all event dispatching globally.
    |
    */

    'events' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Trail
    |--------------------------------------------------------------------------
    |
    | When enabled, the package writes a row to resource_lock_audit for every
    | lock lifecycle event (locked, unlocked, expired, force_unlocked).
    | Requires events.enabled to be true, as the audit trail is powered by
    | Laravel events. Set to false to disable audit logging globally.
    |
    */

    'audit' => [
        'enabled' => false,
        'navigation_icon' => 'heroicon-o-clipboard-document-list',
        'navigation_label' => 'Lock Audit Log',
        'plural_label' => 'Lock Audit Logs',
        'navigation_group' => null,
        'navigation_sort' => 2,
        'should_register_navigation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    |
    | When auto_clear_expired is enabled, the package registers a scheduled
    | task that runs filament-resource-lock:clear-expired --force every hour.
    | Set to false to manage the schedule yourself.
    |
    */

    'schedule' => [
        'auto_clear_expired' => true,
    ],
];
