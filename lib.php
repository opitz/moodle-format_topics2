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
 * Class format_topics2
 *
 * @package    format_topics2
 * @copyright  2018 Matthias Opitz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
defined('COURSE_DISPLAY_COLLAPSE') || define('COURSE_DISPLAY_COLLAPSE', 2); // Legacy support - no longer used.
defined('COURSE_DISPLAY_NOCOLLAPSE') || define('COURSE_DISPLAY_NOCOLLAPSE', 3);
require_once($CFG->dirroot. '/course/format/topics/lib.php');

/**
 * Main class for the topics2 course format with added tab-ability
 *
 * @package    format_topics2
 * @copyright  2018 Matthias Opitz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_topics2 extends format_topics {

    /**
     * Add some options
     *
     * @param bool $foreditform
     * @return array|bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function course_format_options($foreditform = false) {
        global $CFG, $COURSE, $DB;
        $fo = $DB->get_records('course_format_options', array('courseid' => $COURSE->id));
        $formatoptions = array();
        foreach ($fo as $o) {
            $formatoptions[$o->name] = $o->value;
        }

        $maxtabs = (
            (isset($formatoptions['maxtabs']) &&
            $formatoptions['maxtabs'] > 0) ? $formatoptions['maxtabs'] : (isset($CFG->max_tabs) ? $CFG->max_tabs : 9));
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseformatoptions = array(
                'maxtabs' => array(
                    'label' => get_string('maxtabs_label', 'format_topics2'),
                    'help' => 'maxtabs',
                    'help_component' => 'format_topics2',
                    'default' => (isset($CFG->max_tabs) ? $CFG->max_tabs : 5),
                    'type' => PARAM_INT,
                ),
                'limittabname' => array(
                    'label' => get_string('limittabname_label', 'format_topics2'),
                    'help' => 'limittabname',
                    'help_component' => 'format_topics2',
                    'default' => 0,
                    'type' => PARAM_INT,
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
                            COURSE_DISPLAY_NOCOLLAPSE => get_string('coursedisplay_nocollapse', 'format_topics2'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
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

            // The sequence in which the tabs will be displayed.
            $courseformatoptions['tab_seq'] = array(
                'default' => '',
                'type' => PARAM_TEXT,
                'label' => '',
                'element_type' => 'hidden'
            );

            // Now loop through the tabs but don't show them as we only need the DB records...
            $courseformatoptions['tab0_title'] = array(
                'default' => get_string('tabzero_title',
                'format_topics2'),
                'type' => PARAM_TEXT,
                'label' => '',
                'element_type' => 'hidden',
                );
            $courseformatoptions['tab0'] = array('default' => "",
                'type' => PARAM_TEXT,
                'label' => '',
                'element_type' => 'hidden'
            );

            for ($i = 1; $i <= $maxtabs; $i++) {
                $courseformatoptions['tab'.$i.'_title'] = array(
                    'default' => "Tab ".$i,
                    'type' => PARAM_TEXT,
                    'label' => '',
                    'element_type' => 'hidden'
                    );
                $courseformatoptions['tab'.$i] = array('default' => "",
                    'type' => PARAM_TEXT,
                    'label' => '',
                    'element_type' => 'hidden'
                );
                $courseformatoptions['tab'.$i.'_sectionnums'] = array(
                    'default' => "",
                    'type' => PARAM_TEXT,
                    'label' => '',
                    'element_type' => 'hidden'
                );
            }

        }
        return $courseformatoptions;
    }

    /**
     * Turning numbers into text
     *
     * @param string $string
     * @return mixed
     */
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
            $string = str_replace($numwords[$i], $i, $string);
        }
        return $string;
    }

    /**
     * Tab action for sections
     *
     * @param stdClass|section_info $section
     * @param string $action
     * @param int $sr
     * @return array|stdClass|null
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        $tcsettings = $this->get_format_options();
        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'topics2' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        if (strstr($action, 'movetotab')) {
            $action2 = $this->words2numbers($action);
            return $this->move2tab((int)str_replace('movetotab', '', $action2), $section, $tcsettings);
        } else {
            switch ($action) {
                case 'removefromtabs':
                    return $this->removefromtabs($section, $tcsettings);
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

    /**
     * Move section ID and section number to tab format settings of a given tab.
     *
     * @param int $tabnum
     * @param array|stdClass $section2move
     * @param array|stdClass $settings
     * @return mixed
     */
    public function move2tab($tabnum, $section2move, $settings) {

        // Remove section number from all tab format settings.
        $settings = $this->removefromtabs($section2move, $settings);

        // Add section number to new tab format settings if not tab0.
        if ($tabnum > 0) {
            $settings['tab'.$tabnum] .= ($settings['tab'.$tabnum] === '' ? '' : ',').$section2move->id;
            $settings['tab'.$tabnum.'_sectionnums'] .= ($settings['tab'.$tabnum.'_sectionnums'] === '' ? '' : ',').
                $section2move->section;
            $this->update_course_format_options($settings);
        }
        return $settings;
    }

    /**
     * Remove section id from all tab format settings.
     *
     * @param array|stdClass $section2remove
     * @param array|stdClass $settings
     * @return mixed
     */
    public function removefromtabs($section2remove, $settings) {
        global $CFG;

        $maxtabs = ((isset($settings['maxtabs']) &&
            $settings['maxtabs'] > 0) ? $settings['maxtabs'] : (isset($CFG->max_tabs) ? $CFG->max_tabs : 9)
        );

        for ($i = 0; $i <= $maxtabs; $i++) {
            if (strstr($settings['tab'.$i], $section2remove->id) > -1) {
                $sections = explode(',', $settings['tab'.$i]);
                $newsections = array();
                foreach ($sections as $section) {
                    if ($section != $section2remove->id) {
                        $newsections[] = $section;
                    }
                }
                $settings['tab'.$i] = implode(',', $newsections);

                $sectionnums = explode(',', $settings['tab'.$i.'_sectionnums']);
                $newsectionnums = array();
                foreach ($sectionnums as $sectionnum) {
                    if ($sectionnum != $section2remove->section) {
                        $newsectionnums[] = $sectionnum;
                    }
                }
                $settings['tab'.$i.'_sectionnums'] = implode(',', $newsectionnums);
                $this->update_course_format_options($settings);
            }
        }
        return $settings;
    }

    /**
     * Switch to show section0 always on top of the tabs.
     *
     * @param array|stdClass $settings
     * @param string $value
     * @return mixed
     */
    public function sectionzeroswitch($settings, $value) {
        $settings['section0_ontop'] = $value;
        $this->update_course_format_options($settings);

        return $settings;
    }

    /**
     * Delete a section
     *
     * @param int|section_info|stdClass $section
     * @param bool $forceifnotempty
     * @return bool
     * @throws dml_exception
     */
    public function delete_section($section, $forceifnotempty = false) {
        global $DB;

        // Before we delete the section record we need it's ID to remove it from tabs after(!) a successful deletion.
        $srec = $DB->get_record('course_sections', array('course' => $this->courseid, 'section' => $section));
        $sectionid = $srec->id;

        $whatparentssay = parent::delete_section($section, $forceifnotempty);
        if (!$whatparentssay) {
            return false;
        }

        // Remove sectionid and section(num) from tabs.
        $this->remove_from_tabs($section, $sectionid);
        return $whatparentssay;
    }

    /**
     * Remove traces of a deleted section from tabs where needed.
     *
     * @param bool $section
     * @param bool $sectionid
     * @return bool
     * @throws dml_exception
     */
    public function remove_from_tabs($section = false, $sectionid = false) {
        global $DB;
        if (!$section || !$sectionid) {
            return false;
        }
        // Loop through the tabs.
        $records = $DB->get_records('course_format_options', array('courseid' => $this->courseid, 'format' => $this->format));
        foreach ($records as $option) {
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
                    if (strstr($option->value, $sectionid)) {
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
                    if (strstr($option->value, $section)) {
                        $this->remove_sectionnum($option, $section);
                    }
                    break;
            }
        }

    }

    /**
     * Remove the section ID from tabs.
     *
     * @param array|stdClass $option
     * @param int $sectionid
     * @throws dml_exception
     */
    public function remove_sectionid($option, $sectionid) {
        global $DB;
        $tabsections = explode(',', $option->value);
        $newtabsections = array();
        foreach ($tabsections as $tabsectionid) {
            if ($tabsectionid !== $sectionid) {
                $newtabsections[] = $tabsectionid;
            }
        }
        if (count(array_diff($tabsections, $newtabsections)) > 0) {
            $option->value = implode(',', $newtabsections);
            $DB->update_record('course_format_options', $option);
        }
    }

    /**
     * Remove the section number from tabs.
     *
     * @param array|stdClass $option
     * @param int $sectionnum
     * @throws dml_exception
     */
    public function remove_sectionnum($option, $sectionnum) {
        global $DB;
        $tabsectionnums = explode(',', $option->value);
        $newtabsectionnums = array();
        foreach ($tabsectionnums as $tabsectionnum) {
            if ($tabsectionnum !== $sectionnum) {
                $newtabsectionnums[] = $tabsectionnum;
            }
        }
        if (count(array_diff($tabsectionnums, $newtabsectionnums)) > 0) {
            $option->value = implode(',', $newtabsectionnums);
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
    // Deal with inplace changes of a tab name.
    if ($itemtype === 'tabname') {
        global $DB;
        // The $itemid is actually the name of the record so use it to get the id.

        // Update the database with the new value given.
        // Must call validate_context for either system, or course or course module context.

        // This will both check access and set current context.
        \external_api::validate_context(context_system::instance());

        // Clean input and update the record.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);
        $record = $DB->get_record('course_format_options', array('id' => $itemid), '*', MUST_EXIST);
        $DB->update_record('course_format_options', array('id' => $record->id, 'value' => $newvalue));

        // Prepare the element for the output ().
        $output = new \core\output\inplace_editable('format_topics2', 'tabname', $record->id,
            true,
            format_string($newvalue), $newvalue, 'Edit tab name',  'New value for ' . format_string($newvalue));

        return $output;
    }
}

