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

/**
 * The content class.
 *
 * @package   format_topics2
 * @copyright 2022, Matthias Opitz <opitz@gmx.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {
    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the course content is rendered.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_topics2/local/content';
    }

    /**
     * Export this data, so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        $data = parent::export_for_template($output);
        $data->tabs = $this->get_tabs();

        return $data;
    }

    /**
     * Get the tabs.
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_tabs() {

        $format = $this->format;
        $course = $format->get_course();
        $sections = $format->get_sections();
        $formatoptions = $this->get_formatoptions($course->id);
        $tabs = [];
        $maxtabs = ((isset($formatoptions['maxtabs']) &&
            $formatoptions['maxtabs'] > 0) ? $formatoptions['maxtabs'] : (isset($CFG->max_tabs) ? $CFG->max_tabs : 5));

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
                $tabsections = $this->check_tab_section_ids($course->id, $sectionids, $tabsections, $tabsectionnums, $i);
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
                $tabs[] = $tab;
            }
        }
        return $tabs;
    }

    /**
     * Get the format options for a course.
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public function get_formatoptions($courseid) {
        global $DB;
        $options = $DB->get_records('course_format_options', array('courseid' => $courseid));
        $formatoptions = array();
        foreach ($options as $option) {
            $formatoptions[$option->name] = $option->value;
        }
        return $formatoptions;
    }

    /**
     * Check section IDs used in tabs and repair them if they have changed - most probably because a course was imported.
     *
     * @param int $courseid
     * @param array|stdClass $sectionids
     * @param array|stdClass $tabsectionids
     * @param array|stdClass $tabsectionnums
     * @param int $i
     * @return array|string
     * @throws dml_exception
     */
    protected function check_tab_section_ids($courseid, $sectionids, $tabsectionids, $tabsectionnums, $i) {
        global $DB;
        $idhaschanged = false;

        $newtabsectionids = array();
        $newtabsectionnums = array();
        $tabformatrecordids = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => 'tab'.$i));
        $tabformatrecordnums = $DB->get_record('course_format_options',
            array('courseid' => $courseid, 'name' => 'tab'.$i.'_sectionnums')
        );

        if ($tabsectionids != "") {
            $tabsectionids = explode(',', $tabsectionids);
        } else {
            $tabsectionids = array();
        }

        if ($tabsectionnums != "") {
            $tabsectionnums = explode(',', $tabsectionnums);
        } else {
            $tabsectionnums = array();
        }

        foreach ($tabsectionids as $key => $tabsectionid) {
            if (!in_array($tabsectionid, $sectionids) && isset($tabsectionnums[$key]) &&
                isset($sectionids[$tabsectionnums[$key]])) {
                // The tab_section_id is not among the (new) section ids of that course.
                // This is most likely because the course has been restored - so use the sectionnums to determine the new id.
                $newtabsectionids[] = $sectionids[$tabsectionnums[$key]];
                $idhaschanged = true;
                // Preserve the backup sequence of sectionnums.
                $newtabsectionnums[] = $tabsectionnums[$key];
            } else {
                // The tab_section_id IS part of the section ids of that course and will be preserved.
                $newtabsectionids[] = $tabsectionid;
                // Create a backup sequence of sectionnums from section IDs to use it in the correction scheme above after a backup.
                $newtabsectionnums[] = array_search($tabsectionid, $sectionids);
            }
        }

        $tabsectionids = implode(',', $newtabsectionids);
        $tabsectionnums = implode(',', $newtabsectionnums);
        if ($idhaschanged) {
            $DB->update_record('course_format_options', array('id' => $tabformatrecordids->id, 'value' => $tabsectionids));
        }
        if ($tabformatrecordnums && $tabsectionnums != $tabformatrecordnums->value) {
            // If the tab nums of that tab have changed update them.
            $DB->update_record('course_format_options', array('id' => $tabformatrecordnums->id, 'value' => $tabsectionnums));
        }

        return $tabsectionids;
    }


}
