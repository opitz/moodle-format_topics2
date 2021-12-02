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
 * Updating the user preferences with the current toggle state of all sections in the course
 *
 * @package    format_topics2
 * @copyright  2020 Matthias Opitz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_login();

/**
 * Update the toggle status for each topic of the course to the users preferences
 *
 * @param int $courseid
 * @param string $toggleseq
 * @return mixed
 * @throws dml_exception
 */
function update_toggle_status($courseid, $toggleseq) {
    global $DB, $USER;

    $name = "toggle_seq_".$courseid;
    if ($DB->record_exists('user_preferences', array('userid' => $USER->id, 'name' => $name))) {
        $toggleseqrecord = $DB->get_record('user_preferences', array('userid' => $USER->id, 'name' => $name));
        $toggleseqrecord->value = $toggleseq;
        $DB->update_record('user_preferences', $toggleseqrecord);
    } else {
        $toggleseqrecord = new \stdClass();
        $toggleseqrecord->userid = $USER->id;
        $toggleseqrecord->name = $name;
        $toggleseqrecord->value = $toggleseq;
        $DB->insert_record('user_preferences', $toggleseqrecord);
    }
    return $toggleseq;
}

if (!isset($_POST['toggle_seq']) || count(str_split($_POST['toggle_seq'])) === 0) {
    exit;
}

require_sesskey();
echo update_toggle_status($_POST['courseid'], $_POST['toggle_seq']);