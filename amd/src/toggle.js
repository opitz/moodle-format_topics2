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
                    url: "format/tabbedtopics/ajax/update_toggles.php",
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
            // rumdidumdidum
            var toggleSection = function() { $(".toggler").on('click', function() {
//                alert('toggler!');
                if ($(this).hasClass('toggler_closed')) {
//                    alert('now opening section');
                    $(this).parent().find('.toggler_open').show();
                    $(this).hide();
                    $(this).parent().parent().parent().find('.toggle_area').removeClass('hidden').show();
                } else {
//                    alert('now closing section');
                    $(this).parent().find('.toggler_closed').show();
                    $(this).hide();
                    $(this).parent().parent().parent().find('.toggle_area').addClass('hidden').hide();
                }

                // Now get the toggler status of each section
                updateToggleSeq();
            });};

            var toggleSection0 = function() { $(".toggler").on('click', function() {
                alert('toggler!');
                if ($(this).parent().parent().find('.toggle_area').hasClass('hidden')) {
                    $(this).parent().parent().find('.toggle_area').removeClass('hidden').show();
                    $(this).parent().parent().find('.toggler_closed').hide();
                    $(this).parent().parent().find('.toggler_open').show();
                } else {
                    $(this).parent().parent().find('.toggle_area').addClass('hidden').hide();
                    $(this).parent().parent().find('.toggler_open').hide();
                    $(this).parent().parent().find('.toggler_closed').show();
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

                console.log('=================< tabbedtopics/toggle.js >=================');
                initFunctions();

            });
        }
    };
});
