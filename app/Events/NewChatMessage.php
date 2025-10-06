<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// "ShouldBroadcast" arayüzünü eklediğimize dikkat edin.
class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Dışarıdan bir ChatMessage modeli alacağız.
    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Olayın yayınlanacağı kanalı/kanalları alın.
     * Her sohbet oturumu için özel (private) bir kanal oluşturuyoruz.
     * Kanal adı: "chat.OTURUM_ID"
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->message->chatSession->session_id),
        ];
    }

    /**
     * Yayına gönderilecek verinin adını belirler.
     * Mobil tarafta bu ismi dinleyeceğiz.
     */
    public function broadcastAs(): string
    {
        return 'new.message';
    }
}
