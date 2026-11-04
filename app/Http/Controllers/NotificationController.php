<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->notifications()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'kind' => $n->data['kind'] ?? null,
                'title' => $n->data['title'] ?? '',
                'body' => $n->data['body'] ?? '',
                'url' => $n->data['url'] ?? null,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
            ])
            ->values();
    }

    public function unread(Request $request)
    {
        return $request->user()->unreadNotifications->map(fn ($n) => [
            'id' => $n->id,
            'kind' => $n->data['kind'] ?? null,
            'title' => $n->data['title'] ?? '',
            'body' => $n->data['body'] ?? '',
            'url' => $n->data['url'] ?? null,
            'created_at' => $n->created_at->diffForHumans(),
        ])->values();
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->each->markAsRead();

        return response()->json(['ok' => true]);
    }
}
