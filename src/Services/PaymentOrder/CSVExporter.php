<?php

declare(strict_types=1);


namespace App\Services\PaymentOrder;

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class CSVExporter
{
    public function __construct(
        private SerializerInterface $serializer,
    )
    {
    }

    /**
     * Export the given payment orders as CSV files
     * @param  array  $data
     * @return string
     */
    public function export(array $data): string
    {
        return $this->serializer->serialize($data, 'csv', [
            CSVEncoder::DELIMITER_KEY => ';', //For german excel compatibility
            CSVEncoder::OUTPUT_UTF8_BOM_KEY => true, // For excel compatibility
            'groups' => ['csv_export'],
        ]);
    }
}