<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class FrontendChatController extends Controller
{
    public function initSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'session_id' => 'required|string',
            'visitor_ip' => 'required|string',
            'site_domain' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // API key'den user bul
        $user = User::where('api_key', $request->api_key)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz API anahtarı'
            ], 401);
        }

        // Token oluştur
        $token = $user->createToken('chat-session')->plainTextToken;

        $chatSession = ChatSession::firstOrCreate(
            [
                'session_id' => $request->session_id,
                'user_id' => $user->id
            ],
            [
                'visitor_ip' => $request->visitor_ip,
                'status' => 'active',
                'last_activity' => now()
            ]
        );

        $messages = $chatSession->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'token' => $token,
            'session_id' => $chatSession->session_id,
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'message' => 'required|string|max:1000',
            'sender_type' => 'required|in:visitor,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $chatSession = ChatSession::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$chatSession) {
            return response()->json([
                'success' => false,
                'message' => 'Session bulunamadı'
            ], 404);
        }

        $chatSession->update(['last_activity' => now()]);

        $message = ChatMessage::create([
            'chat_session_id' => $chatSession->id,
            'sender_type' => $request->sender_type,
            'message' => $request->message
        ]);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    public function getMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $chatSession = ChatSession::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$chatSession) {
            return response()->json([
                'success' => false,
                'message' => 'Session bulunamadı'
            ], 404);
        }

        $messages = $chatSession->messages()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function updateVisitorInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $chatSession = ChatSession::where('session_id', $request->session_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$chatSession) {
            return response()->json([
                'success' => false,
                'message' => 'Session bulunamadı'
            ], 404);
        }

        $chatSession->update([
            'visitor_name' => $request->name,
            'visitor_email' => $request->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bilgiler güncellendi'
        ]);
    }
}
