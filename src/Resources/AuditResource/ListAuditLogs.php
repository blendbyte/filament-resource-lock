<?php

namespace Blendbyte\FilamentResourceLock\Resources\AuditResource;

use Blendbyte\FilamentResourceLock\Resources\AuditResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
