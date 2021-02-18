<?php

namespace DNAFactory\Slack;

use DNAFactory\Slack\Events\EventCallbackDispatcher;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\ServiceProvider;

class SlackServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/laravel-slack.php' => config_path('laravel-slack.php'),
        ], 'config');

        $this->app['router']->post(config('laravel-slack.url'), SlackEventsController::class.'@index');
    }

    public function register()
    {
        $token = config('laravel-slack.api_token');

        $this->app->singleton(Endpoints\Users::class, function () use ($token) {
            $users = new Endpoints\Users(app(HttpClient::class));
            $users->setBaseUrl('https://slack.com/api');
            $users->setToken($token);
            return $users;
        });

        $this->app->singleton(Endpoints\Chat::class, function () use ($token) {
            $chat = new Endpoints\Chat(app(HttpClient::class));
            $chat->setBaseUrl('https://slack.com/api');
            $chat->setToken($token);
            return $chat;
        });

        $handlers = config('laravel-slack.event_handlers', []);
        $this->app->singleton(EventCallbackDispatcher::class, function () use ($handlers) {
            return new EventCallbackDispatcher($handlers);
        });


    }
}
