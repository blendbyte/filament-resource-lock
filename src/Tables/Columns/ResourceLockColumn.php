<?php

namespace Blendbyte\FilamentResourceLock\Tables\Columns;

use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Filament\Tables\Columns\IconColumn;

class ResourceLockColumn extends IconColumn
{
    public static function getDefaultName(): ?string
    {
        return 'resourceLock';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getStateUsing(function ($record): ?string {
            if ($record->resourceLock === null || $record->resourceLock->isExpired()) {
                return null;
            }

            if ($record->isLockedByCurrentUser()) {
                return 'locked_by_me';
            }

            return 'locked_by_other';
        });

        $this->icon(function (?string $state): ?string {
            return match ($state) {
                'locked_by_me', 'locked_by_other' => 'heroicon-s-lock-closed',
                default => null,
            };
        });

        $this->color(function (?string $state): ?string {
            return match ($state) {
                'locked_by_me' => 'primary',
                'locked_by_other' => 'danger',
                default => null,
            };
        });

        $this->label(__('filament-resource-lock::table.lock'));

        $this->tooltip(function ($record, ?string $state): ?string {
            if ($state === null) {
                return null;
            }

            if ($state === 'locked_by_me') {
                return __('filament-resource-lock::table.locked_by_you');
            }

            if (ResourceLockPlugin::get()->shouldDisplayResourceLockOwner()) {
                $actionClass = ResourceLockPlugin::get()->getResourceLockOwnerAction();
                $name = app($actionClass)->execute($record->resourceLock->user);

                return __('filament-resource-lock::table.locked_by', ['name' => $name]);
            }

            return __('filament-resource-lock::table.locked_by_other');
        });
    }
}
