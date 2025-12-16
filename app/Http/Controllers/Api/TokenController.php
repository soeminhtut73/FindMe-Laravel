<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/tokens
     * Return current user's token balance
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $balance = $user->tokens_balance;

        return $this->successResponse($balance, 'Token balance retrieved successfully', 200);
    }

    /**
     * POST /api/tokens/topup
     * Body: { "amount": 10 }
     *
     * This will add the given amount to the current user's balance.
     * Later you can restrict this to:
     * - only after successful IAP
     * - only for admin, etc.
     */
    public function topup(Request $request)
    {
        $user = $request->user();

        // 1) Validate input
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $amount = $data['amount'];

        // 2) Increase tokens_balance atomically
        $user->increment('tokens_balance', $amount);

        // Refresh user instance to get latest value
        $user->refresh();

        $data = [
            'amount_added' => $amount,
            'new_balance' => $user->tokens_balance,
        ];

        return $this->successResponse($data, 'Tokens topped up successfully', 200);

        return response()->json([
            'message' => 'Tokens topped up successfully',
            'amount_added' => $amount,
            'new_balance' => $user->tokens_balance,
        ], 200);
    }
}
