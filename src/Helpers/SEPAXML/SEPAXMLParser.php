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

class SEPAXMLParser
{
    /**
     * Parse various infos needed for SEPA Export from the given string and return info as array
     * @param  string  $xml
     * @return array
     */
    public static function parseFromString(string $xml): array
    {
        $root = new \SimpleXMLElement($xml);

        $group_header = $root->CstmrCdtTrfInitn->GrpHdr;
        $payment_info = $root->CstmrCdtTrfInitn->PmtInf;

        return [
            'msg_id' => (string) ($group_header->MsgId),
            'number_of_payments' => (int) ($group_header->NbOfTxs),
            'total_sum' => (int) str_replace('.','',$group_header->CtrlSum),
            'initiator_iban' => (string) $payment_info->DbtrAcct->Id->IBAN,
            'initiator_bic' => (string) $payment_info->DbtrAgt->FinInstnId->BIC,
        ];

    }

    /**
     * Parse various infos needed for SEPA Export from the given file and return info as array
     * @param  string  $xml
     * @return array
     */
    public static function parseFromFile(\SplFileInfo $file): array
    {
        $xmlstring = file_get_contents($file->getPathname());

        return self::parseFromString($xmlstring);
    }
}