<?php

namespace DNAFactory\Slack\Events;

abstract class BaseEventHandler
{
    protected array $request;

    public function setRequest(array $request)
    {
        $this->request = $request;
    }

    abstract public function handle(array $event);
}
