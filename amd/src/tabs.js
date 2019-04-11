define(['jquery', 'jqueryui'], function($) {
    /*eslint no-console: ["error", { allow: ["log", "warn", "error"] }] */
    return {
        init: function() {
// ---------------------------------------------------------------------------------------------------------------------
            // When a single section is shown under a tab use the section name as tab name
            var changeTab = function(tab, target) {
                 console.log('single section in tab: using section name as tab name');

                // Replace the tab name with the section name
                var origSectionname = target.find('.sectionname:not(.hidden)');
                if ($('.tabname_backup:visible').length > -1) {
                    var theSectionname = target.attr('aria-label');
                    tab.parent().append(tab.clone().addClass('tabname_backup').hide()); // Create a hidden clone of tab name
                    tab.html(theSectionname).addClass('tabsectionname');

                    // Hide the original sectionname when not in edit mode
                    if ($('.inplaceeditable').length === 0) {
                        origSectionname.hide();
                        target.find('.sectionhead').hide();
                    } else {
                        origSectionname.addClass('edit_only');
                        target.find('.hidden.sectionname').hide();
                        target.find('.section-handle').hide();
                    }
                }
            };

// ---------------------------------------------------------------------------------------------------------------------
            // A section name is updated...
            $(".section").on('updated', function() {
                var newSectionname = $(this).find('.inplaceeditable').attr('data-value');
                $(this).attr('aria-label', newSectionname);
                $('.tablink.active').click();
            });

// ---------------------------------------------------------------------------------------------------------------------
            // Restore the tab name
            var restoreTab = function(tab) {
                // Restore the tab name from the backup
                var theBackup = tab.parent().find('.tabname_backup');
                var theTab = tab.parent().find('.tabsectionname').removeClass('tabsectionname');
                theTab.html(theBackup.html());
                theBackup.remove();

                // Reveal the original sectionname
                $('.sectionname').removeClass('edit_only').show();
                $('.hidden.sectionname').show();
                $('.section-handle').show();

                 console.log('--> restoring section headline ');
            };

// ---------------------------------------------------------------------------------------------------------------------
            // react to a clicked tab
            var tabClick = function() { $(".tablink").on('click', function() {
                var tabid = $(this).attr('id');
                var sections = $(this).attr('sections');
                var sectionArray = sections.split(",");

                console.log('----');

                // Make this an active tab
                $(".tablink.active").removeClass("active"); // First remove any active class from tabs
                $(this).addClass('active'); // Then add the active class to the clicked tab

                var clickedTabName;
                if ($(this).find('.inplaceeditable-text')) {
                    clickedTabName = $(this).find('.inplaceeditable-text').attr('data-value');
                }
                if (typeof clickedTabName == 'undefined') {
                    clickedTabName = $(this).html();
                }
                 console.log('Clicked tab "'+clickedTabName+'":');

                if (tabid === 'tab0') { // Show all sections - then hide each section shown in other tabs
                    $("#changenumsections").show();
                    $("li.section").show();
                    $(".topictab:visible").each(function() {
                        if ($(this).attr('sections').length > 0) {
                            // If any split sections into an array, loop through it and hide section with the found ID
                            $.each($(this).attr('sections').split(","), function(index, value) {
                                var target = $(".section[section-id='"+value+"']");
                                target.hide();
                                 console.log("--> hiding section " + value);
                            });
                        }
                    });
                } else { // Hide all sections - then show those found in sectionArray
                    $("#changenumsections").show();
                    $("li.section").hide();
                    $.each(sectionArray, function(index, value) {
                        var target = $(".section[section-id='" + value + "']");
                        target.show();
                        console.log("--> showing section " + value);
                    });
                }

                // Show section-0 always when it should be shown always
                $('#ontop_area #section-0').show();

                var visibleSections=$('li.section:visible').length;
                var hiddenSections=$('li.section.hidden:visible').length;
                if ($('.section0_ontop').length > 0) {
                   console.log('section0 is on top - so reducing the number of visible sections for this tab by 1');
                    visibleSections--;
                }
                console.log('number of visible sections: '+visibleSections);
                console.log('number of hidden sections: '+hiddenSections);

                // If all visible sections are hidden for students the tab is hidden for them as well
                // in this case mark the tab for admins so they are aware
                if (visibleSections <= hiddenSections) {
                    $(this).addClass('hidden-tab');
                    console.log("==> marking hidden tab "+tabid);
                    var self = $(this);
                    require(['core/str'], function(str) {
                        var getTheString = str.get_string('hidden_tab_hint', 'format_tabbedtopics');
                        $.when(getTheString).done(function(theString) {
                            self.find('#not-shown-hint-' + tabid).remove();
                            var theAppendix = '<i id="not-shown-hint-'+tabid+'" class="fa fa-info" title="'+theString+'"></i>';
                            if ($('.tablink .fa-pencil').length > 0) { // When in edit mode ...
                                self.find('.inplaceeditable').append(theAppendix);
                            } else {
                                self.append(theAppendix);
                            }
                        });
                    });
                } else {
                    $(this).removeClass('hidden-tab');
                    $('#not-shown-hint-'+tabid).remove();
                }

                if (visibleSections < 1) {
                    console.log('tab with no visible sections - hiding it');
                    $(this).parent().hide();

                    // Restoring generic tab name
                    var courseid = $('#courseid').attr('courseid');
                    var genericTitle = $(this).attr('generic_title');
                    $.ajax({
                        url: "format/tabbedtopics/ajax/update_tab_name.php",
                        type: "POST",
                        data: {'courseid': courseid, 'tabid': tabid, 'tab_name': genericTitle},
                        success: function(result) {
                            if(result !== '') {
                               console.log('Reset name of tab ID ' + tabid + ' to "' + result + '"');
                                $('[data-itemid=' + result + ']').attr('data-value', genericTitle).
                                find('.quickeditlink').html(genericTitle);
                            }
                        }
                    });
                } else {
                    console.log('tab with visible sections - showing it');
                    $(this).parent().show();
                }

                // If option is set and when a tab other than tab 0 shows a single section perform some visual tricks
                if ($('.single_section_tabs').length  > 0 && tabid !== 'tab0') {
                    var target = $('li.section:visible:not(.hidden)').first();
                    // If section0 is shown always on top ignore the first visible section and use the 2nd
                    if ($('.section0_ontop').length > 0) {
                        target = $('li.section:visible:not(.hidden):eq(1)');
                    }
                    var firstSectionId = target.attr('id');

                    if (visibleSections - hiddenSections <= 1 && firstSectionId !== 'section-0'
//                        && !$('li.section:visible').first().hasClass('hidden')
//                        && !$('li.section:visible').first().hasClass('stealth')
                    ) {
                        changeTab($(this), target);
                        // Make sure the content is un-hidden
                        target.find('.toggle_area').removeClass('hidden').show();
                    } else if ($('.inplaceeditable').length > 0 && firstSectionId !== 'section-0') {
                        restoreTab($(this));
                    }
                }

                // If tab0 is alone hide it
                if (tabid === 'tab0' && $('.tabitem:visible').length === 1) {
                    // X console.log('--> tab0 is a single tab - hiding it');
                    $('.tabitem').hide();
                }
            });};

// ---------------------------------------------------------------------------------------------------------------------
            // Moving a section to a tab by menu
            var tabMove = function() { $(".tab_mover").on('click', function() {
                var tabnum = $(this).attr('tabnr'); // This is the tab number where the section is moved to
                var sectionid = $(this).closest('li.section').attr('section-id');
                var sectionnum = $(this).closest('li.section').attr('id').substring(8);

                // X console.log('--> found section num: '+sectionnum);
                var activeTabId = $('.topictab.active').first().attr('id');

                if (typeof activeTabId == 'undefined') {
                    activeTabId = 'tab0';
                }
                // X console.log('----');
                // X console.log('moving section '+sectionid+' from tab "'+activeTabId+'" to tab nr '+tabnum);

                // Remove the section id and section number from any tab
                $(".tablink").each(function() {
                    $(this).attr('sections', $(this).attr('sections').replace("," + sectionid, ""));
                    $(this).attr('sections', $(this).attr('sections').replace(sectionid + ",", ""));
                    $(this).attr('sections', $(this).attr('sections').replace(sectionid, ""));

                    $(this).attr('section_nums', $(this).attr('section_nums').replace("," + sectionnum, ""));
                    $(this).attr('section_nums', $(this).attr('section_nums').replace(sectionnum + ",", ""));
                    $(this).attr('section_nums', $(this).attr('section_nums').replace(sectionnum, ""));
                });
                // Add the sectionid to the new tab
                if (tabnum > 0) { // No need to store section ids for tab 0
                    if ($("#tab"+tabnum).attr('sections').length === 0) {
                        $("#tab"+tabnum).attr('sections', $("#tab"+tabnum).attr('sections')+sectionid);
                    } else {
                        $("#tab"+tabnum).attr('sections', $("#tab"+tabnum).attr('sections')+","+sectionid);
                    }
                    if ($("#tab"+tabnum).attr('section_nums').length === 0) {
                        $("#tab"+tabnum).attr('section_nums', $("#tab"+tabnum).attr('section_nums')+sectionnum);
                    } else {
                        $("#tab"+tabnum).attr('section_nums', $("#tab"+tabnum).attr('section_nums')+","+sectionnum);
                        // X console.log('---> section_nums: '+$("#tab"+tabnum).attr('section_nums'));
                    }
                }
                $("#tab"+tabnum).click();
                $('#'+activeTabId).click();

                // Restore the section before moving it in case it was a single
                restoreTab($('#tab'+tabnum));

                // If the last section of a tab was moved click the target tab
                // otherwise click the active tab to refresh it
                var countableSections = $('li.section:visible').length-($("#ontop_area").hasClass('section0_ontop') ? 1 : 0);
                // X console.log('---> visible sections = '+$('li.section:visible').length);
                // X console.log('---> countableSections = '+countableSections);
                if (countableSections > 0 && $('li.section:visible').length >= countableSections) {
                    // X console.log('staying with the current tab (id = '+activeTabId+
                    // X   ') as there are still '+$('li.section:visible').length+' sections left');
                    $("#tab"+tabnum).click();
                    $('#'+activeTabId).click();
                } else {
                    // X console.log('no section in active tab id '+
                    // X   activeTabId+' left - hiding it and following section to new tab nr '+tabnum);
                    $("#tab"+tabnum).click();
                    $('#'+activeTabId).parent().hide();
                }
            });};

// ---------------------------------------------------------------------------------------------------------------------
            // Moving section0 to the ontop area
            var moveOntop = function() {
                $(".ontop_mover").on('click', function() {
                    $("ul#ontop_area").append($(this).closest('.section')).addClass('section0_ontop');
//                    $("#ontop_area").addClass('section0_ontop');
                    $("#section-0").removeClass('main');
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            // Moving section0 back into line with others
            var moveInline = function() {
                $(".inline_mover").on('click', function() {
                    var sectionid = $(this).closest('.section').attr('section-id');
                    $("#inline_area").append($(this).closest('.section'));
                    $("#section-0").addClass('main');
                    // Remove the 'section0_ontop' class
                    $('.section0_ontop').removeClass('section0_ontop');
                    // Find the former tab for section0 if any and click it
                    $(".tablink").each(function() {
                        if ($(this).attr('sections').indexOf(sectionid) > -1) {
                            $(this).click();
                            return false;
                        }
                        return false;
                    });
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            // A section edit menu is clicked
            // hide the the current tab from the tab move options of the section edit menu
            // if this is section0 do some extra stuff
            var dropdownToggle = function() { $(".menubar").on('click', function() {
                if ($(this).parent().parent().hasClass('section_action_menu')) {
                    var sectionid = $(this).closest('.section').attr('id');
                    $('#' + sectionid + ' .tab_mover').show(); // 1st show all options
                    // replace all tabnames with the current names shown in tabs
                    // Get the current tab names
                    var tabArray = [];
                    var trackIds = []; // tracking the tab IDs so to use each only once
                    $('.tablink').each(function() {
                        if (typeof $(this).attr('id') !== 'undefined') {
                            var tabname = '';
                            var tabid = $(this).attr('id').substr(3);
                            if ($(this).hasClass('tabsectionname')) {
                                tabname = $(this).html();
                            } else {
                                tabname = $(this).find('.inplaceeditable').attr('data-value');
                            }
                            if ($.inArray(tabid,trackIds) < 0) {
                                if ($(this).hasClass('hidden-tab')) { // If this is a hidden tab remove all garnish from the name
                                    tabname = $(this).find('a').clone();
                                    tabname.find('span.quickediticon').remove();
                                    tabname = $.trim(tabname.html());
                                }
                                tabArray[tabid] = tabname;
                                trackIds.push(tabid);
                            }
                        }
                    });

                    // Updating menu options with current tab names
                    // X console.log('--> Updating menu options with current tab names');
                    $(this).parent().find('.tab_mover').each(function() {
                        var tabnr = $(this).attr('tabnr');
                        var tabtext = $(this).find('.menu-action-text').html();
                        console.log(tabnr + ' --> ' + tabtext + ' ==> ' + tabArray[tabnr]);
                        $(this).find('.menu-action-text').html('To Tab "' + tabArray[tabnr] +
                            ( (tabArray[tabnr] === 'Tab ' + tabnr || tabnr === '0') ? '"' : '" (Tab ' + tabnr + ')'));
                    });
                    if (sectionid === 'section-0') {
                        if ($('#ontop_area.section0_ontop').length === 1) { // If section0 is on top don't show tab options
                            $("#section-0 .inline_mover").show();
                            $("#section-0 .tab_mover").addClass('tab_mover_bak').removeClass('tab_mover').hide();
                            $("#section-0 .ontop_mover").hide();
                        } else {
                            $("#section-0 .inline_mover").hide();
                            $("#section-0 .tab_mover_bak").addClass('tab_mover').removeClass('tab_mover_bak').show();
                            $("#section-0 .ontop_mover").show();
                        }
                    } else if (typeof $('.tablink.active').attr('id') !== 'undefined') {
                        var tabnum = $('.tablink.active').attr('id').substring(3);
                        $('#' + sectionid + ' .tab_mover[tabnr="' + tabnum+'"]').hide(); // Then hide the one not needed
                        // X console.log('hiding tab ' + tabnum + ' from edit menu for section '+sectionid);
                    }
                    if( $('.tablink:visible').length === 0) {
                        $('#' + sectionid + ' .tab_mover[tabnr="0"]').hide();
                    }
                }
            });};

// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function() {
                // Load all required functions above
                tabClick();
                tabMove();
                moveOntop();
                moveInline();
                dropdownToggle();
            };

// ---------------------------------------------------------------------------------------------------------------------
            // what to do if a tab has been dropped onto another
            var handleTabDropEvent = function( event, ui ) {
                var draggedTab = ui.draggable.find('.topictab').first();
                var targetTab = $(this).find('.topictab').first();

// For development purposes only - not used in production environments
                var draggedTab_id = draggedTab.attr('id');
                var targetTab_id = targetTab.attr('id');
                console.log('The tab with ID "' + draggedTab_id + '" was dropped onto tab with the ID "' + targetTab_id + '"');

                // Swap both tabs
                var zwischenspeicher = draggedTab.parent().html();
                draggedTab.parent().html(targetTab.parent().html());
                targetTab.parent().html(zwischenspeicher);

                // Re-instantiate the clickability for the just added DOM elements
                initFunctions();

                // Get the new tab sequence and write it back to format options
                var tabSeqA = [];

                // Get the id of each tab according to their position (left to right)
                $('.tablink').each(function() {
                    var tabid = $(this).attr('id');
                    if (typeof tabid !== 'undefined') {
                        if (!tabSeqA.includes(tabid)) {
                            tabSeqA.push(tabid);
                        }
                    }
                });

                // Implode the array to a string
                var tabSeq = tabSeqA.join();

                // Finally call php to write the data
                var courseid = $('#courseid').attr('courseid');
                $.ajax({
                    url: "format/tabbedtopics/ajax/update_tab_seq.php",
                    type: "POST",
                    data: {'courseid': courseid, 'tab_seq': tabSeq},
//                    success: function() {
                    success: function(result) {
                        console.log('the new tab sequence: ' + result);
                    }});
            };

// ---------------------------------------------------------------------------------------------------------------------
            // A link to an URL is clicked - check if there is a section ID in it and if so reveal the corresponding tab
            $("a").click(function() {
                if ($(this).attr('href') !== '#') {
                    var sectionid = $(this).attr('href').split('#')[1];
                    // If the link contains a section ID (e.g. is NOT undefined) click the corresponding tab
                    if (typeof sectionid !== 'undefined') {
                        var sectionnum = $('#' + sectionid).attr('section-id');
                        // Find the tab in which the section is
                        var foundIt = false;
                        $('.tablink').each(function() {
                            if ($(this).attr('sections').indexOf(sectionnum) > -1) {
                                $(this).click();
                                foundIt = true;
                                return false;
                            }
                        });
                        if (!foundIt) {
                            $('#tab0').click();
                        }
                    }
                }
            });

// ---------------------------------------------------------------------------------------------------------------------
            $(document).ready(function() {
                initFunctions();

                console.log('=================< tabbedtopics/tabs.js >=================');
                // Show the edit menu for section-0
                $("#section-0 .right.side").show();

                // Make tabs draggable when in edit mode (the pencil class is present)
                if ($('.inplaceeditable').length > 0) {

                    $('.topictab').parent().draggable({
                        cursor: 'move',
                        stack: '.tabitem', // Make sure the dragged tab is always on top of others
                        revert: true,
                    });
                    $('.topictab').parent().droppable({
                        accept: '.tabitem',
                        hoverClass: 'hovered',
                        drop: handleTabDropEvent,
                    });
                }

                // If there are visible tabs click them all once to potentially reveal any section names as tab names
                if ($(".topictab:visible").length > 0) {
                    $('#tab0').click();
                    // click ALL tabs once
                    $('.tablink:visible').click();
                    // click the 1st visible tab by default
                    $('.tablink:visible').first().click();
                }

                // If section0 is on top restrict section menu - restore otherwise
                if ($("#ontop_area").hasClass('section0_ontop')) {
                    $("#section-0 .inline_mover").show();
                    $("#section-0 .tab_mover").hide();
                    $("#section-0 .ontop_mover").hide();
                } else {
                    $("#section-0 .inline_mover").hide();
                    $("#section-0 .tab_mover").show();
                    $("#section-0 .ontop_mover").show();
                }
            });
        }
    };
});
