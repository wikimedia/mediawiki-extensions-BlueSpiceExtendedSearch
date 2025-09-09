( function ( mw, $, bs ) {

	bs.extendedSearch = {
		_registerTrackableLinks: function () {
			$( '.bs-traceable-link' ).each( function () {
				const $a = $( this ),
					data = $a.data( 'bs-traceable-page' );

				function openLink( url, inNew ) {
					if ( inNew ) {
						window.open( url, '_blank' );
					} else {
						window.location = url;
					}
				}
				function doTrack( e ) {
					if ( mw.user.isAnon() ) {
						openLink( $a.attr( 'href' ), shouldOpenInNew( e ) );
						return;
					}
					bs.extendedSearch._trackLink( data ).always( () => {
						openLink( $a.attr( 'href' ), shouldOpenInNew( e ) );
					} );
				}

				function shouldOpenInNew( e ) {
					return e.ctrlKey || e.metaKey;
				}

				if ( $a.hasClass( 'bs-recently-found-suggestion' ) ) {
					const ignoreButton = new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'close',
						classes: [ 'bs-extendedsearch-recentlyfound-ignore-button' ],
						title: mw.message( 'bs-extendedsearch-recentlyfound-ignore' ).plain(),
						data: data,
						tabIndex: -1
					} );
					ignoreButton.connect( ignoreButton, {
						click: function () {
							this.setDisabled( true );
							bs.extendedSearch._untrackLink( data ).done( () => {
								bs.extendedSearch.Autocomplete._instance.focusSearchBox();
								this.$element.parent().fadeOut( 'normal', function () { // eslint-disable-line no-jquery/no-fade
									$( this ).remove();
								} );
							} ).fail( ( e ) => {
								console.error( e ); // eslint-disable-line no-console
							} );
						}
					} );
					ignoreButton.$element.insertAfter( $a );
				}
				$a.on( 'click', ( e ) => {
					e.preventDefault();
					e.stopPropagation();
					doTrack( e );
				} );
				$a.parent().on( 'click', function ( e ) {
					if ( $( this ).hasClass( 'bs-extendedsearch-result-header-container' ) ) {
						return;
					}
					e.preventDefault();
					doTrack( e );
				} );
			} );
		},
		_trackLink: function ( data ) {
			mw.hook( 'bs-extendedsearch-track-link' ).fire( data );
			return bs.extendedSearch._rest( 'track', JSON.stringify( data ), 'POST' );
		},
		_untrackLink: function ( data ) {
			return bs.extendedSearch._rest( 'track', JSON.stringify( data ), 'DELETE' );
		},
		_getRecentlyFound: function () {
			return bs.extendedSearch._rest( 'recentlyfound' );
		},
		_rest: function ( path, data, method ) {
			data = data || {};
			const dfd = $.Deferred();

			$.ajax( {
				method: method,
				url: mw.util.wikiScript( 'rest' ) + '/bluespice/extendedsearch/' + path,
				data: data,
				contentType: 'application/json',
				dataType: 'json'
			} ).done( ( response ) => {
				if ( response.success === false ) {
					dfd.reject();
					return;
				}
				dfd.resolve( response );
			} ).fail( ( jgXHR, type, status ) => {
				if ( type === 'error' ) {
					dfd.reject( {
						error: jgXHR.responseJSON || jgXHR.responseText
					} );
				}
				dfd.reject( { type: type, status: status } );
			} );

			return dfd.promise();
		}
	};
}( mediaWiki, jQuery, blueSpice ) );
