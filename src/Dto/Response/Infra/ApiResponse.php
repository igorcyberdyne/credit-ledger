<?php

namespace App\Dto\Response\Infra;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class ApiResponse
{
    public function __construct(
        public ?ApiSuccessResponse $apiSuccessResponse = null,
        #[SerializedName('error')]
        public ?ApiErrorResponse $apiErrorResponse = null,
    ) {
    }
}
