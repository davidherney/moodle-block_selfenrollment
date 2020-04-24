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
 * Form for editing selfenrollment block instances.
 *
 * @package   block_selfenrollment
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_selfenrollment extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_selfenrollment');
    }

    function has_config() {
        return true;
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newblocktitle', 'block_selfenrollment'));
    }

    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $current = explode('/', $_SERVER['SCRIPT_FILENAME']);

        $script = array_pop($current);
        $path = array_pop($current);
        $current_search = '';
        $current_top = '';
        $current_manage = '';
        $current_report = '';
        if ($path == 'selfenrollment') {
            switch ($script) {
                case 'search.php':
                    $top = optional_param('top', 0, PARAM_INT);
                    if ($top) {
                        $current_top = 'current';
                    }
                    else {
                        $current_search = 'current';
                    }
                    break;
                case 'manage.php':
                    $current_manage = 'current';
                    break;
                case 'report.php':
                    $current_report = 'current';
                    break;
            }
        }
        $html = '';

        $block_url = $CFG->wwwroot.'/blocks/selfenrollment';
        $html .= '<div class="block_selfenrollment_box_search ' . $current_search . '">
                        <a class="button" href="'. $block_url . '/search.php">';
        $html .= empty($this->config->searchtext) ?
                        get_string('search_courses', 'block_selfenrollment') :
                        $this->config->searchtext;
        $html .= '</a></div>';

        $html .= '<div class="block_selfenrollment_box_top ' . $current_top . '">
                        <a class="button" href="'. $block_url . '/search.php?top=1">';
        $html .= empty($this->config->topcoursestext) ?
                        get_string('top_courses', 'block_selfenrollment') :
                        $this->config->topcoursestext;
        $html .= '</a></div>';

        $systemcontext = context_system::instance();
        if (has_capability('block/selfenrollment:manage', $systemcontext)){
            $html .= '<div class="block_selfenrollment_box_manage ' . $current_manage . '">
                            <a class="button" href="'. $block_url . '/manage.php">';
            $html .=            get_string('manage_offers', 'block_selfenrollment');
            $html .= '</a></div>';

            $html .= '<div class="block_selfenrollment_box_manage ' . $current_report . '">
                            <a class="button" href="'. $block_url . '/report.php">';
            $html .=            get_string('enrolled_report', 'block_selfenrollment');
            $html .= '</a></div>';
        }


        $this->content         =  new stdClass;
        $this->content->text   = $html;
        $this->content->footer = '';

        return $this->content;
    }

    public function instance_can_be_docked() {
        return true;
    }

}
