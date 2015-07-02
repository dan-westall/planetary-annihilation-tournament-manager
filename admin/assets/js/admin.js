var wptmGenerateMatchUI = {

    config: {
        wrapper: '.generate-match-container',
        testNotifySpinner: null,
        testNotifyResponse: null
    },

    init : function () {
        self.testNotifySpinner  = jQuery( wptmGenerateMatchUI.config.wrapper + ' button .spinner' );
        self.testNotifyResponse = jQuery( '#generate-match-response' );
        this._bindEvents();

    },

    _bindEvents : function () {
        console.log(jQuery(wptmGenerateMatchUI.config.wrapper).find('button'));
        jQuery(wptmGenerateMatchUI.config.wrapper).find('button').on('click', wptmGenerateMatchUI.ajaxGenerateMatches);
    },

    ajaxGenerateMatches : function (e) {

        e.preventDefault();

        self.testNotifyResponse.html('');
        self.testNotifySpinner.show();

        console.log(ajaxurl);

        var xhr = jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            data: {
                action: 'ajax_generate_tournament_matches',
                security: jQuery(wptmGenerateMatchUI.config.wrapper).find('hidden').val(),
                tournament_id: post_id
            },
            dataType: 'JSON',
            async: true
        });

        xhr.done( function( r ) {

            console.log('done')
            console.log(r)

            //magic line
            P2PAdmin.boxes['tournament_matches'].candidates.sync()

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

        wptmGenerateMatchUI.init();

	});

}(jQuery));