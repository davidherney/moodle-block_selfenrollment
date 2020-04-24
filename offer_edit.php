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

require_once '../../config.php';
require_once 'offer_edit_form.php';
require_once 'locallib.php';
require_once 'tabs.php';

$systemcontext = context_system::instance();
require_login();
require_capability('block/selfenrollment:manage', $systemcontext);

$id   = optional_param('id', 0, PARAM_INT);

if ($id) {
    $offer = $DB->get_record('block_selfenrollment_offers', array('id'=>$id));
}
else {
    $offer = null;
}

$PAGE->set_url('/blocks/selfenrollment/offer_edit.php');
$PAGE->set_context($systemcontext);

$s_title = get_string('offer', 'block_selfenrollment');
$PAGE->set_title($s_title);
$PAGE->set_heading($s_title);
$PAGE->set_pagelayout('mydashboard');

$errormsg = '';
$statusmsg = '';

// First create the form.
$editform = new block_selfenrollment_offer_edit_form(NULL, array('data'=>$offer));
if ($editform->is_cancelled()) {
    $url = new moodle_url($CFG->wwwroot . '/blocks/selfenrollment/manage.php');
    redirect($url);
}
else if ($data = $editform->get_data()) {

    if (!$offer) {
        $offer = new stdClass();
    }

    $offer->type = $data->type;
    $offer->name = $data->name;
    $offer->modality = $data->modality;
    $offer->intensity = $data->intensity;
    $offer->cost = $data->cost;
    $offer->currency = !empty($data->currency) ? $data->currency : NULL;
    $offer->active = $data->active;

    if (is_array($data->description)) {
        $offer->description = $data->description['text'];
    }

    if (is_array($data->observations)) {
        $offer->observations = $data->observations['text'];
    }

    if (is_array($data->emailtext)) {
        $offer->emailtext = $data->emailtext['text'];
    }

    if (!empty($data->id)) {
        $DB->update_record('block_selfenrollment_offers', $offer);

        require_once 'classes/event/offer_updated.php';
        $event = \block_selfenrollment\event\offer_updated::create(array(
            'objectid' => $offer->id,
            'context' => $PAGE->context,
        ));
        $event->trigger();
    }
    else {
        $id = $DB->insert_record('block_selfenrollment_offers', $offer, true);

        require_once 'classes/event/offer_created.php';
        $event = \block_selfenrollment\event\offer_created::create(array(
            'objectid' => $id,
            'context' => $PAGE->context,
        ));
        $event->trigger();

        $url = new moodle_url($CFG->wwwroot . '/blocks/selfenrollment/cohortoffer.php', array('offerid' => $id));
        redirect($url);
    }

    $statusmsg = get_string('changessaved');
}

echo $OUTPUT->header();

echo $OUTPUT->heading($s_title);

block_selfenrollment_printtabs('general', $id);

if ($errormsg !== '') {
    echo $OUTPUT->notification($errormsg);
}
else if ($statusmsg !== '') {
    echo $OUTPUT->notification($statusmsg, 'notifysuccess');
}

$editform->display();

echo $OUTPUT->footer();
