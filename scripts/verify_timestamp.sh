#!/usr/bin/env bash

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

# Little tool to verify a timestamp of a single file
# First argument is file, second argument is tsr file

CHAIN_FILE=/tmp/dfn_verify_chain.txt
CHAIN_URL=https://pki.pca.dfn.de/dfn-ca-global-g2/pub/cacert/chain.txt

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

INPUT_FILE=$1
if [ ! -z "$2" ]; then
  TSR_FILE=$2
else
  TSR_FILE="$1.tsr"
  echo "No file TSR file specified. Using $TSR_FILE";
fi

if [ ! -f "$TSR_FILE" ]; then
  echo "TSR $TSR_FILE not existing! Aborting..."
  exit 1
fi

if [ ! -f $CHAIN_FILE ]; then
  wget $CHAIN_URL -O $CHAIN_FILE
fi

printf "${RED}===== TSR info ====${NC}\n\n"
openssl ts -reply -in "$TSR_FILE" -text
printf "\n\n${RED}===== Verification status ====${NC}\n"
openssl ts -verify -in "$TSR_FILE" -data "$INPUT_FILE" -CAfile "$CHAIN_FILE"