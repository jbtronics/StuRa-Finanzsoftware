#!/usr/bin/env bash

#
# Copyright (C) 2020  Jan Böhmer
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published
# by the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#

# This script checks the integrity of all files in the uploads directory

CHAIN_FILE=/tmp/dfn_verify_chain.txt
CHAIN_URL=https://pki.pca.dfn.de/dfn-ca-global-g2/pub/cacert/chain.txt

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

cd "$(dirname $0)/../";

if [ ! -f $CHAIN_FILE ]; then
  wget $CHAIN_URL -O $CHAIN_FILE
fi

# This is important to iterate over files with a space in name
SAVEIFS=$IFS
IFS=$(echo -en "\n\b")

for f in $(find ./uploads/ -type f -name '*.tsr')
do
  TSR_FILE=$f
  INPUT_FILE=${f%".tsr"}
  echo "Checking Integrity of $INPUT_FILE"
  if ! openssl ts -verify -in "$TSR_FILE" -data "$INPUT_FILE" -CAfile "$CHAIN_FILE" 2> /dev/null; then
    echo ""
    echo -e "${RED} Verification of $INPUT_FILE failed! ${NC}" >&2
    echo ""
    exit 1;
  fi
done


IFS=$SAVEIFS