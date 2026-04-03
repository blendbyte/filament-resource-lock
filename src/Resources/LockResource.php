<?php

namespace Blendbyte\FilamentResourceLock\Resources;

use Blendbyte\FilamentResourceLock\Events\ResourceLockForceUnlocked;
use Blendbyte\FilamentResourceLock\Models\ResourceLock;
use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Blendbyte\FilamentResourceLock\Resources\LockResource\ManageResourceLocks;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Collection;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class LockResource extends Resource
{
    public static function getNavigationIcon(): ?string
    {
        return ResourceLockPlugin::get()->getNavigationIcon();
    }

    public static function getModel(): string
    {
        return ResourceLockPlugin::get()->getResourceLockModel();
    }

    public static function getPluralLabel(): string
    {
        return ResourceLockPlugin::get()->getPluralLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('Lock ID')),
                TextColumn::make('user.id')->label(__('User ID')),
                TextColumn::make('lockable.id')->label(__('Lockable ID')),
                TextColumn::make('lockable_type')->label(__('Lockable type')),
                TextColumn::make('created_at')->label(__('Created at')),
                TextColumn::make('updated_at')->label(__('Updated at')),
                TextColumn::make('lock_status')->label(__('Expired'))
                    ->state(fn ($record) => $record->isExpired())
                    ->badge()
                    ->color(static function ($record): string {
                        if ($record->isExpired()) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->icon(static function ($record): string {
                        if ($record->isExpired()) {
                            return 'heroicon-o-lock-open';
                        }

                        return 'heroicon-o-lock-closed';
                    })->formatStateUsing(static function ($record) {
                        if ($record->isExpired()) {
                            return __('filament-resource-lock::manager.expired');
                        }

                        return __('filament-resource-lock::manager.active');
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make()
                    ->before(function (ResourceLock $record) {
                        if (config('filament-resource-lock.events.enabled', true)) {
                            ResourceLockForceUnlocked::dispatch(
                                $record->lockable,
                                $record->user_id,
                                auth()->id()
                            );
                        }
                    })
                    ->icon('heroicon-o-lock-open')
                    ->successNotificationTitle(__('filament-resource-lock::manager.unlocked'))
                    ->label(__('filament-resource-lock::manager.unlock')),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->before(function (Collection $records) {
                        if (config('filament-resource-lock.events.enabled', true)) {
                            $records->each(function (ResourceLock $record) {
                                ResourceLockForceUnlocked::dispatch(
                                    $record->lockable,
                                    $record->user_id,
                                    auth()->id()
                                );
                            });
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->icon('heroicon-o-lock-open')
                    ->successNotificationTitle(__('filament-resource-lock::manager.unlocked_selected'))
                    ->label(__('filament-resource-lock::manager.unlock')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageResourceLocks::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (ResourceLockPlugin::get()->shouldLimitAccessToResourceLockManager()) {
            $gate = ResourceLockPlugin::get()->getGate();

            return $gate !== null && Gate::allows($gate);
        }

        return true;
    }

    public static function canDeleteAny(): bool
    {
        if (ResourceLockPlugin::get()->shouldLimitAccessToResourceLockManager()) {
            $gate = ResourceLockPlugin::get()->getGate();

            return $gate !== null && Gate::allows($gate);
        }

        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! ResourceLockPlugin::get()->shouldShowNavigationBadge()) {
            return null;
        }

        return (string) static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return ResourceLockPlugin::get()->getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return ResourceLockPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return ResourceLockPlugin::get()->getNavigationSort();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ResourceLockPlugin::get()->shouldRegisterNavigation();
    }
}
