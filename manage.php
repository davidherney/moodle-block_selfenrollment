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
require_once $CFG->dirroot . '/blocks/selfenrollment/lib_filters.php';

$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$sort         = optional_param('sort', 'id', PARAM_ALPHA);
$dir          = optional_param('dir', 'DESC', PARAM_ALPHA);
$page         = optional_param('spage', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page

$operation    = optional_param('op', null, PARAM_TEXT);

$systemcontext = context_system::instance();
require_login();
require_capability('block/selfenrollment:manage', $systemcontext);


$PAGE->set_url('/blocks/selfenrollment/manage.php');
$PAGE->set_context($systemcontext);

$s_title = get_string('manage_offers', 'block_selfenrollment');
$PAGE->set_title($s_title);
$PAGE->set_heading($s_title);
$PAGE->set_pagelayout('mydashboard');

echo $OUTPUT->header();

// Delete a offer, after confirmation
if ($delete && confirm_sesskey()) {
    $offer = $DB->get_record('block_selfenrollment_offers', array('id'=>$delete), '*', MUST_EXIST);

    if ($confirm != md5($delete)) {
        $returnurl = new moodle_url('/blocks/selfenrollment/manage.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));
        echo $OUTPUT->heading(get_string('offer_delete', 'block_selfenrollment'));
        $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
        echo $OUTPUT->confirm(get_string('deletecheck', '', "'{$offer->name}'"), new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    }
    else if (data_submitted()) {

        $DB->delete_records('block_selfenrollment_cohort', array('offerid'=>$offer->id));
        $DB->delete_records('block_selfenrollment_offers', array('id'=>$offer->id));
        $operation = 'del';

        require_once 'classes/event/offer_deleted.php';
        $event = \block_selfenrollment\event\offer_deleted::create(array(
            'objectid' => $offer->id,
            'context' => $PAGE->context,
        ));
        $event->add_record_snapshot('block_selfenrollment_offers', $offer);
        $event->trigger();
    }
}


if ($operation) {
    $statusmsg = $operation == 'add' ? get_string('offer_add', 'block_selfenrollment') : ($operation == 'del' ? get_string('offer_deleted', 'block_selfenrollment') : get_string('changessaved'));
    echo $OUTPUT->notification($statusmsg, 'notifysuccess');
}

// create the filter form
$ifiltering = new block_selfenrollment_offers_filtering();


list($extrasql, $params) = $ifiltering->get_sql_filter();

$offers = get_offers_listing(true, $sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql, $params);
$offerscount = get_offers_listing(false);
$offerssearchcount = get_offers_listing(false, '', '', 0, 0, '', '', '', $extrasql, $params);

if ($extrasql != '' && $offerscount) {
    $a = new stdClass();
    $a->count = $offerssearchcount;
    $a->total = $offerscount;
    echo $OUTPUT->heading(get_string('offers_count', 'block_selfenrollment', $a));
    $offerscount = $offerssearchcount;
}
else {
    echo $OUTPUT->heading(get_string('manage_offers', 'block_selfenrollment', $offerscount));
}

$pagingbar = new paging_bar($offerscount, $page, $perpage, "manage.php?sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;");
$pagingbar->pagevar = 'spage';
echo $OUTPUT->render($pagingbar);

// add filters
$ifiltering->display_add();
$ifiltering->display_active();

$table = new html_table();
$table->attributes['class'] = 'admintable generaltable';
$table->cellspacing = 0;

$table->head = array();

$columns = array();
$columns['name'] = get_string('name');
$columns['type'] = get_string('offer_type', 'block_selfenrollment');
$columns['active'] = get_string('active');

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
    $url = new moodle_url('/blocks/selfenrollment/manage.php', array('sort' => $ckey, 'dir' => $columndir, 'perpage' => $perpage, 'page'=>$page));
    $table->head[] = html_writer::link($url, $column) . $columnicon;
}

//Operations column
$table->head[] = '';

if($offers) {

    foreach($offers as $offer){

        $data = array ();
        $data[] = $offer->name;
        $data[] = $offer->type;
        $data[] = $offer->active ? get_string('yes') : get_string('no');

        $menu = new action_menu();
        $menu->set_alignment(action_menu::TL, action_menu::TL);
        $menu->set_menu_trigger(get_string('edit'));

        $url = new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/offer_edit.php', array('id'=> $offer->id));
        $action = new action_link($url, get_string('edit'));
        $action->primary = false;
        $menu->add($action);

        $url = new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/manage.php', array('delete'=> $offer->id, 'sesskey'=>sesskey()));
        $action = new action_link($url, get_string('delete'));
        $action->primary = false;
        $menu->add($action);

        $data[] = $OUTPUT->render_action_menu($menu);

        $table->data[] = $data;
    }

}

echo html_writer::table($table);
echo $OUTPUT->render($pagingbar);

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/blocks/selfenrollment/offer_edit.php'), get_string('add'), 'get');
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
