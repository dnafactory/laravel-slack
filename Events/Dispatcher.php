<?php

namespace DNAFactory\Slack\Events;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class Dispatcher
{
    const PRE_HANDLERS = [
        'url_verification' => UrlVerificationHandler::class,
        'event_callback' => EventCallbackDispatcher::class
    ];

    public function dispatch(array $request)
    {
        $type = Arr::get($request, 'type', null);
        if (!array_key_exists($type, self::PRE_HANDLERS)) {
            Log::debug("unknown event type '$type'", (array)$request);
            return null;
        }
        $handler = app(self::PRE_HANDLERS[$type]);
        return $handler->handle($request);
    }
}
