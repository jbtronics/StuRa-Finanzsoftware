<?php

declare(strict_types=1);


namespace App\Entity;

use DateTimeInterface;

/**
 * This class represents the last recent changes of fields in an entity. It associates field names with their change date and the
 * editing person (via name). This allows to mark fields that were changed by the StuRa Finanzen team later on.
 */
class FieldChanges
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param  array<string, array{"date": \DateTimeImmutable, "user": string}>  $changedFields
     * @param  string|null  $lastChangedField
     */
    private function __construct(private array $changedFields, private string|null $lastChangedField)
    {
    }

    /**
     * Mark the given field as changed by the given user at the given date. If no date is given, the current date is used.
     * @param  string  $fieldName
     * @param  string  $user
     * @param  \DateTimeImmutable|null  $dateTime
     * @return void
     */
    public function changeField(string $fieldName, string $user, ?\DateTimeImmutable $dateTime = null): void
    {
        $this->changedFields[$fieldName] = [
            'date' => $dateTime ?? new \DateTimeImmutable(),
            'user' => $user
        ];
        $this->lastChangedField = $fieldName;
    }

    /**
     * Returns the most recent change for the given field
     * @param  string  $fieldName
     * @return array|null The most recent change for the given field, or null if the field was never changed
     * @phpstan-return array{"date": \DateTimeImmutable, "user": string}|null
     */
    public function getMostRecentChangeForField(string $fieldName): ?array
    {
        return $this->changedFields[$fieldName] ?? null;
    }

    /**
     * Returns the field that was most recently changed
     * @return string|null
     */
    public function getMostRecentChangedField(): ?string
    {
        return $this->lastChangedField;
    }

    /**
     * Check if the given field was ever changed
     * @param  string  $field
     * @return bool
     */
    public function wasChanged(string $field): bool
    {
        return isset($this->changedFields[$field]);
    }

    /**
     * Returns the date of the most recent change, or null if no field was ever changed
     * @return \DateTimeImmutable|null
     */
    public function getMostRecentChangeDate(): ?\DateTimeImmutable
    {
        if ($this->lastChangedField === null) {
            return null;
        }

        return $this->changedFields[$this->lastChangedField]['date'];
    }

    /**
     * Returns the user that most recently changed a field, or null if no field was ever changed
     * @return string|null
     */
    public function getMostRecentChangeUser(): ?string
    {
        if ($this->lastChangedField === null) {
            return null;
        }

        return $this->changedFields[$this->lastChangedField]['user'];
    }

    /**
     * Returns all fields that were changed, along with the date and user of the change
     * @return array[]
     * @phpstan-return array<string, array{"date": \DateTimeImmutable, "user": string}>
     */
    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    /**
     * Serialize the FieldChanges object to a JSON array, which can be easily stored in a database
     * @return array
     */
    public function serializeToJSONArray(): array
    {
        $tmp = [
            '$$' => [
                'v' => self::SCHEMA_VERSION
            ],
            'f' => [],
            'l' => $this->lastChangedField
        ];

        //Add field information
        foreach ($this->changedFields as $fieldName => $fieldData) {
            $tmp['f'][$fieldName] = [
                'd' => $fieldData['date']->format(DateTimeInterface::ATOM),
                'u' => $fieldData['user']
            ];
        }

        return $tmp;
    }

    /**
     * Creates a new (empty) FieldChanges object
     * @return self
     */
    public static function new(): self
    {
        return new self([], null);
    }

    /**
     * Deserializes a FieldChanges object from a JSON array
     * @param  array|null  $data
     * @return self
     * @throws \DateMalformedStringException
     */
    public static function deserializeFromJSONArray(?array $data): self
    {
        //If no data is given, return an empty FieldChanges object
        if ($data === null || $data === []) {
            return self::new();
        }

        //Ensure that the version is correct
        if ($data['$$']['v'] !== self::SCHEMA_VERSION) {
            throw new \InvalidArgumentException('The given data does not match the expected schema version');
        }

        return new self(
            array_map(
                static fn(array $fieldData) => [
                    'date' => new \DateTimeImmutable($fieldData['d']),
                    'user' => $fieldData['u']
                ],
                $data['f'] //Short for "fields"
            ),
            $data['l'], //Short for "lastField"
        );
    }
}