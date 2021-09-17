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

function ready(fn) {
   if (document.readyState != 'loading'){
      fn();
   } else {
      document.addEventListener('DOMContentLoaded', fn);
   }
}

ready(function() {
   var elements = document.querySelectorAll('*[data-row-class]');
   Array.prototype.forEach.call(elements, function(el, i) {
      el.closest('tr').classList.add(el.dataset.rowClass);
      el.closest('table').classList.add('table-hover');
   });
});