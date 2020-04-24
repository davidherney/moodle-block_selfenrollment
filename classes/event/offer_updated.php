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

/**
 *
 * @package    block_selfenrollment
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_selfenrollment\event;

defined('MOODLE_INTERNAL') || die();

class offer_updated extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'block_selfenrollment_offers';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventoffer_updated', 'block_selfenrollment');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {

        if (!empty($this->other)) {
            switch ($this->other['element']) {
                default:
                    $res = "The user with id '$this->userid' updated the offer with id '$this->objectid'. Action: " . $this->other['action'] . " - " . $this->other['element'] . " (id:" . $this->other['elementid'] . ").";
            }
        }
        else {
            $res = "The user with id '$this->userid' updated the offer with id '$this->objectid'.";
        }

        return $res;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/selfenrollment/offer_edit.php', array('id' => $this->objectid));
    }

}