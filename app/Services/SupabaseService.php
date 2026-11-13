<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    private string $url;

    private string $apiKey;

    public function __construct()
    {
        $this->url = (string) config('supabase.url');
        $serviceRoleKey = (string) config('supabase.service_role_key');
        $this->apiKey = $serviceRoleKey !== '' ? $serviceRoleKey : (string) config('supabase.anon_key');
    }

    /**
     * Get a configured HTTP client for Supabase REST API.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->url, '/') . '/rest/v1')
            ->timeout(8)
            ->withHeaders([
                'apikey' => $this->apiKey,
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ]);
    }

    /**
     * Fetch records from a table.
     *
     * @param array<string, mixed> $query
     * @return array<int, array<string, mixed>>
     */
    public function from(string $table, array $query = []): array
    {
        $response = $this->client()->get('/' . $table, $query);

        if (!$response->successful()) {
            Log::warning("Supabase GET /{$table} failed ({$response->status()}): {$response->body()}");
            return [];
        }

        return (array) $response->json();
    }

    /**
     * Insert records into a table.
     *
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    public function insert(string $table, array $data): array
    {
        $response = $this->client()->post('/' . $table, $data);

        if (!$response->successful()) {
            Log::warning("Supabase POST /{$table} failed ({$response->status()}): {$response->body()}");
            return [];
        }

        return (array) $response->json();
    }

    /**
     * Update records matching query filters in a table.
     *
     * @param array<string, mixed> $matchQuery
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    public function update(string $table, array $matchQuery, array $data): array
    {
        $queryString = http_build_query($matchQuery);
        $uri = '/' . $table . ($queryString !== '' ? '?' . $queryString : '');
        $response = $this->client()->patch($uri, $data);

        if (!$response->successful()) {
            Log::warning("Supabase PATCH {$uri} failed ({$response->status()}): {$response->body()}");
            return [];
        }

        return (array) $response->json();
    }
}
