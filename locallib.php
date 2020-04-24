<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 */
function block_selfenrollment_offers_types_list () {
    global $CFG;

    $config = get_config('block_selfenrollment');

    $types = $config->offertypes;
    $types = explode("\n", $types);

    $list = array();
    foreach ($types as $type) {
        $type = trim($type);
        if (!empty($type)) {
            $list[$type] = $type;
        }
    }

    return $list;
}

