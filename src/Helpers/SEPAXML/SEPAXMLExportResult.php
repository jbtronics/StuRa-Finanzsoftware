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

namespace App\Helpers\SEPAXML;

use App\Entity\SEPAExport;
use App\Helpers\ZIPBinaryFileResponseFacade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Uid\Ulid;

final class SEPAXMLExportResult implements \Countable
{
    /** @var SEPAExport[] */
    private $sepa_exports;

    /** @var Ulid */
    private $group_ulid;

    /**
     * @param  SEPAExport[]  $sepa_exports
     */
    public function __construct(array $sepa_exports)
    {
        if (count($sepa_exports) === 0) {
            throw new \InvalidArgumentException('$sepa_exports must not be empty!');
        }

        //Perform some checks on the sepa exports
        foreach($sepa_exports as $sepa_export) {
            if (!$sepa_export instanceof SEPAExport) {
                throw new \InvalidArgumentException('$sepa_exports must all be of type SEPAExport!');
            }
            if ($sepa_export->getXmlFile() === null) {
                throw new \InvalidArgumentException('Every Export in $sepa_exports must have an associated XML file!');
            }
        }

        $this->sepa_exports = $sepa_exports;
        $this->group_ulid = new Ulid();

        //Apply group ulid to all SEPA exports
        foreach ($this->sepa_exports as $export) {
            $export->setGroupUlid($this->group_ulid);
        }
    }

    public function getGroupUlid(): Ulid
    {
        return $this->group_ulid;
    }

    /**
     * Returns the number of SEPAExports contained
     * @return int|void
     */
    public function count()
    {
        return count($this->sepa_exports);
    }


    /**
     * Returns the SEPA Exports contained in this result
     * @return SEPAExport[]
     */
    public function getSEPAExports(): array
    {
        return $this->sepa_exports;
    }

    /**
     * Returns an array of all XML files contained in this result.
     * The array keys contains suggested filenames for the files
     * @return \SplFileInfo[]
     */
    public function getXMLFiles(): array
    {
        $result = [];

        foreach ($this->sepa_exports as $sepa_export) {
            $filename = $this->generateFilename($sepa_export);
            //Ensure that filename is not existing yet (add an increment suffix)
            $i = 2;
            while (isset($result[$filename])) {
                $filename = basename($filename) . '_' . sprintf("%d", $i++) . '.xml';
            }
            $result[$filename] = $sepa_export->getXmlFile();
        }

        return $result;
    }

    /**
     * Returns an array of the contained XML files as strings.
     * The array keys contains suggested filenames for the files.
     * @return string[]
     */
    public function getXMLString(): array
    {
        $result = [];
        $files = $this->getXMLFiles();

        foreach ($files as $filename => $file) {
            $result[$filename] = file_get_contents($file->getPathname());
        }

        return $result;
    }

    /**
     * Generates a download response for the contained XML files. If only a single XML is contained, it will be downloaded
     * as XML file, otherwise as ZIP containing all files.
     * @param  string  $disposition_filename The filename that should be used (without extension)
     * @param  bool  $force_zip
     * @return BinaryFileResponse
     */
    public function getDownloadResponse(string $disposition_filename, bool $force_zip = false): BinaryFileResponse
    {
        //If we have only one file, we can just return the file
        if ($force_zip === false && $this->count() === 1) {
            $response = new BinaryFileResponse($this->sepa_exports[0]->getXmlFile());
            $response->setPrivate();
            $response->headers->set('Content-Type', 'application/xml');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $disposition_filename . '.xml');
        } else { //Otherwise we download the file as ZIP
            $response = ZIPBinaryFileResponseFacade::createZIPResponseFromFiles($this->getXMLFiles(), $disposition_filename . '.zip');
            $response->headers->set('Content-Type', 'application/zip');
        }

        return $response;
    }

    /**
     * Persist all contained SEPAExport entities using the given EntityManager.
     * @param  EntityManagerInterface  $entityManager
     * @return void
     */
    public function persistSEPAExports(EntityManagerInterface $entityManager): void
    {
        foreach($this->sepa_exports as $sepa_export) {
            $entityManager->persist($sepa_export);
        }
    }

    private function generateFilename(SEPAExport $export): string
    {
        return $export->getDescription() . '_' . $export->getCreationDate()->format('Y-m-d-His') . '.xml';
    }
}