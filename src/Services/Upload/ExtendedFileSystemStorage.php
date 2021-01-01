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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\FileSystemStorage;

/**
 * We need to extend the original filesystemstorage as we have stored our payment_order files privately and want to use
 * the normal interface to access them via URI...
 */
class ExtendedFileSystemStorage extends FileSystemStorage
{
    private $router;

    public function __construct(PropertyMappingFactory $factory, UrlGeneratorInterface $router)
    {
        parent::__construct($factory);
        $this->router = $router;
    }

    public function resolveUri($obj, ?string $fieldName = null, ?string $className = null): ?string
    {
        $tmp = parent::resolveUri($obj, $fieldName, $className);

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
}
