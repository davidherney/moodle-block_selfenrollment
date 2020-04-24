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


require_once $CFG->dirroot . '/blocks/selfenrollment/filters/simpleselect.php';
require_once $CFG->dirroot . '/blocks/selfenrollment/filters/select.php';
require_once $CFG->dirroot . '/blocks/selfenrollment/filters/text.php';
require_once $CFG->dirroot . '/blocks/selfenrollment/filters/date.php';
require_once $CFG->dirroot . '/blocks/selfenrollment/filters/yesno.php';
require_once $CFG->dirroot . '/blocks/selfenrollment/locallib.php';

//========================================================================================//
//========================================================================================//

// OFFERS FILTERS

//========================================================================================//
//========================================================================================//

/**
 * offers filtering wrapper class.
 */
class block_selfenrollment_offers_filtering {
    /** @var array */
    public $_fields;
    /** @var */
    public $_addform;
    /** @var */
    public $_activeform;

    /**
    * Contructor
    */
    function __construct() {
        global $SESSION;

        $baseurl = $extraparams = null;

        if (!isset($SESSION->block_selfenrollment_offers_filtering)) {
            $SESSION->block_selfenrollment_offers_filtering = array();
        }

        $fieldnames = array('name'=>0, 'type'=>0);

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        // fist the new filter form
        $this->_addform = new selfenrollment_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));

        if ($adddata = $this->_addform->get_data(false)) {
            foreach($this->_fields as $fname=>$field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // nothing new
                }
                if (!array_key_exists($fname, $SESSION->block_selfenrollment_offers_filtering)) {
                    $SESSION->block_selfenrollment_offers_filtering[$fname] = array();
                }
                $SESSION->block_selfenrollment_offers_filtering[$fname][] = $data;
            }
            // clear the form
            $_POST = array();
            $this->_addform = new selfenrollment_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }

        // now the active filters
        $this->_activeform = new selfenrollment_offers_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        if ($adddata = $this->_activeform->get_data(false)) {
            if (!empty($adddata->removeall)) {
                $SESSION->block_selfenrollment_offers_filtering = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach($adddata->filter as $fname=>$instances) {
                    foreach ($instances as $i=>$val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->block_selfenrollment_offers_filtering[$fname][$i]);
                    }
                    if (empty($SESSION->block_selfenrollment_offers_filtering[$fname])) {
                        unset($SESSION->block_selfenrollment_offers_filtering[$fname]);
                    }
                }
            }

            // clear+reload the form
            $_POST = array();
            $this->_activeform = new selfenrollment_offers_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
        // now the active filters
    }

    /**
    * Creates known offers filter if present
    * @param string $fieldname
    * @param boolean $advanced
    * @return object filter
    */
    public function get_field($fieldname, $advanced) {
        global $USER, $CFG, $DB, $SITE;

        $types = block_selfenrollment_offers_types_list();
        switch ($fieldname) {
            case 'name':            return new selfenrollment_filter_text('name', get_string('name'), $advanced, 'name');
            case 'type':            return new selfenrollment_filter_select('type', get_string('offer_type', 'block_selfenrollment'), $advanced, 'type', $types);
            default:                return null;
        }
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array $params named params (recommended prefix ex)
     * @return array sql string and $params
     */
    public function get_sql_filter($extra='', array $params=null) {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array)$params;

        if (!empty($SESSION->block_selfenrollment_offers_filtering)) {
            foreach ($SESSION->block_selfenrollment_offers_filtering as $fname => $datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // Filter not used.
                }
                $field = $this->_fields[$fname];
                foreach ($datas as $i => $data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    $sqls[] = $s;
                    $params = $params + $p;
                }
            }
        }

        if (empty($sqls)) {
            return array('', array());
        } else {
            $sqls = implode(' AND ', $sqls);
            return array($sqls, $params);
        }
    }

    /**
    * Print the add filter form.
    */
    function display_add() {
        $this->_addform->display();
    }

    /**
    * Print the active filter form.
    */
    function display_active() {
        $this->_activeform->display();
    }

}


function get_offers_listing($get = true, $sort='id', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='', $extraselect='', array $extraparams=null, $extracontext = null) {

    global $CFG, $USER, $DB;

    $select = '';
    $params = array();

    if ($extraselect) {
        $select = " WHERE $extraselect ";
        $params = (array)$extraparams;
    }

    if (!empty($search)) {
        $search = trim($search);
    }

    if ($sort) {
        $sort = ' ORDER BY '. $sort .' '. $dir;
    }

    if (!$get) {
        $sql_select = "SELECT COUNT(*) ";
    }
    else {
        $sql_select = "SELECT * ";
    }

    $sql_select .= " FROM {$CFG->prefix}block_selfenrollment_offers";
    $sql_select .= " $select $sort";

    if ($get) {
        return $DB->get_records_sql($sql_select, $params, $page, $recordsperpage);
    }
    else {
        return $DB->count_records_sql($sql_select, $params);
    }
}

class selfenrollment_offers_active_filter_form extends moodleform {

    function definition() {
        global $SESSION; // this is very hacky :-(

        $mform       =& $this->_form;
        $fields      = $this->_customdata['fields'];
        $extraparams = $this->_customdata['extraparams'];

        if (!empty($SESSION->block_selfenrollment_offers_filtering)) {
            // add controls for each active filter in the active filters group
            $mform->addElement('header', 'actfilterhdr', get_string('actfilterhdr','filters'));

            foreach ($SESSION->block_selfenrollment_offers_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $fields)) {
                    continue; // filter not used
                }
                $field = $fields[$fname];
                foreach($datas as $i=>$data) {
                    $description = $field->get_label($data);
                    $mform->addElement('checkbox', 'filter['.$fname.']['.$i.']', null, $description);
                }
            }

            if ($extraparams) {
                foreach ($extraparams as $key=>$value) {
                    $mform->addElement('hidden', $key, $value);
                    $mform->setType($key, PARAM_RAW);
                }
            }

            $objs = array();
            $objs[] = &$mform->createElement('submit', 'removeselected', get_string('removeselected','filters'));
            $objs[] = &$mform->createElement('submit', 'removeall', get_string('removeall','filters'));
            $mform->addElement('group', 'actfiltergrp', '', $objs, ' ', false);
        }
    }
}

//========================================================================================//
//========================================================================================//

// REPORT FILTERS

//========================================================================================//
//========================================================================================//

/**
 * Report filtering wrapper class.
 */
class block_selfenrollment_report_filtering {
    /** @var array */
    public $_fields;
    /** @var */
    public $_addform;
    /** @var */
    public $_activeform;

    /**
    * Contructor
    */
    function __construct() {
        global $SESSION;

        $baseurl = $extraparams = null;

        if (!isset($SESSION->block_selfenrollment_report_filtering)) {
            $SESSION->block_selfenrollment_report_filtering = array();
        }

        $fieldnames = array('timeadded' => 0, 'cohortidnumber' => 1, 'type' => 1);

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        // fist the new filter form
        $this->_addform = new selfenrollment_add_filter_form($baseurl, array('fields' => $this->_fields, 'extraparams' => $extraparams));

        if ($adddata = $this->_addform->get_data(false)) {
            foreach($this->_fields as $fname=>$field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // nothing new
                }
                if (!array_key_exists($fname, $SESSION->block_selfenrollment_report_filtering)) {
                    $SESSION->block_selfenrollment_report_filtering[$fname] = array();
                }
                $SESSION->block_selfenrollment_report_filtering[$fname][] = $data;
            }
            // clear the form
            $_POST = array();
            $this->_addform = new selfenrollment_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }

        // now the active filters
        $this->_activeform = new selfenrollment_report_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        if ($adddata = $this->_activeform->get_data(false)) {
            if (!empty($adddata->removeall)) {
                $SESSION->block_selfenrollment_report_filtering = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach($adddata->filter as $fname=>$instances) {
                    foreach ($instances as $i=>$val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->block_selfenrollment_report_filtering[$fname][$i]);
                    }
                    if (empty($SESSION->block_selfenrollment_report_filtering[$fname])) {
                        unset($SESSION->block_selfenrollment_report_filtering[$fname]);
                    }
                }
            }

            // clear+reload the form
            $_POST = array();
            $this->_activeform = new selfenrollment_report_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
        }
        // now the active filters
    }

    /**
    * Creates known report filter if present
    * @param string $fieldname
    * @param boolean $advanced
    * @return object filter
    */
    public function get_field($fieldname, $advanced) {
        global $USER, $CFG, $DB, $SITE;

        switch ($fieldname) {
            case 'timeadded':       return new selfenrollment_filter_date('timeadded', get_string('date'), $advanced, 'cl.timeadded');
            case 'cohortidnumber':  return new selfenrollment_filter_text('cohortidnumber', get_string('idnumber', 'cohort'), $advanced, 'cl.cohortidnumber');
            case 'type':            return new selfenrollment_filter_yesno('type', get_string('enroltype_self', 'block_selfenrollment'), $advanced, 'type');
            default:                return null;
        }
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array $params named params (recommended prefix ex)
     * @return array sql string and $params
     */
    public function get_sql_filter($extra='', array $params=null) {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array)$params;

        $bytype = null;
        if (!empty($SESSION->block_selfenrollment_report_filtering)) {
            foreach ($SESSION->block_selfenrollment_report_filtering as $fname => $datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // Filter not used.
                }

                $field = $this->_fields[$fname];
                foreach ($datas as $i => $data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    if ($fname == 'type') {
                        $bytype = ($bytype !== null ? $bytype : true) && $data['value'] == '1';
                    } else {
                        $sqls[] = $s;
                        $params = $params + $p;
                    }
                }
            }
        }

        if (empty($sqls)) {
            return array('', array(), $bytype);
        } else {
            $sqls = implode(' AND ', $sqls);
            return array($sqls, $params, $bytype);
        }
    }

    /**
    * Print the add filter form.
    */
    function display_add() {
        $this->_addform->display();
    }

    /**
    * Print the active filter form.
    */
    function display_active() {
        $this->_activeform->display();
    }

}


function get_report_listing($get = true, $sort='id', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='', $extraselect='', array $extraparams=null, $extracontext = null) {

    global $CFG, $USER, $DB;

    $select = '';
    $params = array();

    if ($extraselect) {
        $select = " WHERE $extraselect ";
        $params = (array)$extraparams;
    }

    if (!empty($search)) {
        $search = trim($search);
    }

    if ($sort) {
        $sort = ' ORDER BY '. $sort .' '. $dir;
    }

    if (!$get) {
        $sql_select = "SELECT COUNT(*) ";
    }
    else {
        $sql_select = "SELECT * ";
    }

    $sql_select .= " FROM {$CFG->prefix}block_selfenrollment_offers";
    $sql_select .= " $select $sort";

    if ($get) {
        return $DB->get_records_sql($sql_select, $params, $page, $recordsperpage);
    }
    else {
        return $DB->count_records_sql($sql_select, $params);
    }
}



class selfenrollment_report_active_filter_form extends moodleform {

    function definition() {
        global $SESSION; // this is very hacky :-(

        $mform       =& $this->_form;
        $fields      = $this->_customdata['fields'];
        $extraparams = $this->_customdata['extraparams'];

        if (!empty($SESSION->block_selfenrollment_report_filtering)) {
            // add controls for each active filter in the active filters group
            $mform->addElement('header', 'actfilterhdr', get_string('actfilterhdr','filters'));

            foreach ($SESSION->block_selfenrollment_report_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $fields)) {
                    continue; // filter not used
                }
                $field = $fields[$fname];
                foreach($datas as $i=>$data) {
                    $description = $field->get_label($data);
                    $mform->addElement('checkbox', 'filter['.$fname.']['.$i.']', null, $description);
                }
            }

            if ($extraparams) {
                foreach ($extraparams as $key=>$value) {
                    $mform->addElement('hidden', $key, $value);
                    $mform->setType($key, PARAM_RAW);
                }
            }

            $objs = array();
            $objs[] = &$mform->createElement('submit', 'removeselected', get_string('removeselected','filters'));
            $objs[] = &$mform->createElement('submit', 'removeall', get_string('removeall','filters'));
            $mform->addElement('group', 'actfiltergrp', '', $objs, ' ', false);
        }
    }
}

