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
     * Moves course section to a tab.
     *
     * @Given /^I move section "(?P<section_number>\d+)" to tab "(?P<tab_number>\d+)"$/
     * @param int $sectionnumber The section number
     */
    public function i_move_section_to_tab($sectionnumber, $tabnumber) {
        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // Get the text for the menu item to click
        $strtotab = get_string('totab', 'format_topics2');

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on move to tab menu item.
        // If the tabnumber is 0 the menu item is somehow different
        if ($tabnumber == 0) {
            $strtab0 = get_string('tab0_generic_name', 'format_topics2');
            $this->execute('behat_general::i_click_on_in_the',
                array($strtotab.'"'.$strtab0.'"', "link", $this->escape($xpath), "xpath_element")
            );
        }else {
            $this->execute('behat_general::i_click_on_in_the',
                array($strtotab.$tabnumber, "link", $this->escape($xpath), "xpath_element")
            );
        }

//        if ($this->running_javascript()) {
//            $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);
//        }
    }

    /**
     * Opens a section edit menu if it is not already opened.
     *
     * @Given /^I open section "(?P<section_number>\d+)" edit menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $sectionnumber
     */
    protected function i_open_section_edit_menu($sectionnumber) {
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
//        $this->i_wait_until_section_is_available($sectionnumber);
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
    protected function i_wait_until_section_is_available($sectionnumber) {

        // Looks for a hidden lightbox or a non-existent lightbox in that section.
        $sectionxpath = $this->section_exists($sectionnumber);
        $hiddenlightboxxpath = $sectionxpath . "/descendant::div[contains(concat(' ', @class, ' '), ' lightbox ')][contains(@style, 'display: none')]" .
            " | " .
            $sectionxpath . "[count(child::div[contains(@class, 'lightbox')]) = 0]";

        $this->ensure_element_exists($hiddenlightboxxpath, 'xpath_element');
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
     * Checks if the tab exists.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $tabnumber
     * @return string The xpath of the tab.
     */
    protected function tab_exists($tabnumber) {

        // Just to give more info in case it does not exist.
        $xpath = "//span[@id='tab" . $tabnumber . "']";
        $exception = new ElementNotFoundException($this->getSession(), "Tab $tabnumber ");
        $this->find('xpath', $xpath, $exception);

        return $xpath;
    }

    /**
     * Click on the tab with the specified tab number
     *
     * @Then /^I click on tab "(?P<tab_number>\d+)"$/
     */
    public function i_click_on_tab($tabnumber) {
        $selector = '#tab'.$tabnumber;
        $this->i_click_on_element($selector);
    }

    /**
     * Click in the given DOM element
     *
     * @Then /^I click on element "([^"]*)"$/
     */
    public function i_click_on_element($selector)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', $selector);

        if (empty($element)) {
            throw new Exception("No html element found for the selector ('$selector')");
        }

        $element->click();
    }

    /**
     * Swapping two tabs
     *
     * @Given /^I swap tab "(?P<movingtab_number>\d+)" with tab "(?P<targettab_number>\d+)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param int $movingtabnumber The number of the moving tab
     * @param int $targettabnumber The number of the target tab
     */
    public function i_swap_tab_with_tab($movingtabnumber, $targettabnumber) {
        if (!$this->running_javascript()) {
            throw new DriverException('Section edit menu not available when Javascript is disabled');
        }

        // Ensure the moving tab is valid
//        $movingtabnode = $this->get_tab_element($movingtabnumber);
        $movingtabxpath = $this->tab_exists($movingtabnumber);

        // Ensure the destination is valid.
        $targettabxpath = $this->tab_exists($targettabnumber);
//        $destinationxpath = $targettabxpath . "//li[contains(concat(' ', normalize-space(@class), ' '), ' ui-droppable ')]";

        $this->execute("behat_general::i_drag_and_i_drop_it_in",
            array($this->escape($movingtabxpath), "xpath_element",
                $this->escape($targettabxpath), "xpath_element")
        );
    }

}
