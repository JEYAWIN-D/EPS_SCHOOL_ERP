<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notifications = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        $formatted = collect($notifications->items())->map(function ($n) {
            $data = json_decode($n->data, true) ?: [];
            return [
                'id'         => $n->id,
                'module'     => $data['module'] ?? 'general',
                'title'      => $data['title'] ?? 'Notification',
                'message'    => $data['message'] ?? '',
                'action_url' => $data['action_url'] ?? '#',
                'type'       => $data['type'] ?? 'info',
                'read_at'    => $n->read_at,
                'is_read'    => !is_null($n->read_at),
                'created_at' => $n->created_at,
            ];
        });

        return response()->json([
            'status'       => 'success',
            'unread_count' => DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'data'         => $formatted,
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
            'total'        => $notifications->total(),
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_count' => 0]);
        }

        $count = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        // Also fetch top 5 recent notifications for quick dropdown render
        $recent = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($n) {
                $data = json_decode($n->data, true) ?: [];
                return [
                    'id'         => $n->id,
                    'title'      => $data['title'] ?? 'Notification',
                    'message'    => $data['message'] ?? '',
                    'action_url' => $data['action_url'] ?? '#',
                    'read_at'    => $n->read_at,
                    'created_at' => $n->created_at,
                ];
            });

        return response()->json([
            'unread_count' => $count,
            'recent'       => $recent,
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();
        DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status'  => 'success',
            'message' => 'All notifications marked as read',
        ]);
    }
}
