// Import selectors
import Selectors from './selectors';

export const init = () => {
    initFunctions();

//    Const tabs = document.getElementsByClassName("topictab");
    // Click each topic tab once to activate their status.
    document.getElementsByClassName("topictab").forEach(function(tab) {
        tab.click();
    });

    checkTabs();
    document.getElementById("tab0").click();

};

/**
 * Initialize all functions
 */
var initFunctions = function() {
    // Load all required functions.
    tabClick();
};

/**
 * React to a clicked topic tab
 */
var tabClick = function() {
    document.addEventListener('click', e => {
        if (e.target.closest(Selectors.actions.topicTab)) {
            var tabId = e.target.id;
            var tabSections = e.target.getAttribute('sections');

            // Select the clicked tab.
            selectTab(e.target);

            // When topic tab 0 is clicked show all sections, then hide all sections under other tabs
            if (tabId == "tab0") {
                // Show all sections.
                document.getElementsByClassName("section").forEach(function(section) {
                    section.style.display = "block";
                });
                // Now hide all sections that are under other visible tabs.
                var topicTabs = document.getElementsByClassName("topictab");
                topicTabs.forEach(function(tab) {
                    if (window.getComputedStyle(tab).display != "none") {
                        var otherTabSections = tab.getAttribute('sections');
                        if (otherTabSections) {
                            otherTabSections.split(",").forEach(function(sectionId) {
                                document.getElementById("section-" + sectionId).style.display = "none";
                            });
                        }
                    }
                });
            } else { // Hide all sections, then show all sections under the selected tab.
                if (tabSections) {
                    // Hide all sections.
                    document.getElementsByClassName("section").forEach(function(section) {
                        section.style.display = "none";
                    });
                    // Now show all sections found for the clicked topic tab.
                    tabSections.split(",").forEach(function(sectionId) {
                        document.getElementById("section-" + sectionId).style.display = "block";
                    });
                } else {
                    // Since the tab does not contain any sections hide it.
                    e.target.style.display = "none";
                }
            }
        }
    });
};

/**
 * Select a tab by removing the 'selected' class from the active tab(s) (if any) and re-apply it to the given tab.
 *
 * @param {Object=} selectedTab
 */
var selectTab = function(selectedTab) {
    // There should only be one selected tab at a time - but you never know...
    const selectedTabs = document.getElementsByClassName("selected");

    // Remove the 'selected' class
    for (let tab of selectedTabs) {
        tab.classList.remove("selected");
    }
    // Add the 'selected' class to the given tab.
    selectedTab.classList.add("selected");

};

/**
 * Check if any tabs are visible. If not hide tab 0 as well otherwise show it.
 */
var checkTabs = function() {
    // If there is NO visible topic tab then hide topic tab 0 as well.
    var topicTabs = document.getElementsByClassName("topictab");
    var visibleTabs = 0;
    topicTabs.forEach(function(tab) {
        if (window.getComputedStyle(tab).display != "none") {
            visibleTabs++;
        }
    });

    if (visibleTabs === 1) {
        document.getElementById('tab0').style.display = "none";
    } else {
        document.getElementById('tab0').style.display = "inline";
    }
};