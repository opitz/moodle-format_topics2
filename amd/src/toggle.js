define(['jquery', 'jqueryui'], function($) {
    /*eslint no-console: ["error", { allow: ["log", "warn", "error"] }] */
    return {
        init: function() {
// ---------------------------------------------------------------------------------------------------------------------
            var updateToggleSeq = function() {
                console.log("no of sections = " + $('li.section').length);
                var toggle_seq = '';
                $("li.section").each(function() {
                    if ( $(this).find('.toggle_area').hasClass('hidden')) {
                        toggle_seq = toggle_seq + '0';
                    } else {
                        toggle_seq = toggle_seq + '1';
                    }
                });
                console.log('toggle_seq = ' + toggle_seq);

                // Now write the sequence for this course into the user preference
                var courseid = $('#courseid').attr('courseid');
                $.ajax({
                    url: "format/topics2/ajax/update_toggles.php",
                    type: "POST",
                    data: {'courseid': courseid, 'toggle_seq': toggle_seq},
                    success: function(result) {
                        if(result !== '') {
                            console.log('New toggle sequence: ' + result);
                        }
                    }
                });

            };

// ---------------------------------------------------------------------------------------------------------------------
            // toggle a section content
            var toggleSection = function() { $(".toggler").on('click', function() {
                if ($(this).hasClass('toggler_closed')) {
                    $(this).parent().find('.toggler_open').show();
                    $(this).hide();
                    $(this).parent().parent().parent().find('.toggle_area').removeClass('hidden').show();
                } else {
                    $(this).parent().find('.toggler_closed').show();
                    $(this).hide();
                    $(this).parent().parent().parent().find('.toggle_area').addClass('hidden').hide();
                }

                // Now get the toggler status of each section
                updateToggleSeq();
            });};



// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function() {
                // Load all required functions above
                toggleSection();
            };

// ---------------------------------------------------------------------------------------------------------------------
            $(document).ready(function() {
                console.log('=================< topics2/toggle.js >=================');
                initFunctions();
            });
        }
    };
});
