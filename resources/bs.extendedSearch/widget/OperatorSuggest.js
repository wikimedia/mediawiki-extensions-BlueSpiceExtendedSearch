( function( $, mw ){
	bs.extendedSearch.OperatorSuggest = function( cfg ) {
		this.$element = $( '<div>' ).addClass( 'bs-extendedSearch-operator-suggest' );

		if ( !mw.config.get( 'bsgESOfferOperatorSuggestion' ) ) {
			return;
		}

		this.lookup = cfg.lookup;
		this.searchBar = cfg.searchBar;

		var suggestedQuery = this.getSuggestedQuery();
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

	bs.extendedSearch.OperatorSuggest.prototype.getSuggestedQuery = function() {
		var qs = this.lookup.getQueryString(),
			query = ( qs.query || '' ).trim(),
			defaultOperator = qs.default_operator || 'OR',
			operatorRegex = /AND|OR/gm,
			regexCheckRegex = /\/.*?\//gm,
			quoteRegex = /\".*?\"/gm,
			hasExplicit = false,
			isRegex = false,
			targetOperator = defaultOperator === 'AND' ? 'OR' : 'AND',
			match, cnt = 0, replacements = {}, identifier;

		// Mask any quoted terms
		while ( match = quoteRegex.exec( qs.query ) ) {
			cnt++;
			if ( match.index === quoteRegex.lastIndex ) {
				quoteRegex.lastIndex++;
			}
			identifier = '#Q' + cnt + 'Q#';
			replacements[identifier] = match;
			query = query.replace( match, identifier );
		}

		// If whole query is consisted only of quoted terms, return
		if ( !query.replace( /\#Q(\d*)\#/, '' ).trim() ) {
			return null;
		}

		hasExplicit = operatorRegex.test( query );
		isRegex = regexCheckRegex.test( query );

		if ( hasExplicit || isRegex ) {
			// User specified operators or a regex, don't interfere
			return null;
		}

		var bits = query.split( ' ' ).map( function( i ) {
			return i.trim();
		} ).filter( function( i ) {
			return i !== '';
		} );
		// If only term is in the search term, return
		if ( bits.length < 2 ) {
			return null;
		}

		var final = bits.join( ' ' + targetOperator + ' ' ).trim();
		// Restore quoted parts
		for ( identifier in replacements ) {
			if ( !replacements.hasOwnProperty( identifier ) ) {
				continue;
			}

			final = final.replace( identifier, replacements[identifier] );
		}

		return final;
	};

	bs.extendedSearch.OperatorSuggest.prototype.onSuggestionFollowed = function() {
		this.searchBar.changeValue( this.suggestedQuery );
		this.searchBar.setValue( this.suggestedQuery );
	};

} )( jQuery, mediaWiki );
