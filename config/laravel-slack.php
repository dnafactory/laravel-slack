<?php

return [
    'url' => 'slack/events',
    'api_token' => env('SLACK_API_TOKEN'),
    'signing_secret' => env('SLACK_SIGNING_SECRET'),
    'event_handlers' => [
        // 'event_type' => child class of DNAFactory\Slack\Events\BaseEventHandler
    ],
];
