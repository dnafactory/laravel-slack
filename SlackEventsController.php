<?php

namespace DNAFactory\Slack;

use DNAFactory\Slack\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Psr\Log\LoggerInterface;

class SlackEventsController extends Controller
{
    protected $logger;
    protected $dispatcher;
    protected $token;

    public function __construct(
        LoggerInterface $logger,
        Dispatcher $dispatcher
    ) {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->token = config('laravel-slack.signing_secret');
    }

    public function index(Request $request){
        if (!$this->verifySignature($request)) {
            $this->logger->alert('SlackEvent request has an invalid signature', $request->toArray());
            return null;
        }
        return $this->dispatcher->dispatch($request->json()->all());
    }

    protected function verifySignature(Request $request): bool
    {
        $requestBody = $request->getContent();
        $requestTimestamp = $request->header('X-Slack-Request-Timestamp', null);
        $requestSignature = $request->header('X-Slack-Signature', null);
        if (!$requestSignature || !$requestTimestamp || abs($requestTimestamp-time()) > 300) {
            return false;
        }
        $buffer = "v0:{$requestTimestamp}:{$requestBody}";
        $signature = 'v0='.hash_hmac('sha256', $buffer, $this->token);
        return $signature == $requestSignature;
    }
}
