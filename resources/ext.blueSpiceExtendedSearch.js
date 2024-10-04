(function( mw, $, bs, d, undefined ){


	bs.extendedSearch = {
		_registerTrackableLinks: function () {
			$( '.bs-traceable-link' ).each( function () {
				var $a = $( this ),
					data = $a.data( 'bs-traceable-page' );

				function doTrack( $el ) {
					bs.extendedSearch._trackLink( data ).done( function() {
						window.location = $el.attr( 'href' );
					} ).fail ( function( e ) {
						console.error( e );
						window.location = $el.attr( 'href' );
					} );
				}
				if ( $a.hasClass( 'bs-recently-found-suggestion' ) ) {
					var ignoreButton = new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'close',
						classes: [ 'bs-extendedsearch-recentlyfound-ignore-button' ],
						title: mw.message( 'bs-extendedsearch-recentlyfound-ignore' ).plain(),
						data: data
					} );
					ignoreButton.connect( ignoreButton, {
						click: function() {
							this.setDisabled( true );
							bs.extendedSearch._untrackLink( data ).done( function() {
								bs.extendedSearch.Autocomplete._instance.focusSearchBox();
								this.$element.parent().fadeOut( 'normal', function() {
									$( this ).remove();
								} );
							}.bind( this ) ).fail ( function( e ) {
								console.error( e );
							} );
						}
					} );
					ignoreButton.$element.insertAfter( $a );
				}
				$a.on( 'click', function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					doTrack( $a );
				} );
				$a.parent().on( 'click', function ( e ) {
					if ( $( this ).hasClass( 'bs-extendedsearch-result-header-container' ) ) {
						return;
					}
					e.preventDefault();
					doTrack( $( this ).find( 'a.bs-traceable-link' ) );
				} );
			} );
		},
		_trackLink: function ( data ) {
			return bs.extendedSearch._rest( 'track', JSON.stringify( data ), 'POST' );
		},
		_untrackLink: function ( data ) {
			return bs.extendedSearch._rest( 'track', JSON.stringify( data ), 'DELETE' );
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
