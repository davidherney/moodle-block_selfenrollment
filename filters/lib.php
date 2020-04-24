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


require_once $CFG->dirroot . '/blocks/selfenrollment/filters/selfenrollment_filter_forms.php';


/**
 * filtering wrapper class.
 */
class selfenrollment_filtering {
  private $_fields;
  private $_addform;
  private $_activeform;

  /**
   * Contructor
   * @param array array of visible fields
   * @param string base url used for submission/return, null if the same of current page
   * @param array extra page parameters
   */
  function __construct($showUsers = false, $fieldnames=null, $baseurl=null, $extraparams=null) {
    global $SESSION;

    if (!isset($SESSION->selfenrollment_filtering)) {
        $SESSION->selfenrollment_filtering = array();
    }

    if (empty($fieldnames)) {

        $fieldnames = array('state'=>0,
                'space_type'=>1,
                'createddate'=>1,
                'approveddate'=>1,
                'migrateddate'=>1);//, 'date'=>1);

        if($showUsers){
            $fieldnames['realname'] = 1;
            $fieldnames['username'] = 1;
        }
    }

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
            if (!array_key_exists($fname, $SESSION->selfenrollment_filtering)) {
                $SESSION->selfenrollment_filtering[$fname] = array();
            }
            $SESSION->selfenrollment_filtering[$fname][] = $data;
        }
        // clear the form
        $_POST = array();
        $this->_addform = new selfenrollment_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
    }

    // now the active filters
    $this->_activeform = new selfenrollment_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
    if ($adddata = $this->_activeform->get_data(false)) {
        if (!empty($adddata->removeall)) {
            $SESSION->selfenrollment_filtering = array();

        } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
            foreach($adddata->filter as $fname=>$instances) {
                foreach ($instances as $i=>$val) {
                    if (empty($val)) {
                        continue;
                    }
                    unset($SESSION->selfenrollment_filtering[$fname][$i]);
                }
                if (empty($SESSION->selfenrollment_filtering[$fname])) {
                    unset($SESSION->selfenrollment_filtering[$fname]);
                }
            }
        }
        // clear+reload the form
        $_POST = array();
        $this->_activeform = new selfenrollment_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams'=>$extraparams));
    }
    // now the active filters
  }

  /**
   * Creates known selfenrollment filter if present
   * @param string $fieldname
   * @param boolean $advanced
   * @return object filter
   */
  function get_field($fieldname, $advanced) {
    global $USER, $CFG;
  }

  /**
   * Returns sql where statement based on active user filters
   * @param string $extra sql
   * @return string
   */
  function get_sql_filter($extra='') {
      global $SESSION;

      $sqls = array();
      $statesConditions = array();

      if ($extra != '') {
          $sqls[] = $extra;
      }

      if (!empty($SESSION->selfenrollment_filtering)) {
          foreach ($SESSION->selfenrollment_filtering as $fname=>$datas) {
              if (!array_key_exists($fname, $this->_fields)) {
                  continue; // filter not used
              }
              $field = $this->_fields[$fname];
              foreach($datas as $i=>$data) {
                    if (isset($field->_state)) {
                        $statesConditions[] = $field->get_sql_filter($data);
                    }
                    else {
                        $sqls[] = $field->get_sql_filter($data);
                    }
              }
          }
      }

        $res = array();
        if (count($statesConditions) > 0) {
            $res['statecondition'] = implode(' OR ', $statesConditions);
            $res['countstatecondition'] = count($statesConditions);
        }
        else {
            $res['statecondition'] = '';
            $res['countstatecondition'] = 0;
        }

        if (empty($sqls)) {
            $res['sqlcondition'] = '';
        }
        else {
            $res['sqlcondition'] = implode(' AND ', $sqls);
        }

        return $res;
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

/**
* The base user filter class. All abstract classes must be implemented.
*/
class selfenrollment_filter_type {
    /**
     * The name of this filter instance.
     * @var string
     */
    public $_name;

    /**
     * The label of this filter instance.
     * @var string
     */
    public $_label;

    /**
     * Advanced form element flag
     * @var bool
     */
    public $_advanced;

    /**
    * Constructor
    * @param string $name the name of the filter instance
    * @param string $label the label of the filter instance
    * @param boolean $advanced advanced form element flag
    */
    function __construct($name, $label, $advanced) {
        $this->_name     = $name;
        $this->_label    = $label;
        $this->_advanced = $advanced;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return string the filtering condition or null if the filter is disabled
     */
    public function get_sql_filter($data) {
        print_error('mustbeoveride', 'debug', '', 'get_sql_filter');
    }

    /**
     * Retrieves data from the form data
     * @param stdClass $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata) {
        print_error('mustbeoveride', 'debug', '', 'check_data');
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param moodleform $mform a MoodleForm object to setup
     */
    public function setupForm(&$mform) {
        print_error('mustbeoveride', 'debug', '', 'setupForm');
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {
        print_error('mustbeoveride', 'debug', '', 'get_label');
    }
}
