# Resource Lock

Filament Resource Lock is a Filament plugin that adds resource locking functionality to your site. When a user begins editing a resource, it is automatically locked to prevent other users from editing it at the same time. The resource will be automatically unlocked after a set period of time, or when the user saves or discards their changes.

## Installation

```bash
composer require blendbyte/filament-resource-lock
```

Then run the installation command to publish and run the migration:

```bash
php artisan filament-resource-lock:install
```

Register the plugin with a panel:

```php
use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(ResourceLockPlugin::make());
}
```

## Usage

### Add Locks to your model

Add the `HasLocks` trait to the model you want to lock:

```php
use Blendbyte\FilamentResourceLock\Models\Concerns\HasLocks;

class Post extends Model
{
    use HasFactory;
    use HasLocks;
}
```

### Add Locks to your EditRecord page

Add the `UsesResourceLock` trait to your `EditRecord` page:

```php
use Blendbyte\FilamentResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class EditPost extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = PostResource::class;
}
```

### Simple modal resource

For simple modal resources, use `UsesSimpleResourceLock` instead:

```php
use Blendbyte\FilamentResourceLock\Resources\Pages\Concerns\UsesSimpleResourceLock;

class ManagePosts extends ManageRecords
{
    use UsesSimpleResourceLock;

    protected static string $resource = PostResource::class;
}
```

### Relation manager locking

To lock related records when editing them via a relation manager, add `UsesRelationManagerResourceLock` to your relation manager class. The related model also needs the `HasLocks` trait.

```php
use Blendbyte\FilamentResourceLock\Resources\Pages\Concerns\UsesRelationManagerResourceLock;

class PostCommentsRelationManager extends RelationManager
{
    use UsesRelationManagerResourceLock;

    protected static string $relationship = 'comments';
}
```

When a user opens the edit modal for a related record, it is locked for the duration of the edit session and released when the modal is closed.

## Polling (SPA mode)

To support SPA mode, enable polling-based presence detection in the plugin:

```php
->plugin(ResourceLockPlugin::make()
    ->usesPollingToDetectPresence()
    ->presencePollingInterval(10)
    ->lockTimeout(15)
)
```

> **Tip:** Make sure the lock timeout is not lower than the polling interval — otherwise the lock may expire before the next heartbeat is sent.

Additional polling options:
- **`pollingKeepAlive()`**: Keeps polling alive when the tab is in the background.
- **`pollingVisible()`**: Only polls when the browser tab is visible.

## Resource Lock Manager

The package includes a UI to view and manage all active and expired locks, and to unlock resources individually or in bulk.

## Configuration

### Access control

Restrict access to the Unlock button or resource manager using a gate or Spatie permission:

```php
->plugin(ResourceLockPlugin::make()
    ->limitedAccessToResourceLockManager()
    ->gate('unlock')
)
```

### Custom models

```php
->plugin(ResourceLockPlugin::make()
    ->userModel(\App\Models\CustomUser::class)
    ->resourceLockModel(\App\Models\CustomResourceLock::class)
)
```

### Custom lock owner display

Create a custom action class extending `GetResourceLockOwnerAction`:

```php
namespace App\Actions;

use Blendbyte\FilamentResourceLock\Actions\GetResourceLockOwnerAction;

class CustomResourceLockOwnerAction extends GetResourceLockOwnerAction
{
    public function execute($userModel): string|null
    {
        return $userModel->email;
    }
}
```

Register it in the plugin:

```php
->plugin(ResourceLockPlugin::make()
    ->resourceLockOwnerAction(\App\Actions\CustomResourceLockOwnerAction::class)
)
```

### Overriding default functionality

Override `resourceLockReturnUrl()` to change where the Return button redirects:

```php
public function resourceLockReturnUrl(): string
{
    return route('dashboard');
}
```

## Publishing assets

```bash
# Migrations
php artisan vendor:publish --tag="filament-resource-lock-migrations"
php artisan migrate

# Views
php artisan vendor:publish --tag="filament-resource-lock-views"
```

## Contributing

Please see [GitHub releases](https://github.com/blendbyte/filament-resource-lock/releases) for changelog information.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
