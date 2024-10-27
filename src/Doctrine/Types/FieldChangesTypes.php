<?php

declare(strict_types=1);


namespace App\Doctrine\Types;

use App\Entity\FieldChanges;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * A class to allow Doctrine to store FieldChanges objects in the database
 */
class FieldChangesTypes extends JsonType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof FieldChanges) {
            throw new \InvalidArgumentException('This type can only be used for FieldChanges objects');
        }

        return parent::convertToDatabaseValue($value->serializeToJSONArray(), $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        $json = parent::convertToPHPValue($value, $platform);

        if ($json === null) {
            return null;
        }

        return FieldChanges::deserializeFromJSONArray($json);
    }
}