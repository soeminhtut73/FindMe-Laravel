<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/friends
     * List current user's friends
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $friends = Friend::with('friend')
            ->where('user_id', $user->id)
            ->get()
            ->map(function (Friend $f) {
                return [
                    'id' => $f->id,
                    'friend_id' => $f->friend->id,
                    'uid' => $f->friend->uid,
                    'username' => $f->friend->username,
                    'email' => $f->friend->email,
                    'status' => $f->status,
                    'avatar_url' => $f->friend->avatar_url,
                ];
            });

        $data = $friends;

        return $this->successResponse($data, 'Friends retrieved successfully', 200);
    }

    /**
     * GET /api/friends/search?q=...
     * Search users by uid / email / user_name (excluding self)
     */
    public function search(Request $request)
    {
        $user = $request->user();
        $q = $request->query('q');

        if (! $q) {
            return $this->successResponse([], 'No query provided', 200);
        }

        $results = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($query) use ($q) {
                $query->where('uid', 'LIKE', "%{$q}%")
                    ->orWhere('email', 'LIKE', "%{$q}%")
                    ->orWhere('username', 'LIKE', "%{$q}%");
            })
            ->take(20)
            ->get(['id', 'uid', 'username', 'email', 'avatar_url']);

        return $this->successResponse($results, 'Search results retrieved successfully', 200);
    }

    /**
     * POST /api/friends
     * Body: { "friend_uid": "..." }
     * Add/save friend (Case1 or Case2 in your flow)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'friend_uid' => ['required', 'string'],
        ]);

        // Find the friend user by uid
        $friendUser = User::where('uid', $data['friend_uid'])->first();

        if (! $friendUser) {
            return $this->notFoundResponse('User not found');
        }

        if ($friendUser->id === $user->id) {
            return $this->errorResponse('You cannot add yourself as a friend', 422);
        }

        // Check existing relation
        $friend = Friend::firstOrCreate(
            [
                'user_id' => $user->id,
                'friend_id' => $friendUser->id,
            ],
            [
                'status' => 'active',
            ]
        );

        return $this->successResponse(null, 'Friend added successfully', 201);
    }

    /**
     * DELETE /api/friends/{id}
     * Remove friend relation (only for current user)
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();

        $friend = Friend::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $friend) {
            return $this->notFoundResponse('Friend relation not found');
        }

        $friend->delete();

        return $this->successResponse(null, 'Friend deleted', 200);
    }

    /**
     * POST /api/friends/{id}/block
     */
    public function block(Request $request, int $id)
    {
        $user = $request->user();

        $friend = Friend::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $friend) {
            return $this->notFoundResponse('Friend relation not found');
        }

        $friend->status = 'blocked';
        $friend->save();

        return $this->successResponse(null, 'Friend blocked', 200);
    }

    /**
     * POST /api/friends/{id}/unblock
     */
    public function unblock(Request $request, int $id)
    {
        $user = $request->user();

        $friend = Friend::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $friend) {
            return $this->notFoundResponse('Friend relation not found');
        }

        $friend->status = 'active';
        $friend->save();

        return $this->successResponse(null, 'Friend unblocked', 200);
    }
}
