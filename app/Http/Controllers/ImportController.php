<?php

namespace App\Http\Controllers;

use App\Services\RandomUserService;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function __invoke(RandomUserService $service): JsonResponse
    {
        try {
            $result = $service->fetchAndImport(50);
            return response()->json([
                'success' => true,
                'message' => "Import complete: {$result['imported']} new, {$result['updated']} updated.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
