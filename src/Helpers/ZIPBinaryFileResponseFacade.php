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

namespace App\Helpers;


use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;

class ZIPBinaryFileResponseFacade
{

    /**
     * Creates a ZIP file as Response from given data.
     * @param  array  $data The data as associative array of the form ['filename.txt' => content]
     * @param  string  $disposition_filename The name of the file that will be downloaded.
     * @return BinaryFileResponse
     */
    public static function createZIPResponseFromData(array $data, string $disposition_filename): BinaryFileResponse
    {
        $zip = new ZipArchive();
        $file_path = tempnam(sys_get_temp_dir(), 'stura');
        if (true === $zip->open($file_path, ZipArchive::CREATE)) {
            foreach ($data as $name => $content) {
                $zip->addFromString($name, $content);
            }
            $zip->close();
            $response = new BinaryFileResponse($file_path);
            //This line is important to delete the temp file afterwards
            $response->deleteFileAfterSend();
            $response->setPrivate();
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $disposition_filename);

            return $response;
        }

        throw new RuntimeException('Could the temporay ZIP archive.');
    }

    /**
     * Creates a ZIP file as Response from given data.
     * @param  array  $data The data as associative array of the form ['filename.txt' =>  '/path/on/dir']
     * @param  string  $disposition_filename The name of the file that will be downloaded.
     * @return BinaryFileResponse
     */
    public static function createZIPResponseFromFiles(array $data, string $disposition_filename): BinaryFileResponse
    {
        $zip = new ZipArchive();
        $file_path = tempnam(sys_get_temp_dir(), 'stura');
        if (true === $zip->open($file_path, ZipArchive::CREATE)) {
            foreach ($data as $name => $file) {
                $zip->addFile($file, $name);
            }
            $zip->close();
            $response = new BinaryFileResponse($file_path);
            //This line is important to delete the temp file afterwards
            $response->deleteFileAfterSend();
            $response->setPrivate();
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $disposition_filename);

            return $response;
        }

        throw new RuntimeException('Could the temporay ZIP archive.');
    }
}