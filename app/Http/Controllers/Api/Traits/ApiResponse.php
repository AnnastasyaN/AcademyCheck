<?php

namespace App\Http\Controllers\Api\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], fn ($value) => $value !== null), $code);
    }

    protected function created(mixed $data = null, string $message = 'Berhasil dibuat.'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function unauthorized(string $message = 'Unauthorized.'): JsonResponse
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function notFound(string $message = 'Resource tidak ditemukan.'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function validationError(mixed $errors, string $message = 'Validasi gagal.'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    protected function serverError(string $message = 'Terjadi kesalahan internal server.'): JsonResponse
    {
        return $this->error($message, 500);
    }

    protected function badGateway(string $message = 'Layanan tidak tersedia. Silakan coba kembali.'): JsonResponse
    {
        return $this->error($message, 502);
    }

    protected function paginated(mixed $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'next_page_url' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
