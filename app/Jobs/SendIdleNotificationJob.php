<?php

namespace App\Jobs;

use App\Models\IdleEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendIdleNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute retry

    public function __construct(
        private User $salesRep,
        private IdleEvent $idleEvent
    ) {}

    public function handle(): void
    {
        // SR এর FCM device token (users table এ fcm_token column থাকতে হবে)
        $fcmToken = $this->salesRep->fcm_token;

        if (!$fcmToken) {
            Log::warning("No FCM token for SR #{$this->salesRep->id} ({$this->salesRep->name})");
            return;
        }

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => 'Activity Check',
                    'body'  => 'No order placed in 90 minutes. Please log your current activity.',
                ],
                'data' => [
                    'type'         => 'idle_alert',
                    'idle_event_id'=> (string) $this->idleEvent->id,
                    'idle_since'   => $this->idleEvent->start_time->toIso8601String(),
                    'click_action' => 'IDLE_ALERT_SCREEN', // Flutter/React Native deep link
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'idle_alerts',
                        'sound'      => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound'             => 'default',
                            'badge'             => 1,
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $accessToken = $this->getFirebaseAccessToken();

            $projectId = config('services.firebase.project_id');

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->failed()) {
                Log::error('FCM send failed', [
                    'sr_id'    => $this->salesRep->id,
                    'response' => $response->json(),
                ]);
                $this->fail(new \Exception('FCM send failed: ' . $response->body()));
            }

            Log::info("Idle notification sent to SR #{$this->salesRep->id}");

        } catch (\Throwable $e) {
            Log::error('FCM exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Firebase service account দিয়ে access token নেওয়া।
     * config/services.php এ firebase.credentials_path set করো।
     */
    private function getFirebaseAccessToken(): string
    {
        // Google Auth Library দিয়ে OAuth2 token নাও
        // composer require google/auth
        $credentialsPath = config('services.firebase.credentials_path');

        $credentials = \Google\Auth\ApplicationDefaultCredentials::getCredentials(
            'https://www.googleapis.com/auth/firebase.messaging'
        );

        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }
}
