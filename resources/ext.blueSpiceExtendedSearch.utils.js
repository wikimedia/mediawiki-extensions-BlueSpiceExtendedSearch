( function ( mw, $, bs, d, undefined ) { // eslint-disable-line no-shadow-restricted-names

	function parseQueryString( source ) {
		source = source.slice( 1 );
		const parts = source.split( '&' );
		const obj = {};
		for ( let i = 0; i < parts.length; i++ ) {
			const kvpair = parts[ i ].split( '=' );
			const key = decodeURIComponent( kvpair.shift() );
			if ( !key || key.length === 0 ) {
				continue;
			}

			const rawValue = decodeURIComponent( kvpair.join( '=' ) );
			let parsedValue = false;
			if ( rawValue.length > 0 ) {
				try {
					parsedValue = JSON.parse( rawValue );
				} catch ( exception ) {
					parsedValue = rawValue;
				}
			}
			obj[ key ] = parsedValue;
		}

		return obj;
	}

	function _getQueryStringParam( param, loc ) {
		const location = loc || window.location;
		const parts = parseQueryString( location.search );

		if ( param in parts ) {
			return parts[ param ];
		}
	}

	function _removeQueryStringParams( params ) {
		if ( Array.isArray( params ) === false ) {
			params = [ params ];
		}
		let search = location.search;
		for ( let i = 0; i < params.length; i++ ) {
			search = search
				.replace( new RegExp( '[?&]' + params[ i ] + '=[^&#]*(#.*)?$' ), '$1' )
				.replace( new RegExp( '([?&])' + params[ i ] + '=[^&]*&' ), '$1' );
		}
		const newUrl = window.location.href.replace( window.location.search, search );
		this.pushHistory(
			newUrl
		);
	}

	function _pushHistory( url ) {
		window.history.replaceState( { path: url }, '', url );
	}
	/**
	 * @param {Location} loc
	 * @return {Object}
	 */
	function _getFragment( loc ) {
		const location = loc || window.location;

		return parseQueryString( location.hash );
	}

	/**
	 * @param {Object} obj
	 */
	function _setFragment( obj ) {
		const hashMap = {};

		for ( const key in obj ) {
			if ( !obj.hasOwnProperty( key ) ) {
				continue;
			}
			const value = obj[ key ];
			const encValue = JSON.stringify( value );
			hashMap[ key ] = encValue;
		}

		history.replaceState( undefined, undefined, '#' + $.param( hashMap ) );
		$( window ).trigger( 'hashchange' );
	}

	function _clearFragment( loc ) {
		const location = loc || window.location;

		location.hash = '';
	}

	function _getNamespacesList() {
		return mw.config.get( 'wgNamespaceIds' );
	}

	function _getNamespaceNames( namespaces, id ) {
		const names = [];
		for ( const namespaceName in namespaces ) {
			if ( !namespaces.hasOwnProperty( namespaceName ) ) {
				continue;
			}
			const nsId = namespaces[ namespaceName ];
			if ( nsId === id ) {
				let name = namespaceName.charAt( 0 ).toUpperCase() + namespaceName.slice( 1 );
				name = name.replace( '_', ' ' );
				names.push( name );
			}
		}
		return names;
	}

	function _isMobile() {
		// MobileFrontend is required to make this decision
		// on load-time, it is not used, so we init correct type here
		const $nav = $( '.calumma-mobile-visible' );
		if ( $nav.is( ':visible' ) && window.innerWidth < 1000 ) { // eslint-disable-line no-jquery/no-sizzle
			return true;
		}

		return false;
	}

	function _normalizeNamespaceName( nsText ) {
		return nsText.toLowerCase().trim().replace( ' ', '_' );
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
		isMobile: _isMobile,
		normalizeNamespaceName: _normalizeNamespaceName
	};
}( mediaWiki, jQuery, blueSpice, document ) );
