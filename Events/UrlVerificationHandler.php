<?php

namespace DNAFactory\Slack\Events;

class UrlVerificationHandler
{
    public function handle(array $request)
    {
        return $request['challenge'];
    }
}
