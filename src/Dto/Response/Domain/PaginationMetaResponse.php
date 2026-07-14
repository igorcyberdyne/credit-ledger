<?php

namespace App\Dto\Response\Domain;

use Symfony\Component\Serializer\Attribute\Ignore;

final class PaginationMetaResponse
{
    public string $nextUri = '';
    public string $previousUri = '';

    public function __construct(
        public int $page,
        public int $limit,
        public int $total,
        public int $pages,
        #[Ignore]
        public ?string $uri = null,
    ) {
        if (empty($this->uri)) {
            return;
        }

        $this->nextUri = $this->buildUri($this->page + 1);
        $this->previousUri = $this->buildUri($this->page - 1);
    }

    private function buildUri(int $page): string
    {
        if ($page < 1 || $page > $this->pages) {
            return '';
        }

        return sprintf('%s?page=%d&limit=%d', $this->uri, $page, $this->limit);
    }
}
