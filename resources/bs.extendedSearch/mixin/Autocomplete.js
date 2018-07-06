( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.mixin.AutocompleteResults = function( cfg ) {
		cfg = cfg || {};

		//Init containers for each result type
		this.$primaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-primary' );
		this.$topMatches = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-top-match' );
		this.$secondaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-secondary' );

		this.namespaceId = cfg.namespaceId;

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
			if( suggestion.rank == bs.extendedSearch.Autocomplete.AC_RANK_TOP ) {
				if( limits.top > this.displayedResults.top.length ) {
					this.$topMatches.append(
						new bs.extendedSearch.AutocompleteTopMatch( {
							suggestion: suggestion
						} ).$element
					);
					this.displayedResults.top.push( suggestion );
				}
			}

			if( suggestion.rank == bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY ) {
				continue;
			}

			if( limits.primary <= this.displayedResults.primary.length ) {
				break;
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
	}

	bs.extendedSearch.mixin.AutocompleteResults.prototype.fillSecondaryResults = function( suggestions ) {
		//Fuzzy results when no NS is selected and hits in other NSs when it is
		for( idx in suggestions ) {
			var suggestion = suggestions[idx];
			if( suggestion.rank == bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY
					|| this.namespaceId != 0 ) {
				if( this.displayLimits.secondary <= this.displayedResults.secondary.length ) {
					continue;
				}
				this.$secondaryResults.append(
					new bs.extendedSearch.AutocompleteSecondaryResult( {
						suggestion: suggestion
					} ).$element
				);
				this.displayedResults.secondary.push( suggestion );
			}
		}
	}

	OO.initClass( bs.extendedSearch.mixin.AutocompleteResults );

	bs.extendedSearch.mixin.AutocompleteHeader = function( cfg ) {
		this.uri = cfg.uri;
		this.basename = cfg.basename;
		this.pageAnchor = cfg.page_anchor || null;

		if( this.pageAnchor ) {
			this.$pageAnchor = $( this.pageAnchor );
			this.basename = this.$pageAnchor.html();
		}

		this.boldSearchTerm();

		//If backend provided an anchor use it, otherwise create it
		if( this.pageAnchor ) {
			this.$header = this.$pageAnchor.html( this.basename );
		} else {
			this.$header = $( '<a>' )
				.attr( 'href', this.uri )
				.html( this.basename );
		}
		this.$header.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item-header' );
	}

	OO.initClass( bs.extendedSearch.mixin.AutocompleteHeader );

	//Bolds out search term in the result title
	bs.extendedSearch.mixin.AutocompleteHeader.prototype.boldSearchTerm = function() {
		var re = new RegExp( "(" + this.searchTerm + ")", "gi" );
		this.basename = this.basename.replace( re, "<b>$1</b>" );
	}

	bs.extendedSearch.mixin.AutocompleteHitType = function( cfg ) {
		this.hitType = cfg.hitType;
		this.rankType = cfg.rankType;

		this.$type = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-' + this.rankType + '-item-type' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-result-type', this.hitType ).plain() );
	}

	OO.initClass( bs.extendedSearch.mixin.AutocompleteHitType );

	bs.extendedSearch.mixin.AutocompleteCreatePageLink = function( cfg ) {
		cfg = cfg || {};

		if( cfg.creatable == 0 ) {
			return;
		}

		var cnt = this.$specialResults;
		if( this.mobile || this.compact ) {
			cnt = this.$primaryResults;
		}

		var termHtml = "<b class='bs-extendedsearch-autocomplete-create-page-link-term'>{term}</b>";
		termHtml = termHtml.replace( '{term}', cfg.display_text );

		cnt.append(
			$( '<div>' )
				.addClass( 'bs-extendedsearch-autocomplete-popup-create-page-link' )
				.append(
					$( '<a>' ).attr( 'href', cfg.full_url )
					.html( mw.message( 'bs-extendedsearch-autocomplete-create-page-link', termHtml ).parse() )
				)
		);
	}

	OO.initClass( bs.extendedSearch.mixin.AutocompleteCreatePageLink );

	bs.extendedSearch.mixin.FullTextSearchButton = function( cfg ) {
		cfg = cfg || {};

		var cnt = this.$specialResults;
		if( this.mobile || this.compact ) {
			cnt = this.$primaryResults;
		}

		this.fullTextSearchButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-extendedsearch-autocomplete-fulltext-search-button' ).plain(),
			icon: 'search'
		} );
		this.fullTextSearchButton.$element.addClass( 'bs-extendedsearch-autocomplete-popup-fulltext-search-button' );

		cnt.append(
			this.fullTextSearchButton.$element
		);
	}

	OO.initClass( bs.extendedSearch.mixin.FullTextSearchButton );
} )( mediaWiki, jQuery, blueSpice, document );
