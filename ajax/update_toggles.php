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
 * Updating the user preferences with the current toggle state of all sections in the course
 */
require_once('../../../../config.php');

function update_toggle_status($courseid, $toggleSeq) {
    global $DB, $USER;

    $name = "toggle_seq_".$courseid;
    if ($DB->record_exists('user_preferences', array('userid' => $USER->id, 'name' => $name))) {
        $toggleSeqRecord = $DB->get_record('user_preferences', array('userid' => $USER->id, 'name' => $name));
        $toggleSeqRecord->value = $toggleSeq;
        $DB->update_record('user_preferences', $toggleSeqRecord);
    } else {
        $toggleSeqRecord = new \stdClass();
        $toggleSeqRecord->userid = $USER->id;
        $toggleSeqRecord->name = $name;
        $toggleSeqRecord->value = $toggleSeq;
        $DB->insert_record('user_preferences', $toggleSeqRecord);
    }
    return $toggleSeq;
}

if (!isset($_POST['toggle_seq']) || sizeof(str_split($_POST['toggle_seq'])) === 0) {
    exit;
}

echo update_toggle_status($_POST['courseid'], $_POST['toggle_seq']);