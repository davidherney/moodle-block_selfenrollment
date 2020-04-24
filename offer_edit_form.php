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
require_once $CFG->dirroot . '/blocks/selfenrollment/locallib.php';

/**
 * The form for handling editing a offer
 */
class block_selfenrollment_offer_edit_form extends moodleform {
    protected $_data;

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE, $DB;

        $mform    = $this->_form;
        $blockconfig = get_config('block_selfenrollment');

        // This contains the data of this form.
        $this->_data  = $this->_customdata['data'];

        if (!$this->_data) {
            $this->_data = new stdClass();
        }
        else {
            $this->_data->description = array('text'=>$this->_data->description);
            $this->_data->observations = array('text'=>$this->_data->observations);
            $this->_data->emailtext = array('text'=>$this->_data->emailtext);
        }

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
        $editorattributes = array ('rows'=> 5, 'cols'=>50);

        //Select a course
        $mform->addElement('header', 'general', get_string('general'));

        $types = block_selfenrollment_offers_types_list();
        $mform->addElement('select', 'type', get_string('offertypes', 'block_selfenrollment'), $types);

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="127" size="30"');
        $mform->addRule('name', get_string('missingfield', 'block_selfenrollment'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $modalities = array('self'=>get_string('modality_self', 'block_selfenrollment'), 'tutor'=>get_string('modality_tutor', 'block_selfenrollment'));
        $mform->addElement('select', 'modality', get_string('modality', 'block_selfenrollment'), $modalities);

        $mform->addElement('text', 'intensity', get_string('intensity', 'block_selfenrollment'), 'maxlength="4" size="10"');
        $mform->addRule('intensity', get_string('missingfield', 'block_selfenrollment'), 'required', null, 'client');
        $mform->addRule('intensity', get_string('notnumeric', 'block_selfenrollment'), 'numeric', null, 'client');
        $mform->setType('intensity', PARAM_TEXT);


        if ($blockconfig->costenabled) {
            $mform->addElement('text', 'cost', get_string('cost', 'block_selfenrollment'), 'maxlength="10" size="10"');
            $mform->addRule('cost', get_string('missingfield', 'block_selfenrollment'), 'required', null, 'client');
            $mform->addRule('cost', get_string('notnumeric', 'block_selfenrollment'), 'numeric', null, 'client');
            $mform->setType('cost', PARAM_TEXT);

            $currencies = array(''=>'', 'USD'=>'USD', 'COP'=>'COP');
            $mform->addElement('select', 'currency', get_string('currency', 'block_selfenrollment'), $currencies);
        } else {
            $mform->addElement('hidden', 'cost', 0);
            $mform->setType('cost', PARAM_INT);

            $mform->addElement('hidden', 'currency', '');
            $mform->setType('currency', PARAM_TEXT);
        }

        $mform->addElement('editor', 'description', get_string('description'), $editorattributes, $editoroptions);

        $active = $mform->addElement('selectyesno', 'active', get_string('active', 'block_selfenrollment'));
        $active->setValue(1);

        $mform->addElement('editor', 'observations', get_string('observations', 'block_selfenrollment'), $editorattributes, $editoroptions);

        $mform->addElement('editor', 'emailtext', get_string('enrolemail', 'block_selfenrollment'), $editorattributes, $editoroptions);

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();

        // Finally set the current form data.
        $this->set_data($this->_data);
    }

}

