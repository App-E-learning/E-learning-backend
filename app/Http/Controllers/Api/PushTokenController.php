<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'device_type' => ['nullable', 'string', 'in:ios,android,web'],
        ]);

        $pushToken = PushToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'device_type' => $validated['device_type'] ?? null,
            ]
        );

        return response()->json(['data' => $pushToken], 201);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        PushToken::where('user_id', $request->user()->id)
            ->where('token', $request->input('token'))
            ->delete();

        return response()->noContent();
    }
}
