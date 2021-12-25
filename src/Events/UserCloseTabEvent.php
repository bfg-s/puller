<?php

namespace Bfg\Puller\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCloseTabEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $guard;
    public ?int $user_id;
    public ?string $tab;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $guard, int $user_id = null, string $tab = null)
    {
        $this->guard = $guard;
        $this->user_id = $user_id;
        $this->tab = $tab;
    }
}
