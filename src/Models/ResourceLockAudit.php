<?php

namespace Blendbyte\FilamentResourceLock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ResourceLockAudit extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_LOCKED = 'locked';

    public const ACTION_UNLOCKED = 'unlocked';

    public const ACTION_EXPIRED = 'expired';

    public const ACTION_FORCE_UNLOCKED = 'force_unlocked';

    protected $table = 'resource_lock_audit';

    protected $guarded = [];

    public function lockable(): MorphTo
    {
        return $this->morphTo();
    }
}
