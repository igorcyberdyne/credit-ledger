<?php

namespace App\Tools;

final class DateFormatter
{
    public static function toApi(\DateTimeImmutable|string|null $date): ?string
    {
        if (is_string($date)) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (\Exception) {
                return null;
            }
        }

        return $date
            ?->setTimezone(new \DateTimeZone('UTC'))
            ->format(\DateTimeInterface::ATOM);
    }

    public static function fromApi(?string $date): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($date);
        } catch (\Exception) {
            return null;
        }
    }
}
