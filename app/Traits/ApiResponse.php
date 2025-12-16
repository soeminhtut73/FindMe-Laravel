<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Success response method.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, ?string $message = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Operation successful',
            'data' => $data,
        ], $code);
    }

    /**
     * Error response method.
     *
     * @param  mixed  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(?string $message = null, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? 'An error occurred',
        ], $code);
    }

    /**
     * Paginated response method.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $paginator
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse($paginator, ?string $message = null)
    {
        $data = [
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];

        return $this->successResponse($data, $message);
    }

    /**
     * Resource not found response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $resource = 'Resource')
    {
        return $this->errorResponse($resource, 404);
    }
}
