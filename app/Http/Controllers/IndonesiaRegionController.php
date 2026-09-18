<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IndonesiaRegionController extends Controller
{
    public function __invoke(string $level, ?string $code = null): JsonResponse
    {
        $path = match ($level) {
            'provinces' => 'provinces.json',
            'regencies' => $code ? "regencies/{$code}.json" : null,
            'districts' => $code ? "districts/{$code}.json" : null,
            default => null,
        };

        abort_if($path === null, 404);

        try {
            $data = Cache::remember("indonesia-regions:{$level}:{$code}", now()->addDay(), function () use ($path): array {
                return Http::acceptJson()
                    ->timeout(10)
                    ->get("https://wilayah.id/api/{$path}")
                    ->throw()
                    ->json('data', []);
            });
        } catch (\Throwable) {
            return response()->json(['message' => 'Data wilayah sedang tidak tersedia.'], 503);
        }

        return response()->json(['data' => $data]);
    }
}
