<?php

namespace Bfg\Puller\Pulls;

use Bfg\Puller\Task;

class RedirectUserTask extends Task
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
