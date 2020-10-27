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
class behat_format_topics2 extends behat_course {

    /**
     * Moves the current section to the specified tab. You need to be in the course page and on editing mode.
     *
     * @Given /^I move section "(?P<sectionnumber_string>(?:[^"]|\\")*)" to tab "(?P<tabnumber_string>(?:[^"]|\\")*)"$/
     * @param string $sectionnumber
     * @param string $tabnumber
     * @throws \Behat\Mink\Exception\DriverException
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws coding_exception
     */
    public function i_move_section_to_tab($sectionnumber, $tabnumber) {
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

    /**
     * Opens a section edit menu if it is not already opened.
     *
     * @Given /^I open section "(?P<section_number>\d+)" edit menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $sectionnumber
     */
    public function i_open_section_edit_menu($sectionnumber) {
        if (!$this->running_javascript()) {
            throw new DriverException('Section edit menu not available when Javascript is disabled');
        }

        // Wait for section to be available, before clicking on the menu.
        $this->i_wait_until_section_is_available($sectionnumber);

        // If it is already opened we do nothing.
        $xpath = $this->section_exists($sectionnumber);
        $xpath .= "/descendant::div[contains(@class, 'section-actions')]/descendant::a[contains(@data-toggle, 'dropdown')]";

        $exception = new ExpectationException('Section "' . $sectionnumber . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
        $this->i_wait_until_section_is_available($sectionnumber);
    }

    /**
     * Waits until the section is available to interact with it. Useful when the section is performing an action and the section is overlayed with a loading layout.
     *
     * Using the protected method as this method will be usually
     * called by other methods which are not returning a set of
     * steps and performs the actions directly, so it would not
     * be executed if it returns another step.
     *
     * Hopefully we would not require test writers to use this step
     * and we will manage it from other step definitions.
     *
     * @Given /^I wait until section "(?P<section_number>\d+)" is available$/
     * @param int $sectionnumber
     * @return void
     */
    public function i_wait_until_section_is_available($sectionnumber) {

        // Looks for a hidden lightbox or a non-existent lightbox in that section.
        $sectionxpath = $this->section_exists($sectionnumber);
        $hiddenlightboxxpath = $sectionxpath . "/descendant::div[contains(concat(' ', @class, ' '), ' lightbox ')][contains(@style, 'display: none')]" .
            " | " .
            $sectionxpath . "[count(child::div[contains(@class, 'lightbox')]) = 0]";

        $this->ensure_element_exists($hiddenlightboxxpath, 'xpath_element');
    }


}
