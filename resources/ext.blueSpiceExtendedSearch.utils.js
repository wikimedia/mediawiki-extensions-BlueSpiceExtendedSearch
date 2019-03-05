(function( mw, $, bs, d, undefined ){

	function parseQueryString( source ) {
		source = source.substr( 1 );
		var parts = source.split( '&' );
		var obj = {};
		for( var i = 0; i < parts.length; i++ ) {
			var kvpair = parts[i].split( '=' );
			var key = decodeURIComponent( kvpair.shift() );
			if( !key || key.length === 0 ) {
				continue;
			}

			var rawValue = decodeURIComponent( kvpair.join( '=' ) );
			var parsedValue = false;
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

	function _getQueryStringParam( param, loc ) {
		var location = loc || window.location;
		var parts = parseQueryString( location.search );

		if( param in parts ) {
			return parts[param];
		}
	}

	function _removeQueryStringParams( params ) {
		if ( $.isArray( params ) === false ) {
			params = [ params ];
		}
		var search = location.search;
		for( var i = 0; i < params.length; i++ ) {
			search = search
				.replace( new RegExp('[?&]' + params[i] + '=[^&#]*(#.*)?$' ), '$1' )
				.replace( new RegExp('([?&])' + params[i] + '=[^&]*&'), '$1' );
		}
		var newUrl = window.location.href.replace( window.location.search, search );
		this.pushHistory(
			newUrl
		);
	}

	function _pushHistory( url ) {
		window.history.replaceState( {path:url},'',url );
	}
	/**
	 *
	 * @param Location loc
	 * @returns object
	 */
	function _getFragment( loc ) {
		var location = loc || window.location;

		return parseQueryString( location.hash );
	}

	/**
	 *
	 * @param object obj
	 * @param Location loc
	 * @returns void
	 */
	function _setFragment( obj ) {
		var hashMap = {};

		for( var key in obj ) {
			if ( !obj.hasOwnProperty( key ) ) {
				continue;
			}
			var value = obj[key];
			var encValue = JSON.stringify( value );
			hashMap[key] = encValue;
		}

		history.replaceState( undefined, undefined, "#" + $.param( hashMap ) );
		$( window ).trigger( 'hashchange' );
	}

	function _clearFragment( loc ) {
		var location = loc || window.location;

		location.hash = '';
	}

	function _getNamespacesList() {
		return mw.config.get( 'wgNamespaceIds' );
	}

	function _getNamespaceNames( namespaces, id ) {
		var names = [];
		for( var namespaceName in namespaces ) {
			if ( !namespaces.hasOwnProperty( namespaceName ) )  {
				continue;
			}
			var nsId = namespaces[namespaceName];
			if( nsId === id ) {
				names.push( namespaceName.charAt(0).toUpperCase() + namespaceName.slice(1) );
			}
		}
		return names;
	}

	function _isMobile() {
		//MobileFrontend is required to make this decision
		//on load-time, it is not used, so we init correct type here
		var $nav = $( '.calumma-mobile-visible' );
		if ( $nav.is( ':visible' ) && window.innerWidth < 1000 ) {
			return true;
		}

		return false;
	}

	bs.extendedSearch.utils = {
		getFragment: _getFragment,
		setFragment: _setFragment,
		clearFragment: _clearFragment,
		getQueryStringParam: _getQueryStringParam,
		getNamespacesList: _getNamespacesList,
		getNamespaceNames: _getNamespaceNames,
		removeQueryStringParams: _removeQueryStringParams,
		pushHistory: _pushHistory,
		isMobile: _isMobile
	};
})( mediaWiki, jQuery, blueSpice, document );