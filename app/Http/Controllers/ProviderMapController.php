<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ProviderMapController extends Controller
{
    public function geocode(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q'));
        abort_if($query === '' || mb_strlen($query) > 255, 422, 'Query lokasi tidak valid.');

        $result = Cache::remember('provider-map-geocode:'.sha1(mb_strtolower($query)), now()->addDay(), function () use ($query): ?array {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => 'Jadwalkan/1.0'])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'id',
                    'q' => $query,
                ]);

            if (! $response->successful() || ! is_array($response->json()) || ! isset($response->json()[0]['lat'], $response->json()[0]['lon'])) {
                return null;
            }

            return [
                'latitude' => (float) $response->json()[0]['lat'],
                'longitude' => (float) $response->json()[0]['lon'],
            ];
        });

        return response()->json($result ?? []);
    }
}
