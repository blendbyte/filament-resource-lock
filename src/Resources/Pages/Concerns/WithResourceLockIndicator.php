<?php

namespace Blendbyte\FilamentResourceLock\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait WithResourceLockIndicator
{
    public function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['resourceLock', 'resourceLock.user']);
    }
}
