( function ( $, mw ) {
	bs.extendedSearch.OperatorSuggest = function ( cfg ) {
		this.$element = $( '<div>' ).addClass( 'bs-extendedSearch-operator-suggest' );

		if ( !mw.config.get( 'bsgESOfferOperatorSuggestion' ) ) {
			return;
		}

		this.lookup = cfg.lookup;
		this.searchBar = cfg.searchBar;

		const suggestedQuery = this.getSuggestedQuery();
		if ( !suggestedQuery ) {
			return;
		}

		this.suggestedQuery = suggestedQuery;
		this.suggestButton = new OO.ui.ButtonWidget( {
			label: new OO.ui.HtmlSnippet( mw.message(
				'bs-extendedsearch-suggest-operator', this.suggestedQuery
			).text() ),
			framed: false
		} );

		this.suggestButton.connect( this, {
			click: 'onSuggestionFollowed'
		} );

		this.$element.append( this.suggestButton.$element );
	};

	OO.initClass( bs.extendedSearch.OperatorSuggest );

	bs.extendedSearch.OperatorSuggest.prototype.getSuggestedQuery = function () {
		const qs = this.lookup.getQueryString();
		let query = ( qs.query || '' ).trim();
		const defaultOperator = qs.default_operator || 'OR';
		const operatorRegex = /AND|OR/gm;
		const regexCheckRegex = /\/.*?\//gm;
		const quoteRegex = /".*?"/gm;
		let hasExplicit = false;
		let isRegex = false;
		const targetOperator = defaultOperator === 'AND' ? 'OR' : 'AND';
		let match;
		let cnt = 0;
		const replacements = {};
		let identifier;

		// Mask any quoted terms
		while ( match = quoteRegex.exec( qs.query ) ) { // eslint-disable-line no-cond-assign
			cnt++;
			if ( match.index === quoteRegex.lastIndex ) {
				quoteRegex.lastIndex++;
			}
			identifier = '#Q' + cnt + 'Q#';
			replacements[ identifier ] = match;
			query = query.replace( match, identifier );
		}

		// If whole query is consisted only of quoted terms, return
		if ( !query.replace( /#Q(\d*)#/, '' ).trim() ) {
			return null;
		}

		hasExplicit = operatorRegex.test( query );
		isRegex = regexCheckRegex.test( query );

		if ( hasExplicit || isRegex ) {
			// User specified operators or a regex, don't interfere
			return null;
		}

		const bits = query.split( ' ' ).map( ( i ) => i.trim() ).filter( ( i ) => i !== '' );
		// If only term is in the search term, return
		if ( bits.length < 2 ) {
			return null;
		}

		let final = bits.join( ' ' + targetOperator + ' ' ).trim();
		// Restore quoted parts
		for ( identifier in replacements ) {
			if ( !replacements.hasOwnProperty( identifier ) ) {
				continue;
			}

			final = final.replace( identifier, replacements[ identifier ] );
		}

		return final;
	};

	bs.extendedSearch.OperatorSuggest.prototype.onSuggestionFollowed = function () {
		this.searchBar.changeValue( this.suggestedQuery );
		this.searchBar.setValue( this.suggestedQuery );
	};

}( jQuery, mediaWiki ) );
