<?php

declare(strict_types=1);


namespace App\Services\PDF;

use Dompdf\Dompdf;
use Jbtronics\DompdfFontLoaderBundle\Services\DompdfFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: DompdfFactoryInterface::class)]
class DompdfFactory implements DompdfFactoryInterface
{
    public function __construct(private readonly string $fontDirectory, private readonly string $tmpDirectory)
    {
        //Create folder if it does not exist
        $this->createDirectoryIfNotExisting($this->fontDirectory);
        $this->createDirectoryIfNotExisting($this->tmpDirectory);
    }

    private function createDirectoryIfNotExisting(string $path): void
    {
        if (!is_dir($path) && (!mkdir($concurrentDirectory = $path, 0777, true) && !is_dir($concurrentDirectory))) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    public function create(): Dompdf
    {
        return new Dompdf([
            'fontDir' => $this->fontDirectory,
            'fontCache' => $this->fontDirectory,
            'tempDir' => $this->tmpDirectory,
        ]);
    }
}