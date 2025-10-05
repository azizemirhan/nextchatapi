<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private $messaging;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/firebase/service-account.json');

        if (!file_exists($serviceAccountPath)) {
            throw new \Exception("Firebase service account not found");
        }

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($fcmToken, $title, $body, $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM Error: ' . $e->getMessage());
            return false;
        }
    }
}
