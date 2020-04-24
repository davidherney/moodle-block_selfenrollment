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
require_once 'cohortoffer_edit_form.php';
require_once 'tabs.php';
require_once 'classes/event/offer_updated.php';

$systemcontext = context_system::instance();
require_login();
require_capability('block/selfenrollment:manage', $systemcontext);

$id         = optional_param('id', 0, PARAM_INT);
$offerid    = required_param('offerid', PARAM_INT);

$offer = $DB->get_record('block_selfenrollment_offers', array('id'=>$offerid));

if ($id) {
    $cohort = $DB->get_record('block_selfenrollment_cohort', array('id'=>$id));
}
else {
    $cohort = null;
}

$PAGE->set_url('/blocks/selfenrollment/cohortoffer_edit.php');
$PAGE->set_context($systemcontext);

$s_title = get_string('cohortoffer_title', 'block_selfenrollment', $offer);
$PAGE->set_title($s_title);
$PAGE->set_heading($s_title);
$PAGE->set_pagelayout('mydashboard');

$errormsg = '';
$statusmsg = '';

// First create the form.
$editform = new block_selfenrollment_cohortoffer_edit_form(NULL, array('data'=>$cohort, 'offerid'=>$offerid));
if ($editform->is_cancelled()) {
    $url = new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/cohortoffer.php', array('offerid'=>$offerid));
    redirect($url);
}
else if ($data = $editform->get_data()) {

    if (!$cohort) {
        $cohort = new stdClass();
        $cohort->offerid = $data->offerid;
    }

    $cohort->cohortid = $data->cohortid;
    $cohort->timestart = !empty($data->timestart) ? $data->timestart : null;
    $cohort->timeend   = !empty($data->timeend) ? $data->timeend : null;

    if (!empty($data->id)) {
        $DB->update_record('block_selfenrollment_cohort', $cohort);
        $event = \block_selfenrollment\event\offer_updated::create(array(
            'objectid' => $offer->id,
            'context' => $PAGE->context,
            'other' => array('element' => 'cohortoffer', 'elementid' => $cohort->id, 'action' => 'update')
        ));
        $event->trigger();
    }
    else {
        $cohort->id = $DB->insert_record('block_selfenrollment_cohort', $cohort, true);
        $event = \block_selfenrollment\event\offer_updated::create(array(
            'objectid' => $offer->id,
            'context' => $PAGE->context,
            'other' => array('element' => 'cohortoffer', 'elementid' => $cohort->id, 'action' => 'create')
        ));
        $event->trigger();
    }

    $url = new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/cohortoffer.php', array('op'=> !empty($data->id) ? 'edit' : 'add', 'offerid'=>$offerid));
    redirect($url);
}

echo $OUTPUT->header();

echo $OUTPUT->heading($s_title);

block_selfenrollment_printtabs('cohortoffer', $offerid);

if ($errormsg !== '') {
    echo $OUTPUT->notification($errormsg);

}
else if ($statusmsg !== '') {
    echo $OUTPUT->notification($statusmsg, 'notifysuccess');
}

$editform->display();

echo $OUTPUT->footer();
