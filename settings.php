<?php
//
// This is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for the selfenrollment block
 *
 * @package   block_selfenrollment
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Text when is a search button.
    $name = 'block_selfenrollment/searchtext';
    $title = get_string('search_courses_text', 'block_selfenrollment');
    $default = get_string('search_courses', 'block_selfenrollment');
    $setting = new admin_setting_confightmleditor($name, $title, '', $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Text when is a courses top.
    $name = 'block_selfenrollment/topcoursestext';
    $title = get_string('top_courses_text', 'block_selfenrollment');
    $default = get_string('top_courses', 'block_selfenrollment');
    $setting = new admin_setting_confightmleditor($name, $title, '', $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Offer types list.
    $name = 'block_selfenrollment/offertypes';
    $title = get_string('offertypes', 'block_selfenrollment');
    $setting = new admin_setting_configtextarea($name, $title, '', '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Offer types list.
    $name = 'block_selfenrollment/costenabled';
    $title = get_string('costenabled', 'block_selfenrollment');
    $setting = new admin_setting_configcheckbox($name, $title, '', 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

}
