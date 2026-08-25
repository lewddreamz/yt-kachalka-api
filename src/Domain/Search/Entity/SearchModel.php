<?php

namespace App\Domain\Search\Entity;

class SearchModel implements \JsonSerializable
{
    public function __construct(
        public readonly string $videoId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $thumbnailUrl,
        public readonly string $channelTitle,
        public readonly string $publishedAt,
    ) {
    }

    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'videoId' => $this->videoId,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnailUrl' => $this->thumbnailUrl,
            'channelTitle' => $this->channelTitle,
            'publishedAt' => $this->publishedAt,
        ];
    }
}
