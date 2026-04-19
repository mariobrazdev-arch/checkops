<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class EnviarPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        private readonly string $userId,
        private readonly string $titulo,
        private readonly string $corpo,
        private readonly string $url = '/colaborador/rotinas',
    ) {}

    public function handle(): void
    {
        $subscriptions = PushSubscription::where('user_id', $this->userId)->get();

        if ($subscriptions->isEmpty()) {
            // Fallback para e-mail se não há subscription push
            EnviarEmailNotificacao::dispatch($this->userId, $this->titulo, $this->corpo)
                ->onQueue('notifications');
            return;
        }

        $vapidPublic  = config('services.vapid.public_key');
        $vapidPrivate = config('services.vapid.private_key');

        // Se VAPID não está configurado, fallback e-mail
        if (!$vapidPublic || !$vapidPrivate) {
            EnviarEmailNotificacao::dispatch($this->userId, $this->titulo, $this->corpo)
                ->onQueue('notifications');
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => $vapidPublic,
                'privateKey' => $vapidPrivate,
            ],
        ]);

        $payload = json_encode([
            'title' => $this->titulo,
            'body'  => $this->corpo,
            'url'   => $this->url,
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->public_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]),
                $payload,
            );
        }

        foreach ($webPush->flush() as $report) {
            // Remove subscriptions expiradas/inválidas
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }
    }
}
