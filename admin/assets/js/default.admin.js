(function ( $ ) {
	"use strict";

	$(function () {

		// Place your administration-specific JavaScript here
        $('#dashboard-widgets #postbox-container-3, #dashboard-widgets #postbox-container-4').remove();

        $('body').on('click', '.player-missing-pa-stats-id-email', function(){

            var $button = $(this);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                cache: false,
                data: {
                    action: 'player_missing_pa_stats_id',
                    player_id: $button.data('player-id'),
                    security: $button.data('security')
                },
                dataType: 'JSON',
                success: function (results) {

                    $button.slideUp(function(){
                        $button.parent().append('<span>Message Sent!</span>')
                    });



                }
            })

        });

        $('body').on('click', '#send-players-2-day-notification', function(){

            var $button = $(this);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                cache: false,
                data: {
                    action: 'tournament_2_day_notice',
                    security: $button.data('security'),
                    tournament_id: $button.data('tournament-id')
                },
                dataType: 'JSON',
                success: function (results) {
                    $button.parent().append('<div>Message Sent!</div>')
                }
            })

        });

        $('body').on('click', '#send-players-tournament-wrap-up', function(){

            var $button = $(this);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                cache: false,
                data: {
                    action: 'tournament_wrap_up',
                    security: $button.data('security'),
                    tournament_id: $button.data('tournament-id')
                },
                dataType: 'JSON',
                success: function (results) {
                    $button.parent().append('<div>Message Sent!</div>')
                }
            })

        });

	});

}(jQuery));