<?php

namespace Blendbyte\FilamentResourceLock\Tables\Columns;

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
    }
}
