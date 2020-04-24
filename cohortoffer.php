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
require_once 'lib.php';
require_once 'tabs.php';
require_once 'classes/event/offer_updated.php';

$systemcontext = context_system::instance();
require_login();
require_capability('block/selfenrollment:manage', $systemcontext);

$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$sort         = optional_param('sort', 'timestart', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);

$operation    = optional_param('op', null, PARAM_TEXT);

$offerid   = required_param('offerid', PARAM_INT);
$offer = $DB->get_record('block_selfenrollment_offers', array('id'=>$offerid));

$PAGE->set_url('/blocks/selfenrollment/cohortoffer.php');
$PAGE->set_context($systemcontext);

$s_title = get_string('cohortoffer', 'block_selfenrollment', $offer);
$PAGE->set_title($s_title);
$PAGE->set_heading($s_title);
$PAGE->set_pagelayout('mydashboard');

echo $OUTPUT->header();

// Delete a offer cohort, after confirmation
if ($delete && confirm_sesskey()) {

    $cohort = $DB->get_record('block_selfenrollment_cohort', array('id'=>$delete), '*', MUST_EXIST);

    if ($confirm != md5($delete)) {
        $returnurl = new moodle_url('/blocks/selfenrollment/cohortoffer.php', array('sort' => $sort, 'dir' => $dir, 'offerid'=>$offerid));
        echo $OUTPUT->heading(get_string('cohortoffer_delete', 'block_selfenrollment'));
        $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
        echo $OUTPUT->confirm(get_string('deletecheck', '', ""), new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    }
    else if (data_submitted()) {
        $DB->delete_records('block_selfenrollment_cohort', array('id'=>$cohort->id));
        $operation = 'del';

        $event = \block_selfenrollment\event\offer_updated::create(array(
            'objectid' => $offer->id,
            'context' => $PAGE->context,
            'other' => array('element' => 'cohortoffer', 'elementid' => $cohort->id, 'action' => 'delete')
        ));
        $event->trigger();
    }
}

if ($operation) {
    $statusmsg = $operation == 'add' ? get_string('cohortoffer_add', 'block_selfenrollment') : ($operation == 'del' ? get_string('cohortoffer_deleted', 'block_selfenrollment') : get_string('changessaved'));
    echo $OUTPUT->notification($statusmsg, 'notifysuccess');
}

$offercohorts = $DB->get_records('block_selfenrollment_cohort', array('offerid'=>$offerid), $sort . ' ' . $dir);

echo $OUTPUT->heading($s_title);

block_selfenrollment_printtabs('cohortoffer', $offerid);

$table = new html_table();
$table->attributes['class'] = 'admintable generaltable';
$table->cellspacing = 0;

$table->head = array();

$columns = array();
$columns['cohort'] = get_string('cohort', 'cohort');
$columns['timestart'] = get_string('timestart', 'block_selfenrollment');
$columns['timeend'] = get_string('timeend', 'block_selfenrollment');

foreach ($columns as $ckey => $column) {
    if ($sort != $ckey) {
        $columnicon = "";
        $columndir = "ASC";
    }
    else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, $dir);

    }
    $url = new moodle_url('/blocks/selfenrollment/cohortoffer.php', array('sort' => $ckey, 'dir' => $columndir, 'offerid'=>$offerid));
    $table->head[] = html_writer::link($url, $column) . $columnicon;
}

//Operations column
$table->head[] = '';

if($offercohorts) {

    $userformatdatetime = get_string('strftimedatetimeshort');
    $cohorts = $DB->get_records('cohort', null, 'name');
    foreach($offercohorts as $cohort){

        $data = array ();
        if (!isset($cohorts[$cohort->cohortid])) {
            $data[] = get_string('cohort_na', 'block_selfenrollment');
        }
        else {
            $data[] = $cohorts[$cohort->cohortid]->name . ' (' . $cohorts[$cohort->cohortid]->idnumber . ')';
        }
        $data[] = userdate($cohort->timestart, $userformatdatetime);
        $data[] = userdate($cohort->timeend, $userformatdatetime);

        $edit_url = new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/cohortoffer_edit.php', array('id'=> $cohort->id, 'offerid'=>$offerid));
        $operations = html_writer::tag('a', get_string('edit'), array('href'=>$edit_url));

        $delete_url = new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/cohortoffer.php', array('delete'=> $cohort->id, 'sesskey'=>sesskey(), 'offerid'=>$offerid));
        $operations .= html_writer::empty_tag('br') . html_writer::tag('a', get_string('delete'), array('href'=>$delete_url));

        $data[] = $operations;

        $table->data[] = $data;
    }

}

echo html_writer::table($table);

echo $OUTPUT->container_start('buttons');

echo $OUTPUT->action_link(new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/cohortoffer_edit.php', array('offerid'=>$offerid)), get_string('add'), null, array('class'=>'btn'));
echo $OUTPUT->action_link(new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/manage.php'), get_string('back'), null, array('class'=>'btn'));

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
