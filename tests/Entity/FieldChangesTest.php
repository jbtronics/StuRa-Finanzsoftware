<?php

namespace App\Tests\Entity;

use App\Entity\FieldChanges;
use PHPUnit\Framework\TestCase;

class FieldChangesTest extends TestCase
{

    public function testNew(): void
    {
        $fieldChanges = FieldChanges::new();
        $this->assertNull($fieldChanges->getMostRecentChangedField());
        $this->assertNull($fieldChanges->getMostRecentChangeDate());
        $this->assertNull($fieldChanges->getMostRecentChangeUser());
    }

    public function testChangeField(): void
    {
        $fieldChanges = FieldChanges::new();
        $fieldChanges->changeField('test', 'user', new \DateTimeImmutable('2021-01-01'));
        $this->assertEquals('test', $fieldChanges->getMostRecentChangedField());
        $this->assertEquals('user', $fieldChanges->getMostRecentChangeUser());
        $this->assertEquals(new \DateTimeImmutable('2021-01-01'), $fieldChanges->getMostRecentChangeDate());

        //It should also work without a date
        $fieldChanges = FieldChanges::new();
        $fieldChanges->changeField('test2', 'user2', null);
        $this->assertEquals('test2', $fieldChanges->getMostRecentChangedField());
        $this->assertEquals('user2', $fieldChanges->getMostRecentChangeUser());
        $this->assertNotNull($fieldChanges->getMostRecentChangeDate());
    }

    public function testGetMostRecentChangeForField(): void
    {
        $fieldChanges = FieldChanges::new();
        $fieldChanges->changeField('test', 'user', new \DateTimeImmutable('2021-01-01'));

        $this->assertEquals([
            'date' => new \DateTimeImmutable('2021-01-01'),
            'user' => 'user'
        ], $fieldChanges->getMostRecentChangeForField('test'));
    }

    public function testSerializeToJSONArray(): void
    {
        $fieldChanges = FieldChanges::new();
        $fieldChanges->changeField('test', 'user', new \DateTimeImmutable('2021-01-01'));
        $fieldChanges->changeField('test2', 'user2', new \DateTimeImmutable('2022-01-01'));

        $this->assertEquals([
            '$$' => ['v' => 1],
            'f' => [
                'test' => [
                    'd' => '2021-01-01T00:00:00+00:00',
                    'u' => 'user'
                ],
                'test2' => [
                    'd' => '2022-01-01T00:00:00+00:00',
                    'u' => 'user2'
                ]
            ],
            'l' => 'test2'
        ], $fieldChanges->serializeToJSONArray());
    }

    public function testDeserializeFromJSONArrayEmpty(): void
    {
        //For an null or empty array, the object should be empty
        $fieldChanges = FieldChanges::deserializeFromJSONArray([]);
        $this->assertNull($fieldChanges->getMostRecentChangedField());
        $this->assertNull($fieldChanges->getMostRecentChangeDate());
        $this->assertNull($fieldChanges->getMostRecentChangeUser());

        $fieldChanges = FieldChanges::deserializeFromJSONArray(null);
        $this->assertNull($fieldChanges->getMostRecentChangedField());
        $this->assertNull($fieldChanges->getMostRecentChangeDate());
        $this->assertNull($fieldChanges->getMostRecentChangeUser());

    }

    public function testDeserializeFromJSONArray(): void
    {
        $data = [
            '$$' => ['v' => 1],
            'f' => [
                'test' => [
                    'd' => '2021-01-01T00:00:00+00:00',
                    'u' => 'user'
                ],
                'test2' => [
                    'd' => '2022-01-01T00:00:00+00:00',
                    'u' => 'user2'
                ]
            ],
            'l' => 'test2'
        ];

        $fieldChanges = FieldChanges::deserializeFromJSONArray($data);

        $this->assertEquals('test2', $fieldChanges->getMostRecentChangedField());
        $this->assertEquals('user2', $fieldChanges->getMostRecentChangeUser());
        $this->assertEquals(new \DateTimeImmutable('2022-01-01'), $fieldChanges->getMostRecentChangeDate());

        $this->assertEquals([
            'date' => new \DateTimeImmutable('2021-01-01'),
            'user' => 'user'
        ], $fieldChanges->getMostRecentChangeForField('test'));

        $this->assertEquals([
            'date' => new \DateTimeImmutable('2022-01-01'),
            'user' => 'user2'
        ], $fieldChanges->getMostRecentChangeForField('test2'));
    }

}
