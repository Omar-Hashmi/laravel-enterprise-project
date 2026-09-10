<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    /**
     * Display a paginated listing of the authenticated user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filter = (string) $request->query('filter', 'all');

        $query = match ($filter) {
            'unread' => $user->unreadNotifications(),
            'read' => $user->readNotifications(),
            default => $user->notifications(),
        };

        $perPage = (int) $request->query('per_page', 15);
        $notifications = $query->latest()->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Get the count of unread notifications for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Retrieve the user's notification channel preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        return response()->json([
            'preferences' => $request->user()->notificationPreferences()->get(),
        ]);
    }

    /**
     * Update the user's notification channel preferences.
     */
    public function updatePreferences(Request $request, NotificationPreferenceService $service): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.channel' => ['required', 'string', Rule::in(['mail', 'database', 'sms', 'slack'])],
            'preferences.*.notification_type' => ['sometimes', 'string', 'max:64'],
            'preferences.*.enabled' => ['required', 'boolean'],
        ]);

        $service->updatePreferences($request->user(), $validated['preferences']);

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'preferences' => $request->user()->notificationPreferences()->get(),
        ]);
    }
}
