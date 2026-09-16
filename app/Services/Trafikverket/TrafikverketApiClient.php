<?php

namespace App\Services\Trafikverket;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TrafikverketApiClient
{
    public function isConfigured(): bool
    {
        return filled(config('trafikverket.api_key'));
    }

    /**
     * @return array<string, mixed>
     */
    public function postQuery(string $xmlBody): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = Http::timeout(20)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=UTF-8',
                'Accept' => 'application/json',
            ])
            ->withBody($xmlBody, 'text/xml')
            ->post((string) config('trafikverket.endpoint'));

        if (! $response->successful()) {
            $snippet = Str::limit(trim($response->body()), 200);

            throw new RuntimeException(
                'Trafikverket API svarade med HTTP '.$response->status()
                .($snippet !== '' ? ': '.$snippet : '.')
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            $snippet = Str::limit(trim($response->body()), 200);

            throw new RuntimeException(
                'Trafikverket API returnerade ogiltigt JSON'
                .($snippet !== '' ? ': '.$snippet : '.')
            );
        }

        $apiError = $this->extractApiErrorMessage($payload);

        if ($apiError !== null) {
            throw new RuntimeException('Trafikverket API: '.$apiError);
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function extractObjects(array $payload, string $objectType): array
    {
        $results = data_get($payload, 'RESPONSE.RESULT', []);

        if (! is_array($results)) {
            return [];
        }

        $objects = [];

        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }

            $chunk = $result[$objectType] ?? null;

            if ($chunk === null) {
                continue;
            }

            if (array_is_list($chunk)) {
                foreach ($chunk as $item) {
                    if (is_array($item)) {
                        $objects[] = $item;
                    }
                }

                continue;
            }

            if (is_array($chunk)) {
                $objects[] = $chunk;
            }
        }

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractApiErrorMessage(array $payload): ?string
    {
        $topLevel = data_get($payload, 'RESPONSE.ERROR');

        if (is_array($topLevel)) {
            return $this->formatErrorNode($topLevel);
        }

        $results = data_get($payload, 'RESPONSE.RESULT', []);

        if (! is_array($results)) {
            return null;
        }

        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }

            $error = $result['ERROR'] ?? null;

            if (! is_array($error)) {
                continue;
            }

            $message = $this->formatErrorNode($error);

            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $error
     */
    private function formatErrorNode(array $error): ?string
    {
        $message = $error['MESSAGE'] ?? $error['Message'] ?? null;
        $source = $error['SOURCE'] ?? $error['Source'] ?? null;

        if (! is_string($message) || trim($message) === '') {
            return null;
        }

        $message = trim($message);

        if (is_string($source) && trim($source) !== '') {
            return trim($source).': '.$message;
        }

        return $message;
    }
}
