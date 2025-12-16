<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\Location;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LocationController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/location/send
     * Body:
     * {
     *   "receiver_uid": "receiver-uid-here",
     *   "ciphertext": "BASE64_ENCRYPTED_DATA",
     *   "iv": "BASE64_IV",
     *   "meta": { ... } // optional
     * }
     */
    public function send(Request $request)
    {
        $sender = $request->user();

        // 1) Validate request body
        $data = $request->validate([
            'receiver_uid' => ['required', 'string'],
            'ciphertext' => ['required', 'string'],
            'iv' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ]);

        // 2) Find receiver by uid
        $receiver = User::where('uid', $data['receiver_uid'])->first();

        if (! $receiver) {
            return $this->notFoundResponse('Receiver not found');
        }

        if ($receiver->id === $sender->id) {
            return $this->errorResponse('You cannot send location to yourself', 422);
        }

        // 3) Check friend relationship (must exist and be active)
        $friendRelation = Friend::where('user_id', $sender->id)
            ->where('friend_id', $receiver->id)
            ->where('status', 'active')
            ->first();

        if (! $friendRelation) {
            return $this->errorResponse('Receiver is not in your active friend list', 422);
        }

        // 4) Check tokens balance
        if ($sender->tokens_balance <= 0) {
            return $this->errorResponse('Not enough tokens to send location', 402);
        }

        // 5) Create location record
        $location = Location::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'encrypted_payload' => $data['ciphertext'],
            'iv' => $data['iv'] ?? null,
            'meta' => $data['meta'] ?? null,
            // Example: expire after 1 hour, or keep null if you don't want expiry
            // 'expires_at'    => Carbon::now()->addHour(),
        ]);

        // 6) Decrement sender tokens
        $sender->decrement('tokens_balance');

        $data = [
            'location_id' => $location->id,
            'tokens_balance' => $sender->fresh()->tokens_balance,
        ];

        return $this->successResponse($data, 'Location sent successfully', 200);
    }

    /**
     * GET /api/location/{id}
     * Only sender or receiver can view.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $location = Location::with(['sender', 'receiver'])->find($id);

        if (! $location) {
            return $this->notFoundResponse('Location not found');
        }

        // Check permission: must be sender or receiver
        if ($location->receiver_id !== $user->id && $location->sender_id !== $user->id) {
            return $this->errorResponse('Unauthorized access to this location share', 403);
        }

        // Optional: check expiry
        // if ($location->expires_at && $location->expires_at->isPast()) {
        //     return response()->json([
        //         'message' => 'This location share has expired',
        //     ], 410); // 410 Gone (optional)
        // }

        $data = [
            'id' => $location->id,
            'ciphertext' => $location->encrypted_payload,
            'iv' => $location->iv,
            'meta' => $location->meta,
            'sender' => [
                'id' => $location->sender->id,
                'uid' => $location->sender->uid,
                'username' => $location->sender->username,
                'avatar_url' => $location->sender->avatar_url,
            ],
            'receiver' => [
                'id' => $location->receiver->id,
                'uid' => $location->receiver->uid,
                'username' => $location->receiver->username,
                'avatar_url' => $location->receiver->avatar_url,
            ],
            'created_at' => $location->created_at,
        ];

        return $this->successResponse($data, 'Location retrieved successfully', 200);
    }
}
