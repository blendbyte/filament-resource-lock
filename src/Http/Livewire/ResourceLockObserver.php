<?php

namespace Blendbyte\FilamentResourceLock\Http\Livewire;

use Illuminate\Support\Facades\Gate;
use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Livewire\Attributes\On;
use Livewire\Component;

class ResourceLockObserver extends Component
{
    public bool $isAllowedToUnlock = false;

    public bool $usesPollingToDetectPresence = false;

    public int $presencePollingInterval = 15;

    public bool $pollingKeepAlive = false;

    public bool $pollingVisible = false;

    public function render()
    {
        return view('filament-resource-lock::components.resource-lock-observer');
    }

    public function mount()
    {
        if (! ResourceLockPlugin::get()->shouldLimitUnlockerAccess()) {
            $this->isAllowedToUnlock = true;
        } else {
            $gate = ResourceLockPlugin::get()->getUnlockerGate();
            if ($gate !== null && Gate::allows($gate)) {
                $this->isAllowedToUnlock = true;
            }
        }
    }

    public function sendPresenceHeartbeat()
    {
        $this->dispatch('resourceLockObserver::renewLock');
    }

    #[On('enablePollingInResourceLockObserver')]
    public function enablePolling()
    {
        $this->presencePollingInterval = ResourceLockPlugin::get()->getPresencePollingInterval();
        $this->usesPollingToDetectPresence = ResourceLockPlugin::get()->shouldUsePollingToDetectPresence();
        $this->pollingKeepAlive = ResourceLockPlugin::get()->shouldUsePollingKeepAlive();
        $this->pollingVisible = ResourceLockPlugin::get()->shouldUsePollingVisible();
    }

    #[On('disablePollingInResourceLockObserver')]
    public function disablePolling()
    {
        $this->usesPollingToDetectPresence = false;
        $this->presencePollingInterval = 0;
        $this->pollingKeepAlive = false;
        $this->pollingVisible = false;
    }
}
