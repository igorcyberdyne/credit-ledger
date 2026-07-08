<?php

namespace App\Helper;

final class MoneyTransformer
{
    public static function toApi(int $cents): string
    {
        return number_format(
            $cents / 100,
            2,
            '.',
            ''
        );
    }

    public static function fromApi(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
