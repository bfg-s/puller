<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Core\Traits\CacheManagerCheckTrait;
use Bfg\Puller\Core\Traits\CacheManagerCleanerTrait;
use Bfg\Puller\Core\Traits\CacheManagerConditionTrait;
use Bfg\Puller\Core\Traits\CacheManagerEventEmitsTrait;
use Bfg\Puller\Core\Traits\CacheManagerGettersTrait;
use Bfg\Puller\Core\Traits\CacheManagerSettersTrait;

class CacheManager
{
    use CacheManagerGettersTrait,
        CacheManagerConditionTrait,
        CacheManagerCheckTrait,
        CacheManagerCleanerTrait,
        CacheManagerSettersTrait,
        CacheManagerEventEmitsTrait;

    public ?string $tab;
    public string $guard;
    public int $user_id;
    public bool $user_off = false;

    public function __construct(
        string $guard,
        int $user_id = 0,
        string $tab = null
    ) {
        $this->tab = $tab;
        $this->guard = $guard;
        $this->user_id = $user_id;
    }

    protected function key_of_tabs(): string
    {
        return "puller:tabs:{$this->guard}:{$this->user_id}";
    }

    protected function key_of_users(): string
    {
        return "puller:users:{$this->guard}";
    }
}
