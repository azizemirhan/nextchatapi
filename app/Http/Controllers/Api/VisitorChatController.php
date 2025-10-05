<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;

class VisitorChatController extends Controller
{
    public function initSession(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|exists:users,api_key', // api_key'in geçerli ve users tablosunda var olduğunu doğrula
            'session_id' => 'required|string',
            'visitor_ip' => 'nullable|ip',
        ]);

        // 1. API anahtarı ile doğru kullanıcıyı veritabanından bul.
        $user = User::where('api_key', $request->api_key)->firstOrFail();

        // 2. Sohbeti, bulunan kullanıcının ID'si ile oluştur veya mevcut olanı getir.
        $chatSession = ChatSession::firstOrCreate(
            ['session_id' => $request->session_id],
            [
                'user_id' => $user->id, // <-- ARTIK ID DİNAMİK OLARAK ATANIYOR
                'visitor_ip' => $request->visitor_ip,
                'status' => 'active',
                'last_activity' => now()
            ]
        );

        $messages = $chatSession->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'session_id' => $chatSession->session_id,
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'message' => 'required|string|max:1000',
            'sender_type' => 'required|in:visitor,admin',
        ]);

        $session = ChatSession::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $session->update(['last_activity' => now()]);

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => $request->sender_type,
            'message' => $request->message,
        ]);

        // Visitor mesajıysa admin'e bildirim gönder
        if ($request->sender_type === 'visitor' && $session->user->fcm_token) {
            $fcmService = new FcmService();
            $fcmService->sendNotification(
                $session->user->fcm_token,
                'New Message',
                ($session->visitor_name ?? 'Visitor') . ': ' . $request->message,
                ['session_id' => $session->session_id]
            );
        }

        return response()->json(['success' => true, 'data' => $message]);
    }

    public function getMessages(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        $session = ChatSession::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'messages' => $session->messages
        ]);
    }

    public function updateVisitorInfo(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $session = ChatSession::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $session->update([
            'visitor_name' => $request->name,
            'visitor_email' => $request->email,
        ]);

        return response()->json(['success' => true]);
    }
}
