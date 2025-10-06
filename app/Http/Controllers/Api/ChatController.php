<?php

namespace App\Http\Controllers\Api;

use App\Events\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function getSessions()
    {
        $sessions = ChatSession::forUser(auth()->id())
            // ESKİ HALİ: ->with('messages')
            ->with('lastMessage') // YENİ HALİ
            ->withCount('unreadVisitorMessages')
            ->latest('last_activity')
            ->get();

        return response()->json(['sessions' => $sessions]);
    }

    public function getSession($sessionId)
    {
        $session = ChatSession::where('session_id', $sessionId)
            ->where('user_id', auth()->id())
            ->with(['messages' => fn($q) => $q->orderBy('created_at')])
            ->firstOrFail();

        $session->unreadVisitorMessages()->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'messages' => $session->messages
        ]);
    }

    public function sendMessage(Request $request, $sessionId)
    {
        $request->validate(['message' => 'required|string']);

        $session = ChatSession::where('session_id', $sessionId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $message = $session->messages()->create([
            'sender_type' => 'admin',
            'message' => $request->message,
        ]);

        // <-- YENİ EKLENEN SATIR BURASI
        // Mesajı yayına göndermeden önce ilişkili chatSession modelini yüklüyoruz.
        $message->load('chatSession');

        broadcast(new NewChatMessage($message))->toOthers();

        return response()->json(['data' => $message], 201);
    }

    public function deleteMessage($messageId)
    {
        $message = ChatMessage::findOrFail($messageId);

        if ($message->session->user_id !== auth()->id() || $message->sender_type !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }

    // ChatController.php -> getSessions() metodu

    public function deleteSession($sessionId)
    {
        try {
            $session = ChatSession::where('id', $sessionId)
                ->forUser(auth()->id())
                ->firstOrFail();

            // İlişkili tüm mesajları da silmek için
            $session->messages()->delete();
            $session->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Session could not be deleted.'], 500);
        }
    }

}
