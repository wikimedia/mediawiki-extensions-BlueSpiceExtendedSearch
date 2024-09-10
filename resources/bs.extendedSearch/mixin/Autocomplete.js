( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.mixin.AutocompleteResults = function( cfg ) {
		cfg = cfg || {};

		this.headerText = cfg.headerText || false;

		//Init containers for each result type
		this.$primaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-primary' );
		this.$actions = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-actions' );
		this.$secondaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-secondary' );

		if ( this.headerText ) {
			this.$primaryResults.append(
				new OO.ui.LabelWidget( { label: this.headerText } ).$element
			);
		}

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

		for( var i = 0; i < cfg.data.length; i++ ) {
			var suggestion = cfg.data[i];
			//Top matches
			if( !this.compact && suggestion.rank === bs.extendedSearch.Autocomplete.AC_RANK_TOP ) {
				if( limits.top > this.displayedResults.top.length ) {
					topResultElements.push(
						new bs.extendedSearch.AutocompleteTopMatch( {
							suggestion: suggestion,
							popup: this,
							autocomplete: this.autocomplete,
							titleTrim: this.titleTrim
						} ).$element
					);
					this.displayedResults.top.push( suggestion );
					continue;
				}
			}

			if( suggestion.rank === bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY ) {
				continue;
			}

			if( limits.normal <= this.displayedResults.normal.length ) {
				continue;
			}

			var pageItem = new bs.extendedSearch.AutocompleteNormalResult( {
				suggestion: suggestion,
				term: this.searchTerm,
				popup: this,
				titleTrim: this.titleTrim
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
	};

	bs.extendedSearch.mixin.AutocompleteResults.prototype.fillSecondaryResults = function( suggestions ) {
		//Fuzzy results when no NS is selected and hits in other NSs when it is
		for( var i = 0; i < suggestions.length; i++ ) {
			var suggestion = suggestions[i];
			if(
				suggestion.rank === bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY ||
				this.namespaceId !== 0
			) {
				if( this.displayLimits.secondary <= this.displayedResults.secondary.length ) {
					continue;
				}
				this.$secondaryResults.append(
					new bs.extendedSearch.AutocompleteSecondaryResult( {
						suggestion: suggestion,
						popup: this,
						titleTrim: this.titleTrim
					} ).$element
				);
				this.displayedResults.secondary.push( suggestion );
			}
		}
	};

	OO.initClass( bs.extendedSearch.mixin.AutocompleteResults );

	bs.extendedSearch.mixin.AutocompleteHeader = function( cfg ) {
		bs.extendedSearch.mixin.ResultOriginalTitle.call( this, cfg );

		this.uri = cfg.uri;
		this.basename = cfg.basename;
		this.pageAnchor = cfg.page_anchor || null;

		if( this.pageAnchor ) {
			this.$pageAnchor = $( this.pageAnchor );
			this.basename = this.$pageAnchor.html();
		}

		// Decode HTML entities
		this.basename = $( "<textarea/>" ).html( this.basename ).text();
		if ( this.titleTrim ) {
			var regex = new RegExp( '^' + this.titleTrim );
			this.basename = this.basename.replace( regex, '' );
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
		if ( cfg.original_title ) {
			this.$header.append( this.$originalTitle );
		}

		if ( cfg.is_redirect ) {
			var redirLayout = new OO.ui.HorizontalLayout( {
				items: [
					new OO.ui.IconWidget( {
						icon: 'articleRedirect'
					} )
				]
			} );
			redirLayout.$element.append( cfg.redirect_target_anchor );
			this.$header.append( redirLayout.$element );
		}

		this.$header.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item-header' );
	};

	OO.mixinClass( bs.extendedSearch.mixin.AutocompleteHeader, bs.extendedSearch.mixin.ResultOriginalTitle );
	OO.initClass( bs.extendedSearch.mixin.AutocompleteHeader );

	//Bolds out search term in the result title
	bs.extendedSearch.mixin.AutocompleteHeader.prototype.boldSearchTerm = function() {
		var re = new RegExp( "(" + this.searchTerm + ")", "gi" );
		this.basename = this.basename.replace( re, "<b>$1</b>" );
	};

	bs.extendedSearch.mixin.AutocompleteModifiedTime = function( cfg ) {
		this.mtime = cfg.modified_time;

		this.$modifiedTime = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-item-modified-time' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-modified-time-label', this.mtime ).plain() );
	};

	OO.initClass( bs.extendedSearch.mixin.AutocompleteModifiedTime );

	bs.extendedSearch.mixin.AutocompleteCreatePageLink = function( cfg ) {
		cfg = cfg || {};

		if( !cfg.creatable ) {
			return;
		}

		this.$createPageLink = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-create-page-link' )
			.append( cfg.anchor );
		this.$actions.append(
			this.$createPageLink
		);
	};

	OO.initClass( bs.extendedSearch.mixin.AutocompleteCreatePageLink );

	bs.extendedSearch.mixin.FullTextSearchButton = function( cfg ) {
		cfg = cfg || {};

		if ( cfg.hasOwnProperty( 'canFulltextSearch' ) && !cfg.canFulltextSearch ) {
			return;
		}

		this.fullTextSearchButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-extendedsearch-autocomplete-fulltext-search-button' ).plain(),
			icon: 'search'
		} );
		this.fullTextSearchButton.$element.addClass( 'bs-extendedsearch-autocomplete-popup-fulltext-search-button' );

		this.$actions.append(
			this.fullTextSearchButton.$element
		);
	};

	OO.initClass( bs.extendedSearch.mixin.FullTextSearchButton );
} )( mediaWiki, jQuery, blueSpice, document );
