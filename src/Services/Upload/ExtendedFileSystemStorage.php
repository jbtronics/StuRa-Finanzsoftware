<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services\Upload;

use App\Entity\PaymentOrder;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\FileSystemStorage;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * We need to extend the original filesystemstorage as we have stored our payment_order files privately and want to use
 * the normal interface to access them via URI...
 */
#[AsDecorator(FileSystemStorage::class)]
class ExtendedFileSystemStorage implements StorageInterface
{
    private $router;

    public function __construct(private readonly StorageInterface $decorated, UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function resolveUri($obj, ?string $fieldName = null, ?string $className = null): ?string
    {
        $tmp = $this->decorated->resolveUri($obj, $fieldName, $className);

        if (null !== $tmp && $obj instanceof PaymentOrder) {
            if ('printed_form' === $fieldName || 'printed_form_file' === $fieldName) {
                return $this->router->generate('file_payment_order_form', [
                    'id' => $obj->getId(),
                ]);
            }

            if ('references' === $fieldName || 'references_file' === $fieldName) {
                return $this->router->generate('file_payment_order_references', [
                    'id' => $obj->getId(),
                ]);
            }
        }

        return $tmp;
    }

    public function upload(object $obj, PropertyMapping $mapping): void
    {
        $this->decorated->upload($obj, $mapping);
    }

    public function remove(object $obj, PropertyMapping $mapping): ?bool
    {
        return $this->decorated->remove($obj, $mapping);
    }

    public function resolvePath(
        object|array $obj,
        ?string $fieldName = null,
        ?string $className = null,
        ?bool $relative = false
    ): ?string {
        return $this->decorated->resolvePath($obj, $fieldName, $className, $relative);
    }

    public function resolveStream(object|array $obj, ?string $fieldName = null, ?string $className = null)
    {
        return $this->decorated->resolveStream($obj, $fieldName, $className);
    }
}
