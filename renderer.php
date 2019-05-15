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
 * Renderer for outputting the topics2 course format.
 *
 * @package format_topics2
 * @copyright 2012 Dan Poltawski / 2018 Matthias Opitz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */


defined('MOODLE_INTERNAL') || die();
//require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/course/format/topics/renderer.php');

/**
 * Basic renderer for topics2 format.
 * with added tab-ability
 *
 * @copyright 2018 Matthias Opitz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_topics2_renderer extends format_topics_renderer {

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $CFG, $DB, $PAGE;

        // Include the required JS files
        $this->require_js();

        $this->toggle_seq = $this->get_toggle_seq($course); // the toggle sequence for this user and course
        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $options = $DB->get_records('course_format_options', array('courseid' => $course->id));
        $format_options=array();
        foreach($options as $option) {
            $format_options[$option->name] =$option->value;
        }

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now on to the main stage..
        $numsections = course_get_format($course)->get_last_section_number();
        $sections = $modinfo->get_section_info_all();

        // add an invisible div that carries the course ID to be used by JS
        // add class 'single_section_tabs' when option is set so JS can play accordingly
        $class = ($format_options['single_section_tabs'] ? 'single_section_tabs' : '');

        // An invisible tag with the name of the course format to be used in jQuery
        echo html_writer::div($course->format, 'course_format_name', array('style' => 'display:none;'));

        echo html_writer::start_tag('div', array('id' => 'courseid', 'courseid' => $course->id, 'class' => $class));
        echo html_writer::end_tag('div');

        // display section-0 on top of tabs if option is checked
        echo $this->render_section0_ontop($course, $sections, $format_options, $modinfo);

        // the tab navigation
        $tabs = $this->prepare_tabs($course, $format_options, $sections);

        // rendering the tab navigation
        $rentabs = $this->render_tabs($format_options);
        echo $rentabs;

        // the sections
        echo $this->start_section_list();

        // Render the sections
        echo $this->render_sections($course, $sections, $format_options, $modinfo, $numsections);

        // Show hidden sections to users with update abilities only
        echo $this->render_hidden_sections($course, $sections, $context, $modinfo, $numsections);

        echo $this->end_section_list();

    }

    // Require the jQuery file for this class
    public function require_js() {
        $this->page->requires->js_call_amd('format_topics2/tabs', 'init', array());
        $this->page->requires->js_call_amd('format_topics2/toggle', 'init', array());
    }

    // Get the toggle sequence of a given course for the current user
    public function get_toggle_seq($course) {
        global $DB, $USER;

        $record = $DB->get_record('user_preferences', array('userid' => $USER->id, 'name' => 'toggle_seq_'.$course->id));
        return $record->value;
    }

//=====================================================< tabs >=========================================================
    // Prepare the tabs for rendering
    public function prepare_tabs($course, $format_options, $sections) {
        global $CFG, $DB, $PAGE;

        // prepare a maximum of 10 user tabs (0..9)
        $max_tabs = 9;
        $tabs = array();

        // preparing the tabs
        $count_tabs = 0;
        for ($i = 0; $i <= $max_tabs; $i++) {
            $tab_sections = '';
            $tab_section_nums = '';

            // check section IDs and section numbers for tabs other than tab0
            if($i > 0) {
                $tab_sections = str_replace(' ', '', $format_options['tab' . $i]);
                $tab_section_nums = str_replace(' ', '', $format_options['tab' . $i. '_sectionnums']);
                $section_ids = explode(',', $tab_sections);
                $section_nums = explode(',', $tab_section_nums);
                $tab_sections = $this->check_section_ids($course->id, $sections, $section_ids, $section_nums, $tab_sections, $tab_section_nums,$i);
            }

            $tab = new stdClass();
            $tab->id = "tab" . $i;
            $tab->name = "tab" . $i;
            $tab->title = $format_options['tab' . $i . '_title'];
            $tab->generic_title = ($i === 0 ? get_string('tab0_generic_name', 'format_topics2'):'Tab '.$i);
            $tab->sections = $tab_sections;
            $tab->section_nums = $tab_section_nums;
            $tabs[$tab->id] = $tab;
        }
        $this->tabs = $tabs;
        return $tabs;
    }

    // Render the tabs in sequence order if present or ascending otherwise
    public function render_tabs($format_options) {
        $o = html_writer::start_tag('ul', array('class'=>'tabs nav nav-tabs row'));

        $tab_seq = array();
        if ($format_options['tab_seq']) {
            $tab_seq = explode(',',$format_options['tab_seq']);
        }

        // show the tabs in the sequence
        foreach ($tab_seq as $tabid) {
            if (isset($this->tabs[$tabid]) && $tab = $this->tabs[$tabid]) {
                $o .= $this->render_tab($tab);
            }
        }
        // check if there are tabs that are not in the sequence (yet) - and if so display them now
        // we need to compare the sequence with the keys of the tabs array
        if($seq_diff = array_diff(array_keys($this->tabs),$tab_seq)){
            foreach ($seq_diff as $tabid) {
                if (isset($this->tabs[$tabid]) && $tab = $this->tabs[$tabid]) {
                    $o .= $this->render_tab($tab);
                }
            }
        }

        $o .= html_writer::end_tag('ul');

        return $o;
    }

    // Render a standard tab
    public function render_tab($tab) {
        global $DB, $PAGE, $OUTPUT;

        if(!isset($tab)) {
            return false;
        }

        $o = '';
        if($tab->sections == '') {
            $o .= html_writer::start_tag('li', array('class'=>'tabitem nav-item', 'style' => 'display:none;'));
        } else {
            $o .= html_writer::start_tag('li', array('class'=>'tabitem nav-item'));
        }

        $sections_array = explode(',', str_replace(' ', '', $tab->sections));
        if($sections_array[0]) {
            while ($sections_array[0] == "0") { // remove any occurences of section-0
                array_shift($sections_array);
            }
        }

        if($PAGE->user_is_editing()) {
            // get the format option record for the given tab - we need the id
            // if the record does not exist, create it first
            if(!$DB->record_exists('course_format_options', array('courseid' => $PAGE->course->id, 'name' => $tab->id.'_title'))) {
                $record = new stdClass();
                $record->courseid = $PAGE->course->id;
                $record->format = 'topics2';
                $record->section = 0;
                $record->name = $tab->id.'_title';
                $record->value = ($tab->id == 'tab0' ? get_string('tabzero_title', 'format_topics2') :'Tab '.substr($tab->id,3));
                $DB->insert_record('course_format_options', $record);
            }

            $format_option_tab = $DB->get_record('course_format_options', array('courseid' => $PAGE->course->id, 'name' => $tab->id.'_title'));
            $itemid = $format_option_tab->id;
        } else {
            $itemid = false;
        }

        if ($tab->id == 'tab0') {
            $o .= '<span
                data-toggle="tab" id="'.$tab->id.'"
                sections="'.$tab->sections.'"
                section_nums="'.$tab->section_nums.'"
                class="tablink nav-link "
                tab_title="'.$tab->title.'",
                generic_title = "'.$tab->generic_title.'"
                >';
        } else {
            $o .= '<span
                data-toggle="tab" id="'.$tab->id.'"
                sections="'.$tab->sections.'"
                section_nums="'.$tab->section_nums.'"
                class="tablink topictab nav-link "
                tab_title="'.$tab->title.'"
                generic_title = "'.$tab->generic_title.'"
                style="'.($PAGE->user_is_editing() ? 'cursor: move;' : '').'">';
        }
        // render the tab name as inplace_editable
        $tmpl = new \core\output\inplace_editable('format_topics2', 'tabname', $itemid,
            $PAGE->user_is_editing(),
            format_string($tab->title), $tab->title, get_string('tabtitle_edithint', 'format_topics2'),  get_string('tabtitle_editlabel', 'format_topics2', format_string($tab->title)));
        $o .= $OUTPUT->render($tmpl);
        $o .= "</span>";
        $o .= html_writer::end_tag('li');
        return $o;
    }

    // Check section IDs used in tabs and repair them if they have changed - most probably because a course was imported
    public function check_section_ids($courseid, $sections, $section_ids, $section_nums, $tab_sections, $tab_section_nums, $i) {
        global $DB;
        // check section IDs are valid for this course - and repair them using section numbers if they are not
        $tab_format_record = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => 'tab'.$i));
        $ids_have_changed = false;
        $new_section_nums = array();

        foreach($section_ids as $index => $section_id) {
            if(isset($sections[$section_id])) {
                if($section = $sections[$section_id]) {
                    $new_section_nums[] = $section->section;
                }
                if($section_id && !($section)) {
                    $section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $section_nums[$index]));
                    $tab_sections = str_replace($section_id, $section->id, $tab_sections);
                    $ids_have_changed = true;
                }
            }
        }

        if($ids_have_changed) {
            $DB->update_record('course_format_options', array('id' => $tab_format_record->id, 'value' => $tab_sections));
        }
        else { // all IDs are good - so check stored section numbers and restore them with the real numbers in case they have changed
            $new_sectionnums = implode(',', $new_section_nums);
            if($tab_section_nums !== $new_sectionnums) { // the stored section numbers seems to be different
                if($DB->record_exists('course_format_options', array('courseid' => $courseid, 'name' => 'tab'.$i.'_sectionnums'))) {
                    $tab_format_record = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => 'tab'.$i.'_sectionnums'));
                    $DB->update_record('course_format_options', array('id' => $tab_format_record->id, 'value' => $new_sectionnums));
                } else {
                    $new_tab_format_record = new \stdClass();
                    $new_tab_format_record->courseid = $courseid;
                    $new_tab_format_record->format = 'topics2';
                    $new_tab_format_record->sectionid = 0;
                    $new_tab_format_record->name = 'tab'.$i.'_sectionnums';
                    $new_tab_format_record->value = $new_sectionnums;
                    $DB->insert_record('course_format_options', $new_tab_format_record);
                }
            }
        }
        return $tab_sections;
    }

//=================================================< sections >=========================================================
    // display section-0 on top of tabs if option is checked
    public function render_section0_ontop($course, $sections, $format_options, $modinfo) {
        global $PAGE;
        $o = '';
        if($format_options['section0_ontop']) {
            $thissection = $sections[0];
            $o .= html_writer::start_tag('div', array('id' => 'ontop_area', 'class' => 'section0_ontop'));
            $o .= html_writer::start_tag('ul', array('id' => 'ontop_area', 'class' => 'topics'));
            $o .= $this->render_section($course, $thissection, $format_options);
        } else {
            $o .= html_writer::start_tag('div', array('id' => 'ontop_area'));
            $o .= html_writer::start_tag('ul', array('id' => 'ontop_area', 'class' => 'topics'));
        }

//        $o .= $this->end_section_list();
        $o .= html_writer::end_tag('div');
        return $o;
    }

    // Render the sections of a course
    public function render_sections($course, $sections, $format_options, $modinfo, $numsections){
        $o = '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">';
        foreach ($sections as $section => $thissection) {
            if ($section == 0) {
                $o .= html_writer::start_tag('div', array('id' => 'inline_area'));
                if($format_options['section0_ontop']){ // section-0 is already shown on top
                    $o .= html_writer::end_tag('div');
                    continue;
                }
                $o .= $this->render_section($course, $thissection, $format_options);
                $o .= html_writer::end_tag('div');
                continue;
            }
            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            $o .= $this->render_section($course, $thissection, $format_options);
        }
        return $o;
    }

    public function render_section($course, $section, $format_options) {
        global $PAGE;
        $o = '';
        if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            // Display section summary only.
            $o .= $this->section_summary($section, $course, null);
        } else {
            $o .= $this->section_header($section, $course, false, 0);

            // now the course modules for this section
            if ($section->uservisible) {
                $o .= $this->courserenderer->course_section_cm_list($course, $section, 0);
                $o .= $this->courserenderer->course_section_add_cm_control($course, $section->section, 0);
            }
            $o .= $this->section_footer();
        }
        return $o;
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;
        $o = '';
        $sectionstyle = '';
        $toggle_seq = str_split($this->toggle_seq);

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            }
            if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        // When rendering section-0 check if it is on top and adjust classes
        if($section->section === 0 && $course->section0_ontop) {
            $classes = 'section clearfix'; // On top is not main
        } else {
            $classes = 'section main clearfix';
        }

        // start the section
        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section, 'section-id' => $section->id,
            'class' => $classes.$sectionstyle, 'role'=>'region',
            'aria-label'=> get_section_name($course, $section)));

        // Create a span that contains the section title to be used to create the keyboard section move menu.
//        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => 'hidden sectionname'));

        // the left and right elements
        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));

        // start the content
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        // the sectionhead
        if($section->section !== 0 || $section->name !== '') {
            $o.= html_writer::start_tag('div', array('class' => 'sectionhead'));

            // the sectionname
            if(($section->section !== 0 || $section->name != '')) {
                $o .= html_writer::tag('h' . 3, $this->section_title($section, $course), array('class' => renderer_base::prepare_classes('sectionname')));
            }

            $o .= $this->section_availability($section);

            $o .= html_writer::end_tag('div'); // ending the sectionhead
        }

        // the sectionbody
        if($course->toggle && isset($toggle_seq[$section->section]) && $toggle_seq[$section->section] === '0' && ($section->section !== 0 || $section->name !== '')) {
            $o.= html_writer::start_tag('div', array('class' => 'sectionbody summary toggle_area hidden', 'style' => 'display: none;'));
        } else {
            $o.= html_writer::start_tag('div', array('class' => 'sectionbody summary toggle_area'));
        }
        if ($section->uservisible || $section->visible) {
            // Show summary if section is available or has availability restriction information.
            // Do not show summary if section is hidden but we still display it because of course setting
            $o .= $this->format_summary_text($section);
        }
        return $o;
    }

    // Section title either with toggle or straight
    public function section_title($section, $course) {
        if($course->toggle) {
            // prepare the toggle
            $toggle_seq = str_split($this->toggle_seq);
            if(isset($toggle_seq[$section->section]) && $toggle_seq[$section->section] === '1') {
                $toggler = '<i class="toggler toggler_open fas fa-chevron-down" style="cursor: pointer;"></i>';
                $toggler .= '<i class="toggler toggler_closed fas fa-chevron-right" style="cursor: pointer; display: none;"></i>';
            } else {
                $toggler = '<i class="toggler toggler_open fas fa-chevron-down" style="cursor: pointer; display: none;"></i>';
                $toggler .= '<i class="toggler toggler_closed fas fa-chevron-right" style="cursor: pointer;"></i>';
            }
            $toggler .= ' ';
        } else {
            $toggler = '';
        }

        return $toggler.$this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    // Render hidden sections for course editors only
    public function render_hidden_sections($course, $sections, $context, $modinfo, $numsections) {
        global $PAGE;
        $o ='<div class="testing"></div>';
        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($sections as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                $o .= $this->stealth_section_header($section);
                $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                $o .= $this->stealth_section_footer();
            }
            $o .= $this->change_number_sections($course, 0);
        }
        return $o;
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $DB, $CFG, $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $options = $DB->get_records('course_format_options', array('courseid' => $course->id));
        $format_options=array();
        foreach($options as $option) {
            $format_options[$option->name] =$option->value;
        }

        if(isset($format_options['maxtabs'])){
            $max_tabs = $format_options['maxtabs'];
        } else {
            // allow up to 5 tabs  by default if nothing else is set in the config file
            $max_tabs = (isset($CFG->max_tabs) ? $CFG->max_tabs : 5);
        }
        $max_tabs = ($max_tabs < 10 ? $max_tabs : 9 ); // Restrict tabs to 10 max (0...9)
        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();

        // add move to/from top for section0 only
        if ($section->section === 0) {
            $controls['ontop'] = array(
                "icon" => 't/up',
                'name' => 'Show always on top',

                'attr' => array(
                    'tabnr' => 0,
                    'class' => 'ontop_mover',
                    'title' => 'Show always on top',
                    'data-action' => 'sectionzeroontop'
                )
            );
            $controls['inline'] = array(
                "icon" => 't/down',
                'name' => 'Show inline',

                'attr' => array(
                    'tabnr' => 0,
                    'class' => 'inline_mover',
                    'title' => 'Show inline',
                    'data-action' => 'sectionzeroinline'
                )
            );
        }

        // Insert tab moving menu items
        $controls['no_tab'] = array(
            "icon" => 't/left',
            'name' => 'Remove from Tabs',

            'attr' => array(
                'tabnr' => 0,
                'class' => 'tab_mover',
                'title' => 'Remove from Tabs',
                'data-action' => 'removefromtabs'
            )
        );

        $itemtitle = "Move to Tab ";
        $actions = array('movetotabzero', 'movetotabone', 'movetotabtwo','movetotabthree','movetotabfour','movetotabfive','movetotabsix','movetotabseven','movetotabeight','movetotabnine','movetotabten', 'sectionzeroontop', 'sectionzeroinline');
        for($i = 1; $i <= $max_tabs; $i++) {
            $tabname = 'tab'.$i.'_title';
            $itemname = 'To Tab "'.($course->$tabname ? $course->$tabname : $i).'"';

            $controls['to_tab'.$i] = array(
                "icon" => 't/right',
                'name' => $itemname,

                'attr' => array(
                    'tabnr' => $i,
                    'class' => 'tab_mover',
                    'title' => $itemtitle,
                    'data-action' => $actions[$i]
                )
            );
        }

        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                    'name' => $highlightoff,
                    'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic,
                        'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                    'name' => $highlight,
                    'pixattr' => array('class' => '', 'alt' => $markthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic,
                        'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    protected function section_footer() {
        $o = html_writer::end_tag('div'); // ending the sectionbody
        $o .= html_writer::end_tag('div'); //ending the content
        $o .= html_writer::end_tag('li'); // ending the section

        return $o;
    }

}
