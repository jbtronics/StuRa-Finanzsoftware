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

# This script create a timestamp for all uploads in upload directory

# Config timestamp server
TIMESTAMP_SERVER=https://zeitstempel.dfn.de


RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# This is important to iterate over files with a space in name
SAVEIFS=$IFS
IFS=$(echo -en "\n\b")

cd "$(dirname $0)/../";

# sign_file(source file, target file)
sign_file() {
  local REQ_FILE=$(mktemp)

  local SOURCE=$1
  local TARGET=$2

  openssl ts -query -data "$SOURCE" -no_nonce -sha512 -cert -out $REQ_FILE
  curl -H "Content-Type: application/timestamp-query" --data-binary "@$REQ_FILE" $TIMESTAMP_SERVER > $TARGET

  rm $REQ_FILE
}

counter=0

for f in $(find ./uploads/ -type f -not -name '*.tsr')
do
  if [ ! -f "$f.tsr" ]; then
      echo -e "${BLUE}Generating timestamp for $f ${NC}";
      sign_file "$f" "$f.tsr";
      echo -e "${BLUE}Done${NC}";

      ((counter++))
      echo "";
  fi
done

echo -e "${GREEN}Generated timestamps for $counter files!${NC}"

IFS=$SAVEIFS