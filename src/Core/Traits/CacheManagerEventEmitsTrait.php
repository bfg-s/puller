<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Events\UserNewTabEvent;
use Bfg\Puller\Events\UserCloseTabEvent;
use Bfg\Puller\Events\UserOnlineEvent;
use Bfg\Puller\Events\UserOfflineEvent;

trait CacheManagerEventEmitsTrait
{
    public function emitOnNewTabEvent(bool $user_is_added = false)
    {
        event(new UserNewTabEvent($this->guard, $this->user_id, $this->tab, $user_is_added));
    }

    public function emitOnCloseTabEvent()
    {
        event(new UserCloseTabEvent($this->guard, $this->user_id, $this->tab));
    }

    public function emitOnUserOnlineEvent()
    {
        event(new UserOnlineEvent($this->guard, $this->user_id, $this->tab));
    }

    public function emitOnUserOfflineEvent()
    {
        event(new UserOfflineEvent($this->guard, $this->user_id, $this->tab));
    }
}
