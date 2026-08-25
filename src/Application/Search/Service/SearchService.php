<?php

namespace App\Application\Search\Service;

use App\Domain\Search\Entity\SearchModel;
use App\Domain\Search\Service\SearchServiceInterface;
use App\Infrastructure\Search\Client\YoutubeDataApiClient;

class SearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly YoutubeDataApiClient $youtubeDataApiClient,
    ) {
    }

    public function search(string $searchStr): string
    {
        $searchStr = trim($searchStr);

        if ($searchStr === '') {
            return json_encode([], JSON_THROW_ON_ERROR);
        }

        $data = $this->youtubeDataApiClient->searchVideos($searchStr);
        $results = [];

        foreach ($data['items'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $videoId = $item['id']['videoId'] ?? null;

            if (!is_string($videoId) || $videoId === '') {
                continue;
            }

            $snippet = $item['snippet'] ?? [];
            $thumbnails = is_array($snippet['thumbnails'] ?? null) ? $snippet['thumbnails'] : [];
            $thumbnailUrl = $thumbnails['medium']['url']
                ?? $thumbnails['default']['url']
                ?? '';

            $results[] = new SearchModel(
                videoId: $videoId,
                title: (string) ($snippet['title'] ?? ''),
                description: (string) ($snippet['description'] ?? ''),
                thumbnailUrl: (string) $thumbnailUrl,
                channelTitle: (string) ($snippet['channelTitle'] ?? ''),
                publishedAt: (string) ($snippet['publishedAt'] ?? ''),
            );
        }

        return json_encode(
            array_map(static fn (SearchModel $model): array => $model->jsonSerialize(), $results),
            JSON_THROW_ON_ERROR,
        );
    }
}
