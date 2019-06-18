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
 * This file contains main class for the course format TabbedTopic
 *
 * @since     Moodle 2.0
 * @package   format_topics2
 * @copyright 2018 Matthias Opitz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/topics/lib.php');

/**
 * Main class for the topics2 course format
 * with added tab-ability
 *
 * @package    format_topics2
 * @copyright  2012 Marina Glancy / 2018 Matthias Opitz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_topics2 extends format_topics {

    public function course_format_options($foreditform = false) {
        global $CFG, $COURSE, $DB;
//        $max_tabs = (isset($CFG->max_tabs) ? $CFG->max_tabs : 5);
//        $max_tabs = 9; // Currently there is a maximum of 9 tabs!
        $fo = $DB->get_records('course_format_options', array('courseid' => $COURSE->id));
        $format_options = array();
        foreach($fo as $o) {
            $format_options[$o->name] = $o->value;
        }
        $max_tabs = ((isset($format_options['maxtabs']) && $format_options['maxtabs'] > 0) ? $format_options['maxtabs'] : (isset($CFG->max_tabs) ? $CFG->max_tabs : 9));
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'maxtabs' => array(
                    'label' => get_string('maxtabs_label', 'format_topics2'),
                    'help' => 'maxtabs',
                    'help_component' => 'format_topics2',
                    'default' => (isset($CFG->max_tabs) ? $CFG->max_tabs : 5),
                    'type' => PARAM_INT,
//                    'element_type' => 'hidden',
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ),
                'toggle' => array(
                    'label' => get_string('toggle_label', 'format_topics2'),
                    'element_type' => 'advcheckbox',
                    'help' => 'toggle',
                    'help_component' => 'format_topics2',
                ),
                'section0_ontop' => array(
                    'label' => get_string('section0_label', 'format_topics2'),
                    'element_type' => 'advcheckbox',
                    'default' => 0,
                    'help' => 'section0',
                    'help_component' => 'format_topics2',
                    'element_type' => 'hidden',
                ),
                'single_section_tabs' => array(
                    'label' => get_string('single_section_tabs_label', 'format_topics2'),
                    'element_type' => 'advcheckbox',
                    'help' => 'single_section_tabs',
                    'help_component' => 'format_topics2',
                ),
            );

            // the sequence in which the tabs will be displayed
            $courseformatoptions['tab_seq'] = array('default' => '','type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);

            // now loop through the tabs but don't show them as we only need the DB records...
            $courseformatoptions['tab0_title'] = array('default' => get_string('tabzero_title', 'format_topics2'),'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
            $courseformatoptions['tab0'] = array('default' => "",'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);

            for ($i = 1; $i <= $max_tabs; $i++) {
                $courseformatoptions['tab'.$i.'_title'] = array('default' => "Tab ".$i,'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
                $courseformatoptions['tab'.$i] = array('default' => "",'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
                $courseformatoptions['tab'.$i.'_sectionnums'] = array('default' => "",'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
            }

        }
        return $courseformatoptions;
    }

    public function words2numbers($string) {
        $numwords = array(
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine'
        );
        for ($i = 0; $i < 10; $i++) {
            $string = str_replace($numwords[$i], $i,$string);
        }
        return $string;
    }

    public function section_action($section, $action, $sr) {
        global $PAGE;

        $tcsettings = $this->get_format_options();
        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'topics2' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        if(strstr($action, 'movetotab')) {
            $action2 = $this->words2numbers($action);
            return $this->move2tab((int)str_replace('movetotab', '', $action2), $section, $tcsettings);
        } else {
            switch ($action) {
                case 'removefromtabs':
                    return $this->removefromtabs($PAGE->course, $section, $tcsettings);
                    break;
                case 'sectionzeroontop':
                    return $this->sectionzeroswitch($tcsettings, true);
                    break;
                case 'sectionzeroinline':
                    return $this->sectionzeroswitch($tcsettings, false);
                    break;
            }
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_topics2');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }

    // move section ID and section number to tab format settings of a given tab
    public function move2tab($tabnum, $section2move, $settings) {
        global $PAGE;
        global $DB;

        $course = $PAGE->course;

        // remove section number from all tab format settings
        $settings = $this->removefromtabs($course, $section2move, $settings);

        // add section number to new tab format settings if not tab0
        if($tabnum > 0){
            $settings['tab'.$tabnum] .= ($settings['tab'.$tabnum] === '' ? '' : ',').$section2move->id;
            $settings['tab'.$tabnum.'_sectionnums'] .= ($settings['tab'.$tabnum.'_sectionnums'] === '' ? '' : ',').$section2move->section;
            $this->update_course_format_options($settings);
        }
        return $settings;
    }

    // remove section id from all tab format settings
    public function removefromtabs($course, $section2remove, $settings) {
        global $CFG;
        global $DB;

        $max_tabs = ((isset($settings['maxtabs']) && $settings['maxtabs'] > 0) ? $settings['maxtabs'] : (isset($CFG->max_tabs) ? $CFG->max_tabs : 9));

        for($i = 0; $i <= $max_tabs; $i++) {
            if(strstr($settings['tab'.$i], $section2remove->id) > -1) {
                $sections = explode(',', $settings['tab'.$i]);
                $new_sections = array();
                foreach($sections as $section) {
                    if($section != $section2remove->id) {
                        $new_sections[] = $section;
                    }
                }
                $settings['tab'.$i] = implode(',', $new_sections);

                $section_nums = explode(',', $settings['tab'.$i.'_sectionnums']);
                $new_section_nums = array();
                foreach($section_nums as $section_num) {
                    if($section_num != $section2remove->section) {
                        $new_section_nums[] = $section_num;
                    }
                }
                $settings['tab'.$i.'_sectionnums'] = implode(',', $new_section_nums);
                $this->update_course_format_options($settings);
            }
        }
        return $settings;
    }

    // switch to show section0 always on top of the tabs
    public function sectionzeroswitch($settings, $value) {
        $settings['section0_ontop'] = $value;
        $this->update_course_format_options($settings);

        return $settings;
    }

    public function delete_section($section, $forcedeleteifnotempty = false) {
        global $DB;

        // Before we delete the section record we need it's ID to remove it from tabs after(!) a successful deletion
        $srec = $DB->get_record('course_sections', array('course' => $this->courseid, 'section' => $section));
        $sectionid = $srec->id;

        $what_parents_say = parent::delete_section($section, $forcedeleteifnotempty);
        if(!$what_parents_say) {
            return false;
        }

        // Remove sectionid and section(num) from tabs
        $this->remove_from_tabs($section, $sectionid);
        return $what_parents_say;
    }

    // Remove traces of a deleted section from tabs where needed
    public function remove_from_tabs($section = false, $sectionid = false) {
        global $DB;
        if(!$section || !$sectionid) {
            return false;
        }
        // Loop through the tabs
        $records = $DB->get_records('course_format_options', array('courseid' => $this->courseid, 'format' => $this->format));
        foreach($records as $option) {
            switch($option->name) {
                case 'tab1':
                case 'tab2':
                case 'tab3':
                case 'tab4':
                case 'tab5':
                case 'tab6':
                case 'tab7':
                case 'tab8':
                case 'tab9':
                if(strstr($option->value, $sectionid)) {
                    $this->remove_sectionid($option, $sectionid);
                }
                    break;
                case 'tab1_sectionnums':
                case 'tab2_sectionnums':
                case 'tab3_sectionnums':
                case 'tab4_sectionnums':
                case 'tab5_sectionnums':
                case 'tab6_sectionnums':
                case 'tab7_sectionnums':
                case 'tab8_sectionnums':
                case 'tab9_sectionnums':
                    if(strstr($option->value, $section)){
                        $this->remove_sectionnum($option, $section);
                    }
                    break;
            }
        }

    }

    // Remove the section ID from tabs
    public function remove_sectionid($option, $sectionid) {
        global $DB;
        $tabsections = explode(',',$option->value);
        $new_tabsections = array();
        foreach($tabsections as $tabsectionid) {
            if($tabsectionid !== $sectionid) {
                $new_tabsections[] = $tabsectionid;
            }
        }
        if(sizeof(array_diff($tabsections, $new_tabsections)) > 0) {
            $option->value = implode(',', $new_tabsections);
            $DB->update_record('course_format_options', $option);
        }
    }

    // Remove the section number from tabs
    public function remove_sectionnum($option, $sectionnum) {
        global $DB;
        $tabsectionnums = explode(',',$option->value);
        $new_tabsectionnums = array();
        foreach($tabsectionnums as $tabsectionnum) {
            if($tabsectionnum !== $sectionnum) {
                $new_tabsectionnums[] = $tabsectionnum;
            }
        }
        if(sizeof(array_diff($tabsectionnums, $new_tabsectionnums)) > 0) {
            $option->value = implode(',', $new_tabsectionnums);
            $DB->update_record('course_format_options', $option);
        }
    }

}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_topics2_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'topics2'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
    // deal with inplace changes of a tab name
    if ($itemtype === 'tabname') {
        global $DB, $PAGE;
        $courseid = key($_SESSION['USER']->currentcourseaccess);
        // the $itemid is actually the name of the record so use it to get the id

        // update the database with the new value given
        // Must call validate_context for either system, or course or course module context.
        // This will both check access and set current context.
        \external_api::validate_context(context_system::instance());
        // Check permission of the user to update this item.
//        require_capability('moodle/course:update', context_system::instance());
        // Clean input and update the record.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);
        $record = $DB->get_record('course_format_options', array('id' => $itemid), '*', MUST_EXIST);
        $DB->update_record('course_format_options', array('id' => $record->id, 'value' => $newvalue));

        // Prepare the element for the output ():
        $output = new \core\output\inplace_editable('format_topics2', 'tabname', $record->id,
            true,
            format_string($newvalue), $newvalue, 'Edit tab name',  'New value for ' . format_string($newvalue));

        return $output;
    }
}

