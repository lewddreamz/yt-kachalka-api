<?php

namespace App\Infrastructure\Search\Client;

use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\YouTube;

class YoutubeDataApiClient
{
    private readonly YouTube $youtube;

    public function __construct(
        string $apiKey,
    ) {
        $client = new Client();
        $client->setDeveloperKey($apiKey);

        $this->youtube = new YouTube($client);
    }

    /**
     * @return array<string, mixed>
     */
    public function searchVideos(string $query, int $maxResults = 25): array
    {
        try {
            $response = $this->youtube->search->listSearch('snippet', [
                'q' => $query,
                'type' => 'video',
                'maxResults' => $maxResults,
            ]);
        } catch (GoogleServiceException $e) {
            throw new \RuntimeException($e->getMessage(), previous: $e);
        }

        return json_decode(json_encode($response), true, flags: JSON_THROW_ON_ERROR);
    }
}
