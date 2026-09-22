<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->select('SELECT 1');

            return $this->response(
                status: 'ok',
                database: 'ok',
                httpStatus: 200,
            );
        } catch (Throwable) {
            return $this->response(
                status: 'unavailable',
                database: 'unavailable',
                httpStatus: 503,
            );
        }
    }

    private function response(
        string $status,
        string $database,
        int $httpStatus,
    ): JsonResponse {
        return response()->json([
            'status' => $status,
            'database' => $database,
            'timestamp' => now()->utc()->toIso8601String(),
        ], $httpStatus, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
