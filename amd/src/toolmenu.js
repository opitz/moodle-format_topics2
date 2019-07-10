define(['jquery', 'core/modal_factory', 'core/modal_events'], function($, ModalFactory, ModalEvents) {
    /* eslint no-console: ["error", { allow: ["log", "warn", "error"] }] */
    return {
        init: function() {


// ---------------------------------------------------------------------------------------------------------------------
            var prepare_help_button = function() {
                var helpText = "";
                $('.tool_menu_button').each(function() {
                    if ($(this).attr('title').length > 0) {
                        helpText = helpText + $(this).html() + ' = ' + $(this).attr('title') + '<br>';
                    }
                });
                var trigger = $("#btn_help");

                ModalFactory.create({
                    title: 'Tool Menu Help',
                    body: '<p>'+helpText+'</p>',
//                type: ModalFactory.types.CANCEL
                    footer: 'gnupf'
                }, trigger)
                    .done(function(modal) {
                        var $root = modal.getRoot();
                        $root.on(ModalEvents.save, function () { // Handle clicking
//                        just do nothing really
                        });
                    });

            };
// ---------------------------------------------------------------------------------------------------------------------
            // Scroll the page to the top
            var goToTop = function() {
                $('#btn_top').on('click', function(){
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            // Toggle the tool menu
            var toggleToolMenu = function() {
                var passiveWidth = $('#reveal_tool_menu_area').css('width');
                var activeWidth = $('#reveal_tool_menu_area').attr('tool_menu_width');
                $('#reveal_tool_menu_area').hover(function() {
                    $('#reveal_tool_menu_area').css('width', activeWidth);
                    $('#fixed_tool_menu').animate({width: activeWidth});
                }, function() {
                    $('#reveal_tool_menu_area').css('width', passiveWidth);
                    $('#fixed_tool_menu').delay(500).animate({width: 0});
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var test = function() {
                $('#btn_test').on('click', function() {
                    console.log('==> btn_test was pressed! ');

                });

                $('#btn_test_reset').on('click', function() {
                    console.log('==> btn_test_reset was pressed! ');
                });

            };

// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function() {
                // Load all required functions above
                prepare_help_button();
                goToTop();
                toggleToolMenu();
                test();
            };

// _____________________________________________________________________________________________________________________
            $(document).ready(function() {
                console.log('=================< topics2/toolmenu.js >=================');
                initFunctions();
            });
        }
    };
});
