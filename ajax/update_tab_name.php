<?php
/**
 * Created by PhpStorm.
 * User: opitz
 * Date: 2019-01-18
 *
 * Updating the course format options with a new sequence in which the tabs are displayed
 */
require_once('../../../../config.php');

function update_tab_name($courseid, $tabid, $tab_name)
{
    global $COURSE, $DB, $PAGE;

    $context = context_course::instance($courseid);

    if (has_capability('moodle/course:update', $context)) {
        $format_options = $DB->get_records('course_format_options', array('courseid' => $courseid));
        foreach ($format_options as $option) {
            if ($option->name == $tabid . '_title' && $option->value !== $tab_name) {
                $option->value = $tab_name;
                $DB->update_record('course_format_options', $option);
                return $option->id;
            }
        }
    }
    return '';
}


echo update_tab_name($_POST['courseid'], $_POST['tabid'], $_POST['tab_name']);