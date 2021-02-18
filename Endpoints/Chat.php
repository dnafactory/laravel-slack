<?php

namespace DNAFactory\Slack\Endpoints;

use DNAFactory\Slack\Exceptions\SlackErrorException;
use DNAFactory\Slack\Support\Proxy;

class Chat extends Proxy
{
    public function unfurl(array $params): array
    {
        $response = $this->jsonCall('/chat.unfurl', $params, 'POST', self::ENCODING_JSON);
        if(!$response['ok']){
            throw new SlackErrorException('error with chat.unfurl request, response: '.json_encode($response));
        }
        return $response;
    }
}
