<?php

namespace Blendbyte\FilamentResourceLock\Resources;

use Blendbyte\FilamentResourceLock\Models\ResourceLockAudit;
use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Blendbyte\FilamentResourceLock\Resources\AuditResource\ListAuditLogs;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditResource extends Resource
{
    protected static ?string $model = ResourceLockAudit::class;

    public static function getNavigationIcon(): ?string
    {
        return ResourceLockPlugin::get()->getAuditNavigationIcon();
    }

    public static function getNavigationLabel(): string
    {
        return ResourceLockPlugin::get()->getAuditNavigationLabel();
    }

    public static function getPluralLabel(): string
    {
        return ResourceLockPlugin::get()->getAuditPluralLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return ResourceLockPlugin::get()->getAuditNavigationGroup()
            ?? ResourceLockPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return ResourceLockPlugin::get()->getAuditNavigationSort();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ResourceLockPlugin::get()->shouldAuditEvents()
            && ResourceLockPlugin::get()->shouldRegisterAuditNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')
                    ->label(__('filament-resource-lock::audit.columns.action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ResourceLockAudit::ACTION_LOCKED => 'success',
                        ResourceLockAudit::ACTION_UNLOCKED => 'info',
                        ResourceLockAudit::ACTION_EXPIRED => 'warning',
                        ResourceLockAudit::ACTION_FORCE_UNLOCKED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ResourceLockAudit::ACTION_LOCKED => __('filament-resource-lock::audit.locked'),
                        ResourceLockAudit::ACTION_UNLOCKED => __('filament-resource-lock::audit.unlocked'),
                        ResourceLockAudit::ACTION_EXPIRED => __('filament-resource-lock::audit.expired'),
                        ResourceLockAudit::ACTION_FORCE_UNLOCKED => __('filament-resource-lock::audit.force_unlocked'),
                        default => $state,
                    }),
                TextColumn::make('lockable_type')
                    ->label(__('filament-resource-lock::audit.columns.lockable_type')),
                TextColumn::make('lockable_id')
                    ->label(__('filament-resource-lock::audit.columns.lockable_id')),
                TextColumn::make('user_id')
                    ->label(__('filament-resource-lock::audit.columns.user_id')),
                TextColumn::make('actor_user_id')
                    ->label(__('filament-resource-lock::audit.columns.actor_user_id'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('filament-resource-lock::audit.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->label(__('filament-resource-lock::audit.filters.action'))
                    ->options([
                        ResourceLockAudit::ACTION_LOCKED => __('filament-resource-lock::audit.locked'),
                        ResourceLockAudit::ACTION_UNLOCKED => __('filament-resource-lock::audit.unlocked'),
                        ResourceLockAudit::ACTION_EXPIRED => __('filament-resource-lock::audit.expired'),
                        ResourceLockAudit::ACTION_FORCE_UNLOCKED => __('filament-resource-lock::audit.force_unlocked'),
                    ]),
                Filter::make('created_at')
                    ->label(__('filament-resource-lock::audit.filters.created_at'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('filament-resource-lock::audit.filters.from')),
                        DatePicker::make('until')
                            ->label(__('filament-resource-lock::audit.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
