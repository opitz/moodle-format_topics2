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
 * Behat course-related steps definitions.
 *
 * @package    format_topics2
 * @category   test
 * @copyright  2020 Matthias Opitz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../../course/tests/behat/behat_course.php');

/**
 * Steps definitions related with putting sections under tabs.
 *
 * @copyright 2020 Matthias Opitz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_format_topics2 extends behat_base {

    /**
     * Deletes course section.
     *
     * @Given /^I gnupf section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber The section number
     */
    public function i_gnupf_section($sectionnumber) {
        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = 'format_topics2';
        if (get_string_manager()->string_exists('deletesection', $courseformat)) {
            $strdelete = get_string('deletesection', $courseformat);
        } else {
            $strdelete = get_string('deletesection');
        }

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on delete link.
        $this->execute('behat_general::i_click_on_in_the',
            array($strdelete, "link", $this->escape($xpath), "xpath_element")
        );

    }

    /**
     * Moves course section to a tab.
     *
     * @Given /^I move section "(?P<section_number>\d+)" to tab "(?P<tab_number>\d+)"$/
     * @param int $sectionnumber The section number
     */
    public function i_move_section_to_tab($sectionnumber, $tabnumber) {
        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = 'format_topics2';
        /*
        if (get_string_manager()->string_exists('deletesection', $courseformat)) {
            $strdelete = get_string('deletesection', $courseformat);
        } else {
            $strdelete = get_string('deletesection');
        }
        */
        $strdelete = get_string('deletesection', $courseformat);
        $strtotab = get_string('totab', 'format_topics2');
        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on delete link.
//        $this->execute('behat_general::i_click_on_in_the',
//            array($strdelete, "link", $this->escape($xpath), "xpath_element")
//        );

        // Click on move to tab link.
        $this->execute('behat_general::i_click_on_in_the',
            array($strtotab.$tabnumber, "link", $this->escape($xpath), "xpath_element")
        );

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);
//            $this->i_wait_until_section_is_available($sectionnumber);
        }
    }

    /**
     * Moves the current section to the specified tab. You need to be in the course page and on editing mode.
     *
     * @Given /^I xxxx section "(?P<section_number>\d+)" to tabxxx "(?P<tab_number>\d+)"$/
     * @param int $sectionnumber
     * @param int $tabnumber
     * @throws \Behat\Mink\Exception\DriverException
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws coding_exception
     */
    public function i_xxxx_section_to_tabxx($sectionnumber, $tabnumber) {
        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);
        $strtotab = get_string('totab', 'format_topics2');

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on move to tab link.
        $this->execute('behat_general::i_click_on_in_the',
            array($strtotab.$tabnumber, "link", $this->escape($xpath), "xpath_element")
        );

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);
            $this->i_wait_until_section_is_available($sectionnumber);
        }
    }

    /**
     * Checks if the course section exists.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     * @return string The xpath of the section.
     */
    protected function section_exists($sectionnumber) {

        // Just to give more info in case it does not exist.
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $exception = new ElementNotFoundException($this->getSession(), "Section $sectionnumber ");
        $this->find('xpath', $xpath, $exception);

        return $xpath;
    }

}
