define(['jquery', 'jqueryui'], function($) {
    /*eslint no-console: ["error", { allow: ["log", "warn", "error"] }] */
    return {
        init: function() {
// ---------------------------------------------------------------------------------------------------------------------
            var updateToggleSeq = function() {
                var toggleSeq = {};
                $("li.section").each(function() {
                    if (!$(this).find('.toggle_area').hasClass('hidden')) {
                        toggleSeq[$(this).attr('section-id')] = '1';
                    }
                });

                // Now write the sequence for this course into the user preference
                var courseid = $('#courseid').attr('courseid');
                $.ajax({
                    url: "format/topics2/ajax/update_toggles.php",
                    type: "POST",
                    data: {'courseid': courseid, 'toggle_seq': JSON.stringify(toggleSeq), 'sesskey': M.cfg.sesskey},
                    success: function(result) {
                        if (result !== '') {
                            console.log('New toggle sequence: ' + result);
                        }
                    }
                });

            };

// ---------------------------------------------------------------------------------------------------------------------
            // toggle a section content
            var toggleSection = function() {
                $(".toggler").on('click', function(event) {
                    if (event.altKey) {
                        console.log('ALT pressed...!');
                        if ($(this).hasClass('toggler_closed')) {
                            $('.toggler_open').show();
                            $('.toggler_closed').hide();
                            $('.toggle_area').removeClass('hidden').show();
                        } else {
                            $('.toggler_open').hide();
                            $('.toggler_closed').show();
                            $('.toggle_area').addClass('hidden').hide();
                            // Do not hide section 0
                            $('#section-0').find('.sectionbody').removeClass('hidden').show();
                        }
                    } else {
                        if ($(this).hasClass('toggler_closed')) {
                            $(this).parent().find('.toggler_open').show();
                            $(this).hide();
                            $(this).parent().parent().parent().find('.toggle_area').removeClass('hidden').show();
                        } else {
                            $(this).parent().find('.toggler_closed').show();
                            $(this).hide();
                            $(this).parent().parent().parent().find('.toggle_area').addClass('hidden').hide();
                        }
                    }
                    updateToggleSeq();
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            // toggle a section content
            var toggleSectionsOpen = function() {
                $("#btn_toggle_all_open").on('click', function() {
                    $('.toggler_closed').click();
                    updateToggleSeq();
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            // toggle a section content
            var toggleSectionsClose = function() {
                $("#btn_toggle_all_close").on('click', function() {
                    $('.toggler_open').click();
                    updateToggleSeq();
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var toggleAll = function() {
                $('#btn_open_all').on('click', function() {
                    $('.toggler_open').show();
                    $('.toggler_closed').hide();
                    $('.toggle_area').removeClass('hidden').show();
                    updateToggleSeq();
                });
                $('#btn_close_all').on('click', function() {
                    $('.toggler_open').hide();
                    $('.toggler_closed').show();
                    $('.toggle_area').addClass('hidden').hide();
                    // Do not hide section 0
                    $('#section-0').find('.sectionbody').removeClass('hidden').show();
                    updateToggleSeq();
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function() {
                // Load all required functions above
                toggleSection();
                toggleSectionsOpen();
                toggleSectionsClose();
                toggleAll();
            };

// ---------------------------------------------------------------------------------------------------------------------
            $(document).ready(function() {
                console.log('=================< topics2/toggle.js >=================');
                initFunctions();
            });
        }
    };
});
