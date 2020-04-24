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

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once('lib.php');

$id = optional_param('offerid', 0, PARAM_INT);

$res = null;
if ($id) {
    $offer = $DB->get_record('block_selfenrollment_offers', array('id'=>$id));
    if ($offer) {
        $res = $offer;
        $res->intensity = get_string('intensity_format', 'block_selfenrollment', $offer->intensity);
        $res->modality  = html_writer::tag('span', '', array('class'=>"fa modality-" . $offer->modality, 'title'=>get_string('modality_' . $offer->modality, 'block_selfenrollment'))) . html_writer::tag('span', get_string('modality_' . $offer->modality, 'block_selfenrollment'));
        $res->cost = $offer->cost <= 0 ? get_string('free', 'block_selfenrollment') : $offer->cost . ' ' . $offer->currency;
        $res->active = $offer->active ? get_string('yes') : get_string('no');
    }
}

echo json_encode($res);
