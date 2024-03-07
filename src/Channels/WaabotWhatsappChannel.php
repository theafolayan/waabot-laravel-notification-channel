<?php

namespace ManeOlawale\Laravel\WaabotChannel\Channels;

use GuzzleHttp\Client;
use Illuminate\Container\Container;
use Illuminate\Notifications\Notification;
use ManeOlawale\Laravel\WaabotChannel\Messages\WhatsappMessage;

class WaabotWhatsappChannel
{
    /**
     * Callback to return the prefered client
     *
     * @static
     * @var string
     */
    protected static $clientCallback;

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \WaabotChannel\Message\Message
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('waabotWhatsapp', $notification)) {
            return;
        }

        /**
         * @var WhatsappMessage
         */
        $message = $notification->toWaabotWhatsapp($notifiable);

        if (is_string($message)) {
            $message = new WhatsappMessage($message);
        }

        $client = $this->makeClient();
        $response = $client->post(sprintf(
                '%/whatsapp/message?session_id=%&access_token=%',
                config('services.waabot.url'),
                config('services.waabot.session_id'),
                config('services.waabot.access_token'),
            ), [
            \GuzzleHttp\RequestOptions::JSON => [
                    'chatId' => $to,
                    'message' => $message->getContent()
            ]
        ]);

        return $response;
    }

    /**
     * Make client
     *
     * @return Client
     */
    protected function makeClient(): Client
    {
        if (isset(static::$clientCallback)) {
            return Container::getInstance()->call(static::$clientCallback);
        }

        return new Client();
    }

    /**
     * Set the client to be used
     *
     * @param \Closure $callback
     * @return void
     */
    public static function clientUsing(\Closure $callback)
    {
        static::$clientCallback = $callback;
    }
}