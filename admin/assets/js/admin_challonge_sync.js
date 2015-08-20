var wptmChallongeSync = {

    config: {
        wrapper: '.challonge-sync-container',
        testNotifySpinner: null,
        testNotifyResponse: null
    },

    init : function () {
        self.testNotifySpinner  = jQuery( wptmChallongeSync.config.wrapper + ' button .spinner' );
        self.testNotifyResponse = jQuery( '#challonge-sync-response' );
        this._bindEvents();

    },

    _bindEvents : function () {
        console.log(jQuery(wptmChallongeSync.config.wrapper).find('button'));
        jQuery(wptmChallongeSync.config.wrapper).find('button').on('click', wptmChallongeSync.ajaxGenerateMatches);
    },

    ajaxGenerateMatches : function (e) {

        e.preventDefault();

        self.testNotifyResponse.html('');
        self.testNotifySpinner.show();

        var xhr = jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            data: {
                action: 'challonge_resync',
                security: jQuery(wptmChallongeSync.config.wrapper).find('hidden').val(),
                tournament_id: jQuery( '#post_ID' ).val()
            },
            dataType: 'JSON',
            async: true
        });

        xhr.done( function( r ) {

            //magic line
            window.location.reload();

            self.testNotifyResponse.html( '<span style="color: green">' + r.data.message + '</span>' );
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

        wptmChallongeSync.init();

	});

}(jQuery));