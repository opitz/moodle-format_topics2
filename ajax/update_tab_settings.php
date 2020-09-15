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
 * Created by PhpStorm.
 * User: opitz
 * Date: 2019-09-20
 *
 * Updating the course format options for a tab
 */
require_once('../../../../config.php');
require_login();

function update_tab_settings($courseid, $tabid, $sections, $sectionnums)
{
    global $DB;

    $context = context_course::instance($courseid);

    if (has_capability('moodle/course:update', $context)) {
        // Save the sections of the tab.
        $formatoption = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => $tabid));
        if (isset($formatoption) && $formatoption) {
            $formatoption->value = $sections;
            $DB->update_record('course_format_options', $formatoption);
        }
        // Save the sectionnums of the tab.
        $formatoption = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => $tabid.'_sectionnums'));
        if (isset($formatoption) && $formatoption) {
            $formatoption->value = $sectionnums;
            $DB->update_record('course_format_options', $formatoption);
        }
    }
    return 'ok';
}


echo update_tab_settings($_POST['courseid'], $_POST['tabid'], $_POST['sections'], $_POST['sectionnums']);