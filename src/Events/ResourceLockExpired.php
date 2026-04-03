<?php

namespace Blendbyte\FilamentResourceLock\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResourceLockExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $lockable,
        public readonly int|string $originalUserId,
    ) {}
}
