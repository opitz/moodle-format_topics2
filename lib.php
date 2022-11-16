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
 * @copyright  2018-22 Matthias Opitz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
defined('COURSE_DISPLAY_COLLAPSE') || define('COURSE_DISPLAY_COLLAPSE', 2); // Legacy support - no longer used.
defined('COURSE_DISPLAY_NOCOLLAPSE') || define('COURSE_DISPLAY_NOCOLLAPSE', 3);

/**
 * Main class for the topics2 course format with added tab-ability
 *
 * @package    format_topics2
 * @copyright  2018-22 Matthias Opitz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_topics2 extends core_courseformat\base {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections(): bool {
        return true;
    }

    /**
     * Uses course index
     *
     * @return bool
     */
    public function uses_course_index(): bool {
        return true;
    }

    /**
     * Uses indentation.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the topics2 course format.
     *
     * If the section number is 0, it will show no section title .
     * If the section number is not 0, the base implementation of course_format::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_topics');
        } else {
            // Use course_format::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('topicoutline');
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = $course->coursedisplay ?? COURSE_DISPLAY_SINGLEPAGE;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else {
                if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Supporting components.
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }

    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Topics2 format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
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
            $formatoptions['maxtabs'] > 0) ? $formatoptions['maxtabs'] : (isset($CFG->max_tabs) ? $CFG->max_tabs : 5));

        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ],
                'coursedisplay' => [
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ],
                'maxtabs' => [
                    'type' => PARAM_INT,
                ],
                'limittabname' => [
                    'type' => PARAM_INT,
                ],
            ];
            // The topic tabs.
            for ($i = 0; $i < $maxtabs; $i++) {
                $courseformatoptions['tab'.$i.'_title'] = array(
                    'type' => PARAM_TEXT,
                    'label' => '',
                    'element_type' => 'hidden'
                );
                $courseformatoptions['tab'.$i] = array(
                    'type' => PARAM_TEXT,
                    'label' => '',
                    'element_type' => 'hidden'
                );
                $courseformatoptions['tab'.$i.'_sectionnums'] = array(
                    'type' => PARAM_TEXT,
                    'label' => '',
                    'element_type' => 'hidden'
                );
            }
        }

        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseformatoptionsedit = [
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        ],
                    ],
                ],
                'coursedisplay' => [
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        ],
                    ],
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ],
                // The sequence in which the tabs will be displayed.
                'tab_seq' => array(
                    'default' => '',
                    'type' => PARAM_TEXT,
                    'label' => '',
                    'element_type' => 'hidden'
                ),
            ];

            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);

            $courseformatoptions['maxtabs'] = array(
                'default' => (isset($CFG->max_tabs) ? $CFG->max_tabs : 5),
                'type' => PARAM_INT,
                'label' => get_string('maxtabs_label', 'format_topics2'),
                'help' => 'maxtabs',
                'help_component' => 'format_topics2',
            );

            $courseformatoptions['limittabname'] = array(
                'default' => 0,
                'type' => PARAM_INT,
                'label' => get_string('limittabname_label', 'format_topics2'),
                'help' => 'limittabname',
                'help_component' => 'format_topics2',
                'type' => PARAM_INT,
            );

            $courseformatoptions['section0_ontop'] = array(
                'default' => 0,
                'type' => PARAM_INT,
                'label' => get_string('section0_label', 'format_topics2'),
                'help' => 'section0',
                'help_component' => 'format_topics2',
                'element_type' => 'hidden',
            );

            $courseformatoptions['single_section_tabs'] = array(
                'label' => get_string('single_section_tabs_label', 'format_topics2'),
                'help' => 'single_section_tabs',
                'help_component' => 'format_topics2',
                'element_type' => 'advcheckbox',
            );

            // Now add the tabs but don't show them as we only need the DB records...
            $courseformatoptions['tab0_title'] = array(
                'default' => get_string('tabzero_title',
                    'format_topics2'),
                'type' => PARAM_TEXT,
                'label' => '',
                'element_type' => 'hidden',
            );
            $courseformatoptions['tab0'] = array(
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
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        return $elements;
    }

    /**
     * Updates format options for a course.
     *
     * In case the course format was changed to 'topics2', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from moodleform::get_data() or array with data
     * @param stdClass $oldcourse if this function is called from update_course()
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use course_can_delete_section()
     *
     * @param stdClass $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
                                                         $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_topics');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_topics', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
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
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
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

        if (!($section instanceof section_info)) {
            $modinfo = course_modinfo::instance($this->courseid);
            $section = $modinfo->get_section_info($section->section);
        }
        $elementclass = $this->get_output_classname('content\\section\\availability');
        $availability = new $elementclass($this, $section);

        $rv['section_availability'] = $renderer->render($availability);
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
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
class format_topics2xxx extends core_courseformat\base {

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
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ),
                'defaultcollapse' => array(
                    'label' => get_string('defaultcollapse', 'format_topics2'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => get_string('defaultcollapsed', 'format_topics2'),
                            1 => get_string('defaultexpanded', 'format_topics2'),
                            2 => get_string('alwaysexpanded', 'format_topics2')
                        )
                    ),
                    'help' => 'defaultcollapse',
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
 * This method is required for inplace section name editor.
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

