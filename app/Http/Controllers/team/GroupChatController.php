<?php

namespace App\Http\Controllers\team;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class GroupChatController extends Controller
{
  // Get messages for a group
public function messages($groupId)
{
    $messages = ChatMessage::where('chat_id', $groupId)
        ->with('user:id,name') // eager load user name
        ->get()
        ->map(function($m) {
            return [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'user_name' => $m->user->name, // add sender name
                'message' => $m->message,
                'created_at' => $m->created_at,
            ];
        });

    return response()->json($messages);
}


    public function send(Request $request, $chatId)
    {
        $request->validate(['message'=>'required|string']);

        $msg = ChatMessage::create([
            'chat_id'=>$chatId,
            'user_id'=>auth()->id(),
            'message'=>$request->message
        ]);

        return response()->json($msg);
    }
}
