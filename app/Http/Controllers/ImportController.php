<?php

namespace App\Http\Controllers;

use App\Services\RandomUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __invoke(Request $request, RandomUserService $service): JsonResponse
    {
        $version = $request->input('version', '1.4');

        try {
            $result = $service->fetchAndImport(50, $version);
            return response()->json([
                'success' => true,
                'message' => "Import complete: {$result['imported']} new, {$result['updated']} updated.",
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            $isEmptyResults = str_contains($e->getMessage(), 'returned no users');
            return response()->json([
                'success'       => false,
                'empty_results' => $isEmptyResults,
                'message'       => $e->getMessage(),
            ], 500);
        }
    }
}
