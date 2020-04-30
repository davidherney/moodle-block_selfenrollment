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

defined('MOODLE_INTERNAL') || die;

require_once $CFG->libdir . '/formslib.php';

/**
 * The form for handling editing a cohort offer
 */
class block_selfenrollment_cohortoffer_edit_form extends moodleform {
    protected $_data;
    protected $_offerid;

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE, $DB;

        $mform    = $this->_form;

        // this contains the data of this form
        $this->_data  = $this->_customdata['data'];
        $this->_offerid  = $this->_customdata['offerid'];

        if (!$this->_data) {
            $this->_data = new stdClass();
        }

        $dateattributes = array('stopyear'=>date('Y', time()) + 10, 'startyear'=>date('Y', time()), 'optional' => false);

        $mform->addElement('header', 'general', get_string('general'));

        $cohorts = $DB->get_records_menu('cohort',
                                            array('visible'=>true),
                                            'name',
                                            "id, " . $DB->sql_concat('name', "'('", 'idnumber', "')'"));
        $mform->addElement('select', 'cohortid', get_string('cohort', 'cohort'), $cohorts);

        $mform->addElement('date_time_selector', 'timestart', get_string('timestart', 'block_selfenrollment'), $dateattributes);

        $mform->addElement('date_time_selector', 'timeend', get_string('timeend', 'block_selfenrollment'), $dateattributes);

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'offerid', $this->_offerid);
        $mform->setType('offerid', PARAM_INT);

        $this->add_action_buttons();

        // Finally set the current form data.
        $this->set_data($this->_data);
    }

    /**
     * Perform validation on the extension form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['timestart'] >= $data['timeend']) {
            $errors['timeend'] = get_string('timeend_after', 'block_selfenrollment');
        }

        if (empty($data['cohortid'])) {
            $errors['cohortid'] = get_string('cohortid_required', 'block_selfenrollment');
        }

        return $errors;
    }
}
