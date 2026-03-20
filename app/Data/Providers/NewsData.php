<?php

namespace App\Data\Providers;

final readonly class NewsData
{
    /**
     * @param  list<string>  $topics
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $providerSlug,
        public string $title,
        public ?string $externalReference = null,
        public ?string $summary = null,
        public ?string $url = null,
        public ?string $publishedAt = null,
        public array $topics = [],
        public array $meta = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider_slug' => $this->providerSlug,
            'title' => $this->title,
            'external_reference' => $this->externalReference,
            'summary' => $this->summary,
            'url' => $this->url,
            'published_at' => $this->publishedAt,
            'topics' => $this->topics,
            'meta' => $this->meta,
        ];
    }
}
