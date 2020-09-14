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
 * User: Matthias Opitz
 * Date: 04/10/18
 * Time: 14:46
 *
 * Updating the course format options with a new sequence in which the tabs are displayed
 */
require_once('../../../../config.php');

function update_tab_seq($courseid, $tabSeq) {
    global $DB;

    if ($DB->record_exists('course_format_options', array('courseid' => $courseid, 'name' => 'tab_seq'))) {
        $tabSeqRecord = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => 'tab_seq'));
        $tabSeqRecord->value = $tabSeq;
        $DB->update_record('course_format_options', $tabSeqRecord);
    } else {
        $tabSeqRecord = new \stdClass();
        $tabSeqRecord->courseid = $courseid;
        $tabSeqRecord->format = $_POST['course_format_name'];
        $tabSeqRecord->sectionid = 0;
        $tabSeqRecord->name = 'tab_seq';
        $tabSeqRecord->value = $tabSeq;
        $DB->insert_record('course_format_options', $tabSeqRecord);
    }
    return $tabSeq;
}

if (!isset($_POST['tab_seq']) || sizeof($_POST['tab_seq']) === 0) {
    exit;
}

echo update_tab_seq($_POST['courseid'], $_POST['tab_seq']);