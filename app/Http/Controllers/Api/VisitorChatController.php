<?php

namespace App\Http\Controllers\Api;

use App\Events\NewChatMessage;
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
        return response()->json(['success' => true, 'messages' => []]);
    }

    public function sendMessage(Request $request)
    {
        if ($request->filled('honeypot_email')) {
            return response()->json(['success' => true]);
        }

        $request->validate([
            'api_key' => 'required|string|exists:users,api_key',
            'session_id' => 'required|string',
            'message' => 'required|string|max:1000',
        ]);

        $user = User::where('api_key', $request->api_key)->firstOrFail();

        $chatSession = ChatSession::firstOrCreate(
            ['session_id' => $request->session_id],
            [
                'user_id' => $user->id,
                'visitor_ip' => $request->ip(),
                'status' => 'active',
            ]
        );

        $chatSession->update(['last_activity' => now()]);

        $message = $chatSession->messages()->create([
            'sender_type' => 'visitor',
            'message' => $request->message,
        ]);

        // <-- YENİ EKLENEN SATIR BURASI
        // Mesajı yayına göndermeden önce ilişkili chatSession modelini yüklüyoruz.
        $message->load('chatSession');

        broadcast(new NewChatMessage($message))->toOthers();

        if ($user->fcm_token) {
            $fcmService = new FcmService();
            $fcmService->sendNotification(
                $user->fcm_token,
                $chatSession->visitor_name ?? 'Yeni Mesaj',
                $request->message,
                ['session_id' => $chatSession->session_id]
            );
        }

        return response()->json(['success' => true, 'data' => $message]);
    }

    public function getMessages(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|exists:users,api_key',
            'session_id' => 'required|string',
        ]);

        $user = User::where('api_key', $request->api_key)->firstOrFail();

        $session = ChatSession::where('session_id', $request->session_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => true, 'messages' => []]);
        }

        return response()->json([
            'success' => true,
            'messages' => $session->messages
        ]);
    }

    public function updateVisitorInfo(Request $request)
    {
        if ($request->filled('honeypot_email')) {
            return response()->json(['success' => true]); // Bot'u kandırmak için başarılı cevap dön
        }

        $request->validate([
            'api_key' => 'required|string|exists:users,api_key',
            'session_id' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',

        ]);

        $user = User::where('api_key', $request->api_key)->firstOrFail();

        // Sohbeti bul veya OLUŞTUR. Çünkü kullanıcı bilgi girdikten sonra mesaj atmayabilir.
        $session = ChatSession::firstOrCreate(
            ['session_id' => $request->session_id],
            [
                'user_id' => $user->id,
                'visitor_ip' => $request->ip(),
                'status' => 'active'
            ]
        );

        $session->update([
            'visitor_name' => $request->name,
            'visitor_email' => $request->email,
        ]);

        return response()->json(['success' => true]);
    }
}
