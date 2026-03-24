<?php

declare(strict_types=1);

use Blendbyte\FilamentResourceLock\Resources\LockResource\ManageResourceLocks;
use Livewire\Livewire;

it('can render lock resource index page', function () {
    Livewire::test(ManageResourceLocks::class)
        ->assertSuccessful();
});

it('can render the unlock all resources button', function () {
    Livewire::test(ManageResourceLocks::class)
        ->assertSee('Unlock all resources');
});
