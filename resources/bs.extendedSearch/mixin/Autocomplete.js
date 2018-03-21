( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.mixin.AutocompleteResults = function( cfg ) {
		cfg = cfg || {};

		//Init containers for each result type
		this.$primaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-primary' );
		this.$topMatches = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-top-match' );
		this.$secondaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-secondary' );

		//Just for convinience
		var limits = this.displayLimits;

		//Objects holding suggestions actually displayed
		this.displayedResults = {
			primary: [],
			top: [],
			secondary: []
		};

		for( idx in cfg.data ) {
			var suggestion = cfg.data[idx];
			//Top matches
			if( suggestion.score >= 7 ) {
				if( limits.top >  this.displayedResults.top.length ) {
					this.$topMatches.append(
						new bs.extendedSearch.AutocompleteTopMatch( {
							suggestion: suggestion
						} ).$element
					);
					this.displayedResults.top.push( suggestion );
				}
			}

			//Fuzzy results when no NS is selected and hits in other NSs when it is
			if( ( cfg.namespaceId !== 0 && suggestion.score < 5 ) || suggestion.score <= 2 ) {
				if( limits.secondary <= this.displayedResults.secondary.length ) {
					continue;
				}
				this.$secondaryResults.append(
					new bs.extendedSearch.AutocompleteSecondaryResult( {
						suggestion: suggestion
					} ).$element
				);
				this.displayedResults.secondary.push( suggestion );
				continue;
			}

			//If no namespace is specified, let all namespaces into primaries,
			//otherwise only results in specified namespace
			if( cfg.namespaceId !== 0 && suggestion.score < 5 ) {
				continue;
			}

			if( limits.primary <= this.displayedResults.primary.length ) {
				continue;
			}

			var pageItem = new bs.extendedSearch.AutocompletePrimaryResult( {
				suggestion: suggestion,
				term: this.searchTerm
			} );

			this.$primaryResults.append( pageItem.$element );

			this.displayedResults.primary.push( suggestion );
		}

		//If there are no primary results, display "no results" in primary section
		//Fuzzy results will be displayed
		if( this.displayedResults.primary.length === 0 ) {
			this.$primaryResults.append(
				$( '<div>' )
					.addClass( 'bs-extendedsearch-autocomplete-popup-primary-no-results' )
					.html( mw.message( 'bs-extendedsearch-autocomplete-result-primary-no-results-label' ).plain() )
			);
		}

		//"Right column" container, holding top and fuzzy results
		this.$specialResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-special-cnt' );

		this.$topMatchLabel = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-special-item-label' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-result-top-match-label' ).plain() );

		this.$secondaryResultsLabel = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-special-item-label' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-result-secondary-results-label' ).plain() );

		if( this.$topMatches.children().length > 0 ) {
			this.$specialResults.append( this.$topMatchLabel, this.$topMatches );
		}

		if( this.$secondaryResults.children().length > 0 ) {
			this.$specialResults.append( this.$secondaryResultsLabel, this.$secondaryResults );
		}
	}

	OO.initClass( bs.extendedSearch.mixin.AutocompleteResults );
} )( mediaWiki, jQuery, blueSpice, document );