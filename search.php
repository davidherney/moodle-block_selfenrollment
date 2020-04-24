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
require_once $CFG->dirroot . '/cohort/lib.php';

$enrol        = optional_param('enrol', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$sort         = optional_param('sort', 'id', PARAM_ALPHA);
$dir          = optional_param('dir', 'DESC', PARAM_ALPHA);
$page         = optional_param('spage', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
$top          = optional_param('top', 0, PARAM_INT);
$filter       = optional_param('filter', 0, PARAM_INT);

$operation    = optional_param('op', null, PARAM_TEXT);

$systemcontext = context_system::instance();

$PAGE->set_url('/blocks/selfenrollment/search.php');
$PAGE->set_context($systemcontext);

$s_title = get_string('search_courses', 'block_selfenrollment');
$PAGE->set_title($s_title);
$PAGE->set_heading($s_title);
$PAGE->set_pagelayout('mydashboard');

echo $OUTPUT->header();

$blockconfig = get_config('block_selfenrollment');

// Enrol into an offer, after confirmation
if ($enrol && confirm_sesskey()) {
    $offer = $DB->get_record('block_selfenrollment_offers', array('id'=>$enrol), '*', MUST_EXIST);

    if ($confirm != md5($enrol)) {
        $returnurl = new moodle_url('/blocks/selfenrollment/search.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page, 'top' => $top));
        echo $OUTPUT->heading(get_string('offer_enrol', 'block_selfenrollment'));
        $optionsyes = array('enrol'=>$enrol, 'confirm'=>md5($enrol), 'sesskey'=>sesskey());
        $a = new stdClass();
        $a->name = $offer->name;
        $a->terms = $CFG->wwwroot . '/blocks/selfenrollment/terms.php';

        echo $OUTPUT->container_start('block_selfenrollment_terms');
        echo $OUTPUT->confirm(get_string('enrolcheck', 'block_selfenrollment', $a), new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->container_end();

        $PAGE->requires->js_call_amd('block_selfenrollment/self_control', 'terms');
        $PAGE->requires->string_for_js('terms_title', 'block_selfenrollment');
        echo $OUTPUT->footer();
        die;
    }
    else if (data_submitted()) {

        $params = array($offer->id, time(), time());
        $cohorts = $DB->get_records_select('block_selfenrollment_cohort', "offerid = ? AND timestart <= ? AND timeend >= ?", $params);

        if (is_array($cohorts) && count($cohorts) > 0) {
            $operation = 'enrol';
            $enrolled_cohorts = array();
            foreach($cohorts as $cohort) {
                $globalcohort = $DB->get_record('cohort', array('id' => $cohort->cohortid));
                cohort_add_member($cohort->cohortid, $USER->id);
                $enrolled_cohorts[] = $cohort->cohortid;
                $data = new stdClass();
                $data->userid = $USER->id;
                $data->offerid = $enrol;
                $data->cohortid = $cohort->cohortid;
                $data->cohortname = $globalcohort->name;
                $data->cohortidnumber = $globalcohort->idnumber;
                $data->timeadded = time();
                $DB->insert_record('block_selfenrollment_log', $data);

                if ($offer->emailtext) {
                    $messagehtml = format_text($offer->emailtext, FORMAT_MOODLE, array('para' => false, 'newlines' => true, 'filter' => false));
                    $messagetext = html_to_text($messagehtml);

                    $subject = get_string('enroledsubject', 'block_selfenrollment', format_string($offer->name));

                    $contact = core_user::get_support_user();

                    email_to_user($USER, $contact, $subject, $messagetext, $messagehtml);
                }
            }

            require_once 'classes/event/enrol_created.php';
            $event = \block_selfenrollment\event\enrol_created::create(array(
                'objectid' => $offer->id,
                'context' => $PAGE->context,
                'relateduserid' => $USER->id,
                'other' => array('cohorts' => implode(',', $enrolled_cohorts))
            ));
            $event->trigger();
        }
        else {
            $operation = 'notenrol_notcohort';
        }
    }
}

if ($operation) {
    switch ($operation) {
        case 'enrol':
            $statusmsg =  get_string('enroled', 'block_selfenrollment');
            $statustype = 'notifysuccess';
            break;
        case 'notenrol_notcohort':
            $statusmsg =  get_string('notenrol_notcohort', 'block_selfenrollment');
            $statustype = 'notifyerror';
            break;
        default:
            $statusmsg =  '';
            $statustype = '';
            break;
    }
    echo $OUTPUT->notification($statusmsg, $statustype);
}

// create the filter form
if ($top == 0) {
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
        echo $OUTPUT->heading(get_string('offers', 'block_selfenrollment', $offerscount));
    }

} else {
    echo $OUTPUT->heading(get_string('topoffers_title', 'block_selfenrollment'));

    $where = '';
    if ($filter > 0) {
        $time = time() - ($filter * 30 * 24 * 60 * 60); // Months * Days * Hours * Minutes * Seconds
        $where = 'AND el.timeadded >= ' . $time;
    }

    $sql = "SELECT eo.id, eo.type, eo.name, eo.modality, eo.intensity, eo.cost, eo.currency,
            eo.description, eo.active, eo.observations, eo.emailtext, COUNT(el.offerid) AS total
        FROM {block_selfenrollment_offers} AS eo
        LEFT JOIN {block_selfenrollment_log} AS el ON eo.id = el.offerid
        WHERE eo.active = 1 {$where}
        GROUP BY eo.id ORDER BY total DESC LIMIT 50";
    $offers = $DB->get_records_sql($sql);
}


echo $OUTPUT->box_start('generalbox specialbox');
if ($top == 1) {
    echo html_writer::tag('p', get_string('offer_explanation_top', 'block_selfenrollment'));
} else {
    echo html_writer::tag('p', get_string('offer_explanation', 'block_selfenrollment'));
}
echo $OUTPUT->box_end();

if ($top == 1) {
    require_once $CFG->libdir . '/form/select.php';

    $ranges = array(
        '?top=1&filter=0' => get_string('daterange_all', 'block_selfenrollment'),
        '?top=1&filter=6' => get_string('daterange_6', 'block_selfenrollment'),
        '?top=1&filter=3' => get_string('daterange_3', 'block_selfenrollment'),
        '?top=1&filter=1' => get_string('daterange_1', 'block_selfenrollment')
    );

    $current = '?top=1&filter=' . $filter;

    $list = new MoodleQuickForm_select('daterange', get_string('daterange_filter', 'block_selfenrollment'),
                                        $ranges, array("onchange" => 'location = this.value;', 'class' => 'custom-select'));
    $list->setSelected($current);

    echo $OUTPUT->box_start('generalbox box_filter_top');
    echo html_writer::tag('p', get_string('daterange_filter', 'block_selfenrollment') . $list->toHtml());
    echo $OUTPUT->box_end();
} else {
    $pagingbar = new paging_bar($offerscount, $page, $perpage, "search.php?sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;");
    $pagingbar->pagevar = 'spage';
    echo $OUTPUT->render($pagingbar);

    // add filters
    $ifiltering->display_add();
    $ifiltering->display_active();
}


$table = new html_table();
$table->attributes['class'] = 'admintable generaltable block_selfenrollment_table';
$table->cellspacing = 0;

$table->head = array();

if ($top == 1) {
    $table->head[] = get_string('position', 'block_selfenrollment');
    $table->head[] = get_string('totalenrol', 'block_selfenrollment');
}

$columns = array();
$columns['type'] = $t_type = get_string('offer_type', 'block_selfenrollment');
$columns['name'] = $t_name = get_string('name');
$columns['intensity'] = $t_intensity = get_string('intensity', 'block_selfenrollment');
$columns['modality'] = $t_modality = get_string('modality', 'block_selfenrollment');

if ($blockconfig->costenabled) {
    $columns['cost'] = get_string('cost', 'block_selfenrollment');
}

foreach ($columns as $ckey => $column) {
    if ($top == 1) {
        $table->head[] = $column;
        continue;
    }

    if ($sort != $ckey) {
        $columnicon = "";
        $columndir = "ASC";
    }
    else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, $dir);
    }
    $url = new moodle_url('/blocks/selfenrollment/search.php', array('sort' => $ckey, 'dir' => $columndir, 'perpage' => $perpage, 'page'=>$page));
    $table->head[] = html_writer::link($url, $column) . $columnicon;
}

$table->head[] = get_string('more', 'block_selfenrollment');
$table->head[] = get_string('operations', 'block_selfenrollment');

if ($top == 1) {
    $table->align[2] = 'center';
    $table->align[3] = 'center';
    $table->align[4] = 'center';
    $table->align[5] = 'center';
    $table->align[7] = 'center';
    $table->colclasses[0] = 'numeric';
    $table->colclasses[1] = 'numeric';
    $table->colclasses[6] = 'numeric';
    $table->wrap[4] = 'nowrap';
    $table->wrap[6] = 'nowrap';
} else {
    $table->align[0] = 'center';
    $table->align[1] = 'center';
    $table->align[2] = 'center';
    $table->align[3] = 'center';
    $table->align[5] = 'center';
    $table->colclasses[4] = 'numeric';
    $table->wrap[2] = 'nowrap';
    $table->wrap[4] = 'nowrap';
}



//Operations column
$table->head[] = '';

if($offers) {

    $position = 0;
    foreach($offers as $offer){

        /*if (!$offer->active) {
            continue;
        }*/

        $position++;

        $data = array ();

        if ($top == 1) {
            $data[] = $position;
            $data[] = $offer->total;
        }

        $data[] = $offer->type;
        $data[] = $offer->name;
        $data[] = $offer->intensity == 0 ? get_string('intensitynot', 'block_selfenrollment', $offer->intensity) :
                                            get_string('intensity_format', 'block_selfenrollment', $offer->intensity);
        $data[] = html_writer::tag('span', '', array('class'=>"fa modality-" . $offer->modality,
                                                    'title'=>get_string('modality_' . $offer->modality, 'block_selfenrollment')));

        if ($blockconfig->costenabled) {
            $data[] = $offer->cost <= 0 ? get_string('free', 'block_selfenrollment') : $offer->cost . ' ' . $offer->currency;
        }

        $data[] = html_writer::tag('span', '', array('class'=>"fa icon-more", 'title'=>get_string('more', 'block_selfenrollment'), 'offerid'=>$offer->id));

        $operations = '';

        if (!$offer->active) {
            $operations .= html_writer::tag('span', get_string('enrol_notactive', 'block_selfenrollment'), array('class'=>'mark'));
        }
        else if (has_capability('block/selfenrollment:enrol', $systemcontext)) {
            $params = array($offer->id, time(), time());
            $cohorts = $DB->get_records_select('block_selfenrollment_cohort', "offerid = ? AND timestart <= ? AND timeend >= ?", $params);

            if (is_array($cohorts) && count($cohorts) > 0) {
                $enroled = false;
                foreach($cohorts as $cohort) {
                    if (cohort_is_member($cohort->cohortid, $USER->id)) {
                        $enroled = true;
                        break;
                    }
                }

                if ($enroled) {
                    $operations .= html_writer::tag('span', get_string('enrol_enroled', 'block_selfenrollment'), array('class'=>'mark'));
                }
                else {
                    $enrol_url = new moodle_url($CFG->wwwroot . '/blocks/selfenrollment/search.php', array('enrol'=> $offer->id, 'sesskey'=>sesskey(), 'top' => $top));
                    $operations .= html_writer::tag('a', get_string('enrol', 'block_selfenrollment'), array('href'=>$enrol_url, 'class'=>'btn'));
                }
            }
            else {
                $operations .= html_writer::tag('span', get_string('enrol_notcohort', 'block_selfenrollment'), array('class'=>'mark'));
            }

        }
        $data[] = $operations;

        $table->data[] = $data;
    }

    $PAGE->requires->js_call_amd('block_selfenrollment/self_control', 'initialise');
    $PAGE->requires->string_for_js('more_title', 'block_selfenrollment');
}

echo $OUTPUT->container_start('conventions');
echo html_writer::tag('span', '', array('class'=>"fa modality-self"));
echo html_writer::tag('span', get_string('modality_self', 'block_selfenrollment'));
echo html_writer::tag('span', '', array('class'=>"fa modality-tutor"));
echo html_writer::tag('span', get_string('modality_tutor', 'block_selfenrollment'));
echo html_writer::tag('span', '', array('class'=>"fa icon-more"));
echo html_writer::tag('span', get_string('more', 'block_selfenrollment'));
echo $OUTPUT->container_end();

echo html_writer::table($table);

if ($top == 0) {
    echo $OUTPUT->render($pagingbar);
}

$t_description = get_string('description');
$t_active = get_string('active', 'block_selfenrollment');
$t_state_detail = get_string('state_detail', 'block_selfenrollment');
$t_observations = get_string('observations', 'block_selfenrollment');
$t_cost = get_string('cost', 'block_selfenrollment');

$costenabledstyle = $blockconfig->costenabled ? '' : 'display: none';

$more_html = <<<EOD

<div id="block_selfenrollment_offer_template_more" style="display: none;">
    <table class="block_selfenrollment_table_view">
        <tbody>
            <tr>
                <th>{$t_type}</th>
                <td><span id="block_selfenrollment_offer_type"></span></td>
            </tr>
            <tr>
                <th>{$t_name}</th>
                <td><span id="block_selfenrollment_offer_name"></span></td>
            </tr>
            <tr>
                <th>{$t_intensity}</th>
                <td><span id="block_selfenrollment_offer_intensity"></span></td>
            </tr>
            <tr>
                <th>{$t_modality}</th>
                <td><span id="block_selfenrollment_offer_modality"></span></td>
            </tr>
            <tr style="{$costenabledstyle}">
                <th>{$t_cost}</th>
                <td><span id="block_selfenrollment_offer_cost"></span></td>
            </tr>
            <tr class="full-line">
                <th colspan="2">{$t_description}</th>
            </tr>
            <tr>
                <td colspan="2"><span id="block_selfenrollment_offer_description"></span></td>
            </tr>
            <tr class="full-line subtitle">
                <th colspan="2">{$t_state_detail}</th>
            </tr>
            <tr>
                <th>{$t_active}</th>
                <td><span id="block_selfenrollment_offer_active"></span></td>
            </tr>
            <tr>
                <th>{$t_observations}</th>
                <td><span id="block_selfenrollment_offer_observations"></span></td>
            </tr>
        </tbody>
    </table>
</div>
EOD;

echo $more_html;

echo $OUTPUT->footer();
