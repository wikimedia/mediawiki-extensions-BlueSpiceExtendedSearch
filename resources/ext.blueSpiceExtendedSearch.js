(function( mw, $, bs, d, undefined ){


	bs.extendedSearch = {
		_registerTrackableLinks: function () {
			$( '.bs-traceable-link' ).each( function () {
				var $a = $( this ),
					data = $a.data( 'bs-traceable-page' );

				$a.on( 'click', function ( e ) {
					e.preventDefault();
					var $el = $( this );
					bs.extendedSearch._trackLink( data ).done( function() {
						window.location = $el.attr( 'href' );
					} ).fail ( function( e ) {
						console.error( e );
						window.location = $el.attr( 'href' );
					} );

				} );
			} );
		},
		_trackLink: function ( data ) {
			return bs.extendedSearch._rest( 'track', JSON.stringify( data ), 'POST' );
		},
		_getRecentlyFound: function () {
			return bs.extendedSearch._rest( 'recentlyfound' );
		},
		_rest: function( path, data, method ) {
			data = data || {};
			var dfd = $.Deferred();

			$.ajax( {
				method: method,
				url: mw.util.wikiScript( 'rest' ) + '/bluespice/extendedsearch/' + path,
				data: data,
				contentType: "application/json",
				dataType: 'json',
			} ).done( function( response ) {
				if ( response.success === false ) {
					dfd.reject();
					return;
				}
				dfd.resolve( response );
			}.bind( this ) ).fail( function( jgXHR, type, status ) {
				if ( type === 'error' ) {
					dfd.reject( {
						error: jgXHR.responseJSON || jgXHR.responseText
					} );
				}
				dfd.reject( { type: type, status: status } );
			}.bind( this ) );

			return dfd.promise();
		}
	};
})( mediaWiki, jQuery, blueSpice, document );
