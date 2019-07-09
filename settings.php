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
 * Settings for format_singleactivity
 *
 * @package    format_singleactivity
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $name = 'format_topics2/toolmenupassivewidth';
    $title = get_string('toolmenupassivewidth', 'format_topics2');
    $description = get_string('toolmenupassivewidth_desc', 'format_topics2');
    $default = '10px';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    $name = 'format_topics2/toolmenuactivewidth';
    $title = get_string('toolmenuactivewidth', 'format_topics2');
    $description = get_string('toolmenuactivewidth_desc', 'format_topics2');
    $default = '40px';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);


}
