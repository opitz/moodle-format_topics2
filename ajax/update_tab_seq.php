<?php
/**
 * Created by PhpStorm.
 * User: Matthias Opitz
 * Date: 04/10/18
 * Time: 14:46
 *
 * Updating the course format options with a new sequence in which the tabs are displayed
 */
require_once('../../../../config.php');

function update_tab_seq($courseid, $tab_seq) {
    global $DB;

    if($DB->record_exists('course_format_options', array('courseid'=>$courseid, 'name'=>'tab_seq'))) {
        $tab_seq_record = $DB->get_record('course_format_options', array('courseid'=>$courseid, 'name'=>'tab_seq'));
        $tab_seq_record->value = $tab_seq;
        $DB->update_record('course_format_options', $tab_seq_record);
    } else {
        $tab_seq_record = new \stdClass();
        $tab_seq_record->courseid = $courseid;
        $tab_seq_record->format = 'tabbedtopics';
        $tab_seq_record->sectionid = 0;
        $tab_seq_record->name = 'tab_seq';
        $tab_seq_record->value = $tab_seq;
        $DB->insert_record('course_format_options', $tab_seq_record);
    }
    return $tab_seq;
}

if(!isset($_POST['tab_seq']) || sizeof($_POST['tab_seq']) === 0) {
    exit;
}
$tab_seq = $_POST['tab_seq'];
$sectionid = $_POST['sectionid'];

echo update_tab_seq($_POST['courseid'], $_POST['tab_seq']);