(function( mw, $, bs, d, undefined ){


	/**
	 *
	 * @param Location loc
	 * @returns object
	 */
	function _getFragment( loc ) {
		var location = loc || window.location;
		var hash = location.hash.substr( 1 ); //cut off leading '#'
		var parts = hash.split( '&' );
		var obj = {};
		for( var i = 0; i < parts.length; i++ ) {
			var kvpair = parts[i].split( '=' );
			var key = decodeURIComponent( kvpair.shift() );
			if( !key || key.length === 0 ) {
				continue;
			}
			var rawValue = decodeURIComponent( kvpair.join( '=' ) );
			var parsedValue = true;

			if ( rawValue.length > 0 ) {
				try {
					parsedValue = JSON.parse( rawValue );
				}
				catch (exception) {
					parsedValue = rawValue;
				}
			}
			obj[key] = parsedValue;
		}

		return obj;
	}

	/**
	 *
	 * @param object obj
	 * @param Location loc
	 * @returns void
	 */
	function _setFragment( obj, loc ) {
		var location = loc || window.location;
		var hashMap = {};

		for( var key in obj ) {
			var value = obj[key];
			var encValue = JSON.stringify( value );
			hashMap[key] = encValue;
		}

		location.hash = $.param( hashMap );
	}

	bs.extendedSearch.utils = {
		getFragment: _getFragment,
		setFragment: _setFragment
	};
})( mediaWiki, jQuery, blueSpice, document );