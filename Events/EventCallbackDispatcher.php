<?php

namespace DNAFactory\Slack\Events;

use DNAFactory\Slack\Exceptions\UnknownEventException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class EventCallbackDispatcher
{
    protected array $handlers;

    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    public function handle(array $request)
    {
        $type = Arr::get($request, 'event.type', null);
        if (!isset($this->handlers[$type])) {
            Log::debug("unknown event type '$type'", (array)$request);
            throw new UnknownEventException($type);
        }
        $handler = app($this->handlers[$type]);
        /** @var BaseEventHandler $handler */
        $handler->setRequest($request);
        return $handler->handle($request['event']);
    }
}
