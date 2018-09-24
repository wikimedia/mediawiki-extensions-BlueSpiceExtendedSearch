( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.mixin.AutocompleteResults = function( cfg ) {
		cfg = cfg || {};

		//Init containers for each result type
		this.$primaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-primary' );
		this.$secondaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-secondary' );

		this.namespaceId = cfg.namespaceId;

		//Just for convinience
		var limits = this.displayLimits;

		//Objects holding suggestions actually displayed
		this.displayedResults = {
			normal: [],
			top: [],
			secondary: []
		};

		var normalResultElements = [];
		var topResultElements = [];

		for( idx in cfg.data ) {
			var suggestion = cfg.data[idx];
			//Top matches
			if( !this.compact && suggestion.rank == bs.extendedSearch.Autocomplete.AC_RANK_TOP ) {
				if( limits.top > this.displayedResults.top.length ) {
					topResultElements.push(
						new bs.extendedSearch.AutocompleteTopMatch( {
							suggestion: suggestion,
							popup: this
						} ).$element
					);
					this.displayedResults.top.push( suggestion );
					continue;
				}
			}

			if( suggestion.rank == bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY ) {
				continue;
			}

			if( limits.normal <= this.displayedResults.normal.length ) {
				continue;
			}

			var pageItem = new bs.extendedSearch.AutocompleteNormalResult( {
				suggestion: suggestion,
				term: this.searchTerm,
				popup: this
			} );

			normalResultElements.push( pageItem.$element );
			this.displayedResults.normal.push( suggestion );
		}

		//If there are no primary results, display "no results" in primary section
		//Fuzzy results will be displayed
		if( this.displayedResults.top.length === 0
			&& this.displayedResults.normal.length === 0 ) {
			this.$primaryResults.append(
				$( '<div>' )
					.addClass( 'bs-extendedsearch-autocomplete-popup-primary-no-results' )
					.html( mw.message( 'bs-extendedsearch-autocomplete-result-primary-no-results-label' ).plain() )
			);
		} else {
			this.$primaryResults.append( topResultElements );
			this.$primaryResults.append( normalResultElements );
		}

		//"Right column" container, holding top and fuzzy results
		this.$specialResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-special-cnt' );

		this.$secondaryResultsLabel = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-special-item-label' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-result-secondary-results-label' ).plain() );
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

		var popupWidth = this.popup.searchForm.width();
		popupWidth = ( !this.popup.mobile && !this.popup.compact ) ? popupWidth / 2 : popupWidth;
		var snippetLength = popupWidth > 0 ? popupWidth / 10 : 30;
		snippetLength = this.popup.mobile ? snippetLength * 0.7 : snippetLength;
		snippetLength = Math.round( snippetLength );
		this.basename = this.getSnippet( this.basename, snippetLength, this.searchTerm );

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

	bs.extendedSearch.mixin.AutocompleteHeader.prototype.getSnippet = function( text, length, mustContain ) {
		var hasMoreText = '...';
		if( text.length <= length ) {
			return text;
		}

		length = length - 3; // To fit in the dots

		mustContain = mustContain || '';
		var startsWithMustContain = text.indexOf( mustContain ) === 0;
		if( mustContain === '' || startsWithMustContain ) {
			return text.substring( 0, length ) + hasMoreText;
		}

		var mustContainLen = mustContain.length;
		if( mustContainLen >= length ) {
			return mustContain.substring( 0, length ) + hasMoreText;
		}

		var restAfterMustContainLen = length - mustContainLen;
		if( restAfterMustContainLen >= 6 ) {
			var endsWithMustContain = text.slice( -mustContainLen ) === mustContain;
			if( endsWithMustContain ) {
				return text.substring( 0, restAfterMustContainLen ) + hasMoreText + mustContain;
			}
			return text.substring( 0, restAfterMustContainLen - 3 ) + hasMoreText + mustContain + hasMoreText + text.slice( -3 );
		}
	}

	//Bolds out search term in the result title
	bs.extendedSearch.mixin.AutocompleteHeader.prototype.boldSearchTerm = function() {
		var re = new RegExp( "(" + this.searchTerm + ")", "gi" );
		this.basename = this.basename.replace( re, "<b>$1</b>" );
	}

	bs.extendedSearch.mixin.AutocompleteModifiedTime = function( cfg ) {
		this.mtime = cfg.modified_time;

		this.$modifiedTime = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-item-modified-time' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-modified-time-label', this.mtime ).plain() );
	}

	OO.initClass( bs.extendedSearch.mixin.AutocompleteModifiedTime );

	bs.extendedSearch.mixin.AutocompleteCreatePageLink = function( cfg ) {
		cfg = cfg || {};

		if( cfg.creatable == 0 ) {
			return;
		}

		var cnt = this.$specialResults;
		if( this.mobile || this.compact ) {
			cnt = this.$primaryResults;
		}

		var $anchor = cfg.anchor;
		this.$createPageLink = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-create-page-link' )
			.append( $anchor );
		cnt.append(
			this.$createPageLink
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
