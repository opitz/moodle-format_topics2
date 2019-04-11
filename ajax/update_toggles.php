<?php
/**
 * Created by PhpStorm.
 * User: Matthias Opitz
 * Date: 04/10/18
 * Time: 14:46
 *
 * Updating the user preferences with the current toggle state of all sections in the course
 */
require_once('../../../../config.php');

function update_toggle_status($courseid, $toggle_seq) {
    global $DB, $USER;

    $name = "toggle_seq_".$courseid;
    if($DB->record_exists('user_preferences', array('userid' => $USER->id, 'name'=>$name))) {
        $toggle_seq_record = $DB->get_record('user_preferences', array('userid' => $USER->id, 'name'=>$name));
        $toggle_seq_record->value = $toggle_seq;
        $DB->update_record('user_preferences', $toggle_seq_record);
    } else {
        $toggle_seq_record = new \stdClass();
        $toggle_seq_record->userid = $USER->id;
        $toggle_seq_record->name = $name;
        $toggle_seq_record->value = $toggle_seq;
        $DB->insert_record('user_preferences', $toggle_seq_record);
    }
    return $toggle_seq;
}

if(!isset($_POST['toggle_seq']) || sizeof($_POST['toggle_seq']) === 0) {
    exit;
}

//echo $_POST['toggle_seq'];
echo update_toggle_status($_POST['courseid'], $_POST['toggle_seq']);