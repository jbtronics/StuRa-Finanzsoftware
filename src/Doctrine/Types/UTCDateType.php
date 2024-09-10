<?php

declare(strict_types=1);


namespace App\Doctrine\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;

class UTCDateType extends DateType
{
    private static ?DateTimeZone $utc_timezone = null;

    /**
     * {@inheritdoc}
     *
     * @param T $value
     *
     * @return (T is null ? null : string)
     *
     * @template T
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (!self::$utc_timezone instanceof \DateTimeZone) {
            self::$utc_timezone = new DateTimeZone('UTC');
        }

        if ($value instanceof DateTime) {
            $value->setTimezone(self::$utc_timezone);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * {@inheritDoc}
     *
     * @param T $value
     *
     * @template T
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?DateTime
    {
        if (!self::$utc_timezone instanceof \DateTimeZone) {
            self::$utc_timezone = new DateTimeZone('UTC');
        }

        if (null === $value || $value instanceof DateTime) {
            return $value;
        }

        $converted = DateTime::createFromFormat(
            '!' . $platform->getDateFormatString(),
            $value,
            self::$utc_timezone
        );

        if (!$converted) {
            throw InvalidFormat::new(
                $value,
                static::class,
                $platform->getDateFormatString(),
            );
        }

        return $converted;
    }
}