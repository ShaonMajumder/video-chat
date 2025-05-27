<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\NewMessage;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $receiverId = $request->query('receiver');
        return view('chat', ['receiverId' => $receiverId]);
    }

    public function sendMessage(Request $request)
    {
        broadcast(new NewMessage( $request->user->id, $request->receiver_id, strip_tags($request->message)))->toOthers();
        return response()->json(['status' => 'Message Sent!']);
    }
}
