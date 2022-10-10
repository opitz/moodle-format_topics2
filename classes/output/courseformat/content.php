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
 * Output content for the format_pluginname plugin.
 *
 * @package   format_topics2
 * @copyright 2022, Matthias Opitz <opitz@gmx.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_topics2\output\courseformat;

use core_courseformat\base as base;
use core_courseformat\output\local\content as content_base;

class content extends content_base {

    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the course content is rendered.
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_topics2/local/content';
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE;
        $format = $this->format;

        $data = parent::export_for_template($output);
/*
        // Most formats uses section 0 as a separate section so we remove from the list.
        $sections = $this->export_sections($output);
        $initialsection = '';
        if (!empty($sections)) {
            $initialsection = array_shift($sections);
        }

        $data = (object)[
            'title' => $format->page_title(), // This method should be in the course_format class.
            'initialsection' => $initialsection,
            'sections' => $sections,
            'format' => $format->get_format(),
            'sectionreturn' => 0,
        ];

        // The single section format has extra navigation.
        $singlesection = $this->format->get_section_number();
        if ($singlesection) {
            if (!$PAGE->theme->usescourseindex) {
                $sectionnavigation = new $this->sectionnavigationclass($format, $singlesection);
                $data->sectionnavigation = $sectionnavigation->export_for_template($output);

                $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
                $data->sectionselector = $sectionselector->export_for_template($output);
            }
            $data->hasnavigation = true;
            $data->singlesection = array_shift($data->sections);
            $data->sectionreturn = $singlesection;
        }

        if ($this->hasaddsection) {
            $addsection = new $this->addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
        }
*/
        $data->tabs = $this->get_tabs();

        return $data;
    }

    public function get_tabs0() {
        global $COURSE;

        $format = $this->format;
//        $foptions = $this->get_format_options();
        $formatoptions = $this->get_formatoptions($COURSE->id);
        $tabs = [];
        $maxtabs = 5;

        for ($i = 0; $i < $maxtabs; $i++) {
            $tab = new \stdClass();
            $tab->tabno = $i + 1;
            $tab->id = 'tab_' . $tab->tabno;
            $tab->title = 'Tab ' . $tab->tabno;
            $tab->sections = '';

            $tabs[] = $tab;
        }

        return $tabs;
    }
    public function get_tabs() {

        $format = $this->format;
        $course = $format->get_course();
        $sections = $format->get_sections();
        //        $foptions = $this->get_format_options();
        $formatoptions = $this->get_formatoptions($course->id);
        $tabs = [];
        $maxtabs = ((isset($formatoptions['maxtabs']) &&
            $formatoptions['maxtabs'] > 0) ? $formatoptions['maxtabs'] : (isset($CFG->max_tabs) ? $CFG->max_tabs : 9));

        // Get the section IDs along with their section numbers.
        $sectionids = array();
        foreach ($sections as $section) {
            $sectionids[$section->section] = $section->id;
        }

        // Preparing the tabs.
        for ($i = 0; $i < $maxtabs; $i++) {
            $tabsections = '';
            $tabsectionnums = '';

            // Check section IDs and section numbers for tabs other than tab0.
            if ($i > 0) {
                if (isset($formatoptions['tab' . $i])) {
                    $tabsections = str_replace(' ', '', $formatoptions['tab' . $i]);
                } else {
                    $tabsections = '';
                }
                if (isset($formatoptions['tab' . $i. '_sectionnums'])) {
                    $tabsectionnums = str_replace(' ', '', $formatoptions['tab' . $i. '_sectionnums']);
                } else {
                    $tabsectionnums = '';
                }
//                $tabsections = $this->check_tab_section_ids($course->id, $sectionids, $tabsections, $tabsectionnums, $i);
            }

            $tab = (object) new \stdClass();
            if (isset($tab)) {
                $tab->id = "tab" . $i;
                $tab->name = "tab" . $i;
                $tab->generic_title = ($i === 0 ? get_string('tab0_generic_name', 'format_topics2') : 'Tab '.$i);
                $tab->title = (isset($formatoptions['tab' . $i . '_title']) &&
                $formatoptions['tab' . $i . '_title'] != '' ? $formatoptions['tab' . $i . '_title'] : $tab->generic_title);
                $tab->sections = $tabsections;
                $tab->section_nums = $tabsectionnums;
//                $tabs[$tab->id] = $tab;
                $tabs[] = $tab;
            }
        }
//        $this->tabs = $tabs;

        return $tabs;
    }

    public function get_formatoptions($courseid) {
        global $DB;
        $options = $DB->get_records('course_format_options', array('courseid' => $courseid));
        $formatoptions = array();
        foreach ($options as $option) {
            $formatoptions[$option->name] = $option->value;
        }
        return $formatoptions;
    }
}
