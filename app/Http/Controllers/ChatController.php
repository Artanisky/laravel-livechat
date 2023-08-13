<?php

namespace App\Http\Controllers;

use App\Events\MessageSeen;
use App\Events\MessageSent;
use App\Http\Requests\MessageFormRequest;
use App\Http\Requests\MessageSeenFormRequest;
use App\Models\Message;

class ChatController extends Controller
{
    public function messages()
    {
        return Message::query()->with(['user','messagesSeen' => function ($query) {
            $query->with('user');
        }])->get();
    }

    public function send(MessageFormRequest $request)
    {
        $message = $request->user()
            ->messages()
            ->create($request->validated());

        broadcast(new MessageSent($request->user(), $message))->toOthers();

        return $message;
    }

    public function seen(MessageSeenFormRequest $request)
    {
        $message = Message::query()->with('user')->find($request->validated())->first();
        $messageSeen = \App\Models\MessageSeen::query()->where([
            'message_id' => $message->id,
            'user_id' => $request->user()->id
        ])->first();

        // Проверка есть ли сообщение с таким id и уже просмотренных сообщенийц
        if ($message && !$messageSeen) {
            // Проверка чтобы не "смотреть" свои сообщение
            if ($request->user()->id !== $message->user_id) {
                $request->user()
                    ->messagesSeen()
                    ->create([
                        'message_id' => $message->id
                    ]);
                broadcast(new MessageSeen($request->user(), $message));
                return true;
            }
        }

        return false;
    }
}
