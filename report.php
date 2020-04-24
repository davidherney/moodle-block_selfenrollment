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
require_once($CFG->libdir.'/dataformatlib.php');

$enrol        = optional_param('enrol', 0, PARAM_INT);
$sort         = optional_param('sort', 'timeadded', PARAM_ALPHA);
$dir          = optional_param('dir', 'DESC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
$format       = optional_param('format', '', PARAM_ALPHA);

$operation    = optional_param('op', null, PARAM_TEXT);

$systemcontext = context_system::instance();
require_login();
require_capability('block/selfenrollment:manage', $systemcontext);

$PAGE->set_url('/blocks/selfenrollment/report.php');
$PAGE->set_context($systemcontext);

$s_title = get_string('enrolled_report', 'block_selfenrollment');
$PAGE->set_title($s_title);
$PAGE->set_heading($s_title);
$PAGE->set_pagelayout('mydashboard');

$baseurl = new moodle_url('/blocks/selfenrollment/report.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));

// create the user filter form
$filtering = new block_selfenrollment_report_filtering();

list($extrasql, $params, $bytype) = $filtering->get_sql_filter();

$stringcolumns = array();
if ($format) {
    $perpage = 0;

    $userfields = array('username'  => 'username',
                    'email'     => 'email',
                    'firstname' => 'firstname',
                    'lastname'  => 'lastname',
                    'idnumber'  => 'idnumber',
                    'institution' => 'institution',
                    'department' => 'department',
                    'phone1'    => 'phone1',
                    'phone2'    => 'phone2',
                    'city'      => 'city',
                    'url'       => 'url',
                    'icq'       => 'icqnumber',
                    'skype'     => 'skypeid',
                    'aim'       => 'aimid',
                    'yahoo'     => 'yahooid',
                    'msn'       => 'msnid',
                    'country'   => 'country');

} else {
    $userfields = array('username'  => 'username',
                    'firstname' => 'firstname',
                    'lastname'  => 'lastname',
                    'email'     => 'email',
                    'lastaccess'   => 'lastaccess');
}

$fields_string = '';
foreach($userfields as $key => $field) {
    $fields_string .= "u.$key,";
    $stringcolumns[$key] = get_string($field);
}

$stringcolumns['cohortname'] = get_string('cohort', 'cohort');
$stringcolumns['timeadded'] = get_string('date');
$stringcolumns['type'] = get_string('enroltype', 'block_selfenrollment');

$fields_string = rtrim($fields_string, ',');

$extrasql = $extrasql ? ' WHERE ' . $extrasql : '';

$sql = '';
$sqlcount = '';
if ($bytype === null || $bytype === true) {
    $sql = "(SELECT CONCAT(cl.id, '_s') AS id, cl.userid, cl.cohortid, cl.cohortname, cl.cohortidnumber, cl.timeadded, 'self' AS type, {$fields_string}
        FROM {block_selfenrollment_log} AS cl
        LEFT JOIN {user} AS u ON u.id = cl.userid
        " . $extrasql . ") ";

    $sqlcount = "(SELECT COUNT('x') AS counters
        FROM {block_selfenrollment_log} AS cl
        LEFT JOIN {user} AS u ON u.id = cl.userid
        " . $extrasql . ") ";
}

if ($bytype === null) {
    $sql .= " UNION ";
    $sqlcount .= " UNION ALL ";
}

if ($bytype === null || $bytype === false) {
    $paramsb = array();
    $extrasql2 = $extrasql;
    foreach($params as $key => $param) {
        $extrasql2 = str_replace($key, $key . 'b', $extrasql2);
        $paramsb[$key. 'b'] = $param;
    }
    $params += $paramsb;

    $extrasql2 .= ($extrasql2 ? ' AND ' : ' WHERE ') . " se.id IS NULL ";
    $extrasql2 = str_replace('cl.cohortidnumber', 'ch.idnumber', $extrasql2);

    $sql .= "(SELECT CONCAT(cl.id, '_m') AS id, cl.userid, cl.cohortid, ch.name AS cohortname, ch.idnumber as cohortidnumber, cl.timeadded, 'manual' AS type, {$fields_string}
        FROM {cohort_members} AS cl
        INNER JOIN {cohort} AS ch ON ch.id = cl.cohortid
        LEFT JOIN {block_selfenrollment_log} AS se ON se.cohortid = cl.cohortid AND se.userid = cl.userid
        LEFT JOIN {user} AS u ON u.id = cl.userid
        " . $extrasql2 . ") ";

    $sqlcount .= "(SELECT COUNT('x') AS counters
        FROM {cohort_members} AS cl
        INNER JOIN {cohort} AS ch ON ch.id = cl.cohortid
        LEFT JOIN {block_selfenrollment_log} AS se ON se.cohortid = cl.cohortid AND se.userid = cl.userid
        LEFT JOIN {user} AS u ON u.id = cl.userid
        " . $extrasql2 . ") ";
}

$sql .= " ORDER BY " . $sort . ' ' . $dir;
$enrolled = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

$sqlcount = "SELECT SUM(counters) FROM (" . $sqlcount . ") AS tmp";
$enrolledsearchcount = $DB->count_records_sql($sqlcount, $params);

$sql = "SELECT SUM(counters) FROM
    (
        (SELECT COUNT('x') AS counters
            FROM {cohort_members} AS cl
            LEFT JOIN {block_selfenrollment_log} AS se ON se.cohortid = cl.cohortid AND se.userid = cl.userid
            WHERE se.id IS NULL
        )
    UNION ALL
    (SELECT COUNT('x') AS counters FROM {block_selfenrollment_log})) AS tmp";

$enrolledcount = $DB->count_records_sql($sql);

$strftimedate = get_string('strftimedatetimeshort');

if ($enrolled) {

    // Only download data.
    if ($format) {
        $stringcolumns = array('userid' => get_string('userid', 'block_selfenrollment')) + $stringcolumns;
        $stringcolumns['cohortidnumber'] = get_string('idnumber', 'cohort');

        if ($extrafields = $DB->get_records('user_info_field')) {
            foreach ($extrafields as $n => $field) {
                $stringcolumns['profile_field_'.$field->shortname] = $field->name;
                require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            }
        }

        $data = array();

        foreach($enrolled as $row) {

            $datarow = new stdClass();
            foreach ($stringcolumns as $column => $columntext) {
                if (strpos($column, 'profile_field_') !== false) {
                    continue;
                }

                if (in_array($column, array('timeadded', 'lastaccess'))) {
                    $datarow->$column = userdate($row->$column, $strftimedate);
                } else {
                    $datarow->$column = $row->$column;
                }
            }

            $data[$row->id] = $datarow;
        }

        $filename = clean_filename(get_string('exportfilename', 'block_selfenrollment', date('YmdHi')));
        $downloadusers = new ArrayObject($data);
        $iterator = $downloadusers->getIterator();

        $countries = get_string_manager()->get_list_of_countries(false);

        download_as_dataformat($filename, $format, $stringcolumns, $iterator, function($row) use ($extrafields, $stringcolumns, $countries) {
            global $DB;

            foreach ($extrafields as $field) {
                $newfield = 'profile_field_'.$field->datatype;
                $formfield = new $newfield($field->id, $row->userid);
                $formfield->edit_load_user_data($row);
            }

            if (isset($countries[$row->country])) {
                $row->country = $countries[$row->country];
            }

            $row->type = get_string('enroltype_' . $row->type, 'block_selfenrollment');

            $filedata = array();
            foreach ($stringcolumns as $field => $unused) {
                if (property_exists($row, $field)) {
                    $filedata[$field] = $row->$field;
                } else {
                    $filedata[$field] = '';
                }
            }
            return $filedata;
        });

        die;
    }
    // End download data.
}

echo $OUTPUT->header();

flush();


$table = null;

if ($enrolled) {

    $table = new html_table;
    $table->head = array();

    foreach ($stringcolumns as $column => $columntext) {

        if ($sort != $column) {
            $columnicon = "";
            if ($column == "lastaccess") {
                $columndir = "DESC";
            } else {
                $columndir = "ASC";
            }
        } else {
            $columndir = $dir == "ASC" ? "DESC":"ASC";
            if ($column == "lastaccess") {
                $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
            } else {
                $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
            }
            $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, $dir);
        }
        $table->head[] = "<a href=\"report.php?sort=$column&amp;dir=$columndir\">" . $columntext . "</a>$columnicon";

    }

    $table->attributes = array('class' => 'generaltable block_selfenrollment-report');
    $table->data = array();

    foreach ($enrolled as $row) {

        $lastaccess = userdate($row->lastaccess, $strftimedate);

        // Create the row and add it to the table
        $cells = array(
            $row->username, $row->firstname, $row->lastname, $row->email,
            userdate($row->lastaccess, $strftimedate),
            $row->cohortname, userdate($row->timeadded, $strftimedate),
            get_string('enroltype_' . $row->type, 'block_selfenrollment')
        );

        $tablerow = new html_table_row($cells);
        $table->data[] = $tablerow;
    }

}

if ($extrasql !== '') {
    echo $OUTPUT->heading("$enrolledsearchcount / $enrolledcount " . get_string('courses'));
    $enrolledcount = $enrolledsearchcount;
} else {
    echo $OUTPUT->heading($enrolledcount . ' ' . get_string('courses'));
}

echo $OUTPUT->paging_bar($enrolledcount, $page, $perpage, $baseurl);

// Add filters.
$filtering->display_add();
$filtering->display_active();

if (!empty($table)) {
    echo $OUTPUT->box_start();

    echo html_writer::table($table);

    echo $OUTPUT->box_end();

    echo $OUTPUT->paging_bar($enrolledcount, $page, $perpage, $baseurl);


    // Download form.
    echo $OUTPUT->heading(get_string('download', 'admin'));

    echo $OUTPUT->box_start();
    echo '<ul>';
    echo '    <li><a href="' . $baseurl . '&format=csv">'.get_string('downloadtext').'</a></li>';
    echo '    <li><a href="' . $baseurl . '&format=ods">'.get_string('downloadods').'</a></li>';
    echo '    <li><a href="' . $baseurl . '&format=excel">'.get_string('downloadexcel').'</a></li>';
    echo '</ul>';
    echo $OUTPUT->box_end();

} else {
    echo $OUTPUT->heading(get_string('enrolled_notfound', 'block_selfenrollment'), 3);
}

echo $OUTPUT->footer();
