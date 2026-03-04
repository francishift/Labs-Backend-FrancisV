<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => 'required',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required'
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];
        $user = auth()->user();

        $user->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true], 200);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required']);

        $user = auth()->user();
        $user->deletePushSubscription($request->endpoint);

        return response()->json(['success' => true], 200);
    }
}
