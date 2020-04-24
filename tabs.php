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


function block_selfenrollment_printtabs ($current, $id = 0) {
    global $OUTPUT;

    $tabs = array();
    $inactive_tabs = array();


    $url = new moodle_url('/blocks/selfenrollment/offer_edit.php', array('id' => $id));
    $new_tab = new tabobject("tab_general", $url, get_string('offer_tab_general', 'block_selfenrollment'));
    $tabs[] = $new_tab;

    $url = new moodle_url('/blocks/selfenrollment/cohortoffer.php', array('offerid' => $id));
    $new_tab = new tabobject("tab_cohortoffer", $url, get_string('offer_tab_cohort', 'block_selfenrollment'));
    $tabs[] = $new_tab;

    if (empty($id)) {
        $inactive_tabs[] = 'tab_cohortoffer';
    }

    echo $OUTPUT->tabtree($tabs, "tab_" . $current, $inactive_tabs);
}
