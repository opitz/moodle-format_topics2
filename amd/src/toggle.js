define(['jquery', 'jqueryui'], function($) {
    /* eslint no-console: ["error", { allow: ["log", "warn", "error"] }] */
    return {
        init: function() {

            /**
             * Update the toggles settings per user
             */
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
                            console.log('Updated toggle sequence: ' + result);
                        }
                    }
                });

            };

            /**
             * Toggle a section content
             */
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

            /**
             * Toggle a section content open
             */
            var toggleSectionsOpen = function() {
                $("#btn_toggle_all_open").on('click', function() {
                    $('.toggler_closed').click();
                    updateToggleSeq();
                });
            };

            /**
             * Toggle a section content close
             */
            var toggleSectionsClose = function() {
                $("#btn_toggle_all_close").on('click', function() {
                    $('.toggler_open').click();
                    updateToggleSeq();
                });
            };

            /**
             * Toggle all sections
             */
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

            /**
             * Initialize all functions
             */
            var initFunctions = function() {
                toggleSection();
                toggleSectionsOpen();
                toggleSectionsClose();
                toggleAll();
            };


            /**
             * The document is ready
             */
            $(document).ready(function() {
                console.log('=================< topics2/toggle.js >=================');
                initFunctions();
            });
        }
    };
});
