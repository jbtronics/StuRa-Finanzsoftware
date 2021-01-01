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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * Use a custom namer to create file names which are useful when viewing the directory structure.
 * It contains the department, current date and the project name all in slugged form.
 * @package App\Services\Upload
 */
class PaymentOrderFileNamer implements NamerInterface
{

    public function name($object, PropertyMapping $mapping): string
    {
        if (!$object instanceof PaymentOrder) {
            throw new \InvalidArgumentException('$object must be an PaymentOrder!');
        }

        $file = $mapping->getFile($object);
        if($file instanceof UploadedFile) {
            $originalName = $file->getClientOriginalName();
        } else {
            throw new \InvalidArgumentException('$file must be an UploadedFile instance!');
        }
        $originalExtension = \strtolower(\pathinfo($originalName, PATHINFO_EXTENSION));
        $originalBasename = \pathinfo($originalName, PATHINFO_FILENAME);

        $slugger = new AsciiSlugger();

        $filename = mb_strimwidth($slugger->slug($object->getDepartment()->getName() ?? 'unknown'),0, 16);

        $filename .= '_' . mb_strimwidth($slugger->slug($object->getProjectName()), 0, 16);

        $filename .= '_' . date('ymd-His');


        $filename .= '_' . bin2hex(random_bytes(5));


        //Add original extension
        $filename .= '.' . $originalExtension;

        return $filename;
    }


}