var wptmGenerateMatchUI = {

    config: {
        wrapper: '.generate-match-container',
        testNotifySpinner: null,
        testNotifyResponse: null
    },

    init : function(config) {
        config.testNotifySpinner  = jQuery( config.wrapper + ' button .spinner' );
        config.testNotifyResponse = jQuery( '#generate-match-response' );
        this._bindEvents();

    },

    _bindEvents : function () {
        jQuery(wptmGenerateMatchUI.config.wrapper).find('button').on('click', wptmGenerateMatchUI.ajaxGenerateMatches);
    },

    ajaxGenerateMatches : function () {

        self.testNotifyResponse.html('');
        self.testNotifySpinner.show();

        var xhr = $.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            data: {
                action: 'generate_tournament_matches',
                security: jQuery(wptmGenerateMatchUI.config.wrapper).find('hidden').val()
            },
            dataType: 'JSON'
        });

        xhr.done( function( r ) {

            //magic line
            P2PAdmin.boxes['YOUR_CONNECTION_TYPE'].candidates.sync()

            self.testNotifyResponse.html( '<span style="color: green">OK</span>' );
            self.testNotifySpinner.hide();
        } );

        xhr.fail( function( xhr, textStatus ) {
            var message = textStatus;
            if ( typeof xhr.responseJSON === 'object' ) {
                if ( 'data' in xhr.responseJSON && typeof xhr.responseJSON.data === 'string' ) {
                    message = xhr.responseJSON.data;
                }
            } else if ( typeof xhr.statusText === 'string' ) {
                message = xhr.statusText;
            }
            self.testNotifyResponse.html( '<span style="color: red">' + message + '</span>' );
            self.testNotifySpinner.hide();
        } );

    }


};


(function ( $ ) {
	"use strict";

	$(function () {



	});

}(jQuery));