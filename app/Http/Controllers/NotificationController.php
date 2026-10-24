<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Notifications de l'utilisateur authentifie.
 */
class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('date_envoi')
            ->limit(50)
            ->get();

        return NotificationResource::collection($notifications);
    }

    /** Nombre de notifications non lues, pour la pastille de l'interface. */
    public function nonLues(Request $request): JsonResponse
    {
        return response()->json([
            'non_lues' => $request->user()->notifications()->where('lu', false)->count(),
        ]);
    }

    public function marquerLue(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->id_utilisateur !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $notification->update(['lu' => true]);

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    public function toutMarquerLues(Request $request): JsonResponse
    {
        $request->user()->notifications()->where('lu', false)->update(['lu' => true]);

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }
}
