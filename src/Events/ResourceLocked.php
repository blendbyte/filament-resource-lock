<?php

namespace Blendbyte\FilamentResourceLock\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResourceLocked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $lockable,
        public readonly int|string $userId,
    ) {}
}
