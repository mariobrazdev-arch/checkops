<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * US-20 — Salva subscription push do navegador.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'   => ['required', 'string', 'url'],
            'public_key' => ['required', 'string'],
            'auth_token' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id'    => $request->user()->id,
                'public_key' => $request->public_key,
                'auth_token' => $request->auth_token,
            ],
        );

        return response()->json(['message' => 'Subscription salva'], 201);
    }

    /**
     * US-20 — Remove subscription push (ex: ao fazer logout).
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'string']]);

        PushSubscription::where('endpoint', $request->endpoint)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(null, 204);
    }
}
