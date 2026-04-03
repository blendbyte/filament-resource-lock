<?php

namespace Blendbyte\FilamentResourceLock\Resources\LockResource;

use Blendbyte\FilamentResourceLock\Events\ResourceLockForceUnlocked;
use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Blendbyte\FilamentResourceLock\Resources\LockResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageResourceLocks extends ManageRecords
{
    protected static string $resource = LockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make(__('filament-resource-lock::manager.unlock_all'))
                ->label(__('filament-resource-lock::manager.unlock_all'))
                ->icon('heroicon-o-lock-open')
                ->action(function () {
                    $lockModel = ResourceLockPlugin::get()->getResourceLockModel();

                    if (config('filament-resource-lock.events.enabled', true)) {
                        $lockModel::with('lockable')->get()->each(function ($lock) {
                            ResourceLockForceUnlocked::dispatch(
                                $lock->lockable,
                                $lock->user_id,
                                auth()->id()
                            );
                        });
                    }

                    $lockModel::truncate();
                })
                ->requiresConfirmation(),
        ];
    }
}
