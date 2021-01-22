<?php

namespace DNAFactory\Slack\Endpoints;

use DNAFactory\Slack\Exceptions\SlackErrorException;

class Users extends Proxy
{
    /**
     * @return array
     * @throws SlackErrorException
     */
    public function list(): array
    {
        $request = $this->jsonCall('users.list');
        if(!$request['ok']){
            throw new SlackErrorException('error with users.list request, response: '.json_encode($request));
        }
        return $request['members'];
    }
}
