<?php

namespace Bfg\Puller\Pulls;

use Bfg\Puller\Pull;

class RedirectUserPull extends Pull
{
    protected string $url;

    protected ?string $name = "document:location";

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function handle()
    {
        return $this->url;
    }
}
