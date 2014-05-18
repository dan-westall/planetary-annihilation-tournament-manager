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



                }
            })

        });

	});

}(jQuery));