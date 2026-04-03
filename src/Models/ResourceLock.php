<?php

namespace Blendbyte\FilamentResourceLock\Models;

use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int|string $user_id
 * @property Carbon|null $updated_at
 */
class ResourceLock extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(ResourceLockPlugin::get()->getUserModel());
    }

    public function lockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        $expiredDate = (new Carbon($this->updated_at))->addSeconds(ResourceLockPlugin::get()->getLockTimeout());

        return Carbon::now()->greaterThan($expiredDate);
    }
}
