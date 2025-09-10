bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

bs.extendedSearch.AutocompleteResult = function ( cfg ) {
	cfg = cfg || {};

	this.basename = cfg.suggestion.basename;
	this.type = cfg.suggestion.type;
	this.score = cfg.suggestion.score;
	this.titleTrim = cfg.titleTrim || null;
	this.uri = cfg.suggestion.uri;
	this.pageAnchor = cfg.suggestion.page_anchor || null;
	this.source = cfg.suggestion.source || null;
	this.imageUrl = cfg.suggestion.image_uri || null;
	this.namespaceText = cfg.suggestion.namespace_text || null;
	this.breadcrumbs = cfg.suggestion.breadcrumbs || null;
	this.isRedirect = cfg.suggestion.is_redirect || false;
	this.$redirectTargetAnchor = cfg.suggestion.redirect_target_anchor || null;
	this.id = cfg.id || 9999;

	this.searchTerm = cfg.term || '';

	bs.extendedSearch.AutocompleteResult.parent.call( this, cfg );
	bs.extendedSearch.mixin.ResultOriginalTitle.call( this, cfg.suggestion );

	this.$element = $( '<li>' );
	this.$element.attr( 'role', 'option' );
	this.$element.attr( 'aria-disabled', 'false' );
	this.$element.attr( 'id', this.id );

	this.render();
	this.$element.on( 'click', this.onResultClick );

	this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-item' );
};

OO.inheritClass( bs.extendedSearch.AutocompleteResult, OO.ui.Widget );
OO.mixinClass( bs.extendedSearch.AutocompleteResult, bs.extendedSearch.mixin.ResultOriginalTitle );

bs.extendedSearch.AutocompleteResult.prototype.onResultClick = function ( e ) {
	const $target = $( e.target );
	if ( $target.hasClass( 'bs-extendedsearch-autocomplete-popup-item' ) === false ) {
		return;
	}
	// Anchor may be custom one, coming from backend, so we cannot target more specifically
	const $anchor = $target.find( 'a' );
	if ( $anchor ) {
		window.location = $anchor.attr( 'href' );
	}
};

bs.extendedSearch.AutocompleteResult.prototype.render = function () {
	if ( this.pageAnchor ) {
		this.$pageAnchor = $( this.pageAnchor );
		this.basename = this.$pageAnchor.html();
	}

	// Decode HTML entities
	this.basename = $( '<textarea>' ).html( this.basename ).text();
	if ( this.titleTrim ) {
		const regex = new RegExp( '^' + this.titleTrim );
		this.basename = this.basename.replace( regex, '' );
	}

	this.boldSearchTerm();

	// If backend provided an anchor use it, otherwise create it
	if ( this.pageAnchor ) {
		this.$header = this.$pageAnchor.html( this.basename );
	} else {
		this.$header = $( '<a>' )
			.attr( 'href', this.uri )
			.html( this.basename );
	}

	this.$header.addClass( 'bs-extendedsearch-autocomplete-popup-item-header' );

	const $pathCnt = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-item-header-path' );
	if ( this.source ) {
		$pathCnt.append( $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-item-header-path-source' )
			.text( this.source )
		);
	}
	if ( this.namespaceText ) {
		$pathCnt.append( $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-item-header-path-ns' )
			.text( this.namespaceText )
		);
	}

	if ( this.breadcrumbs ) {
		$pathCnt.append(
			$( '<span>' )
				.addClass( 'bs-extendedsearch-autocomplete-popup-item-header-breadcrumbs' )
				.html( this.breadcrumbs )
		);
	}
	this.$header.append( $pathCnt );
	this.$header.append( this.$originalTitle );
	if ( this.isRedirect ) {
		const redirLayout = new OO.ui.HorizontalLayout( {
			items: [
				new OO.ui.IconWidget( {
					icon: 'articleRedirect'
				} )
			],
			classes: [ 'bs-extendedsearch-autocomplete-popup-item-header-redirect' ]
		} );
		redirLayout.$element.append( this.$redirectTargetAnchor );
		this.$header.append( redirLayout.$element );
	}
	this.$element.append( this.$header );

	if ( this.imageUrl ) {
		this.$element.append( $( '<img>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-item-header-image' )
			.attr( 'src', this.imageUrl )
		);
	}
};

bs.extendedSearch.AutocompleteResult.prototype.boldSearchTerm = function () {
	if ( !this.searchTerm ) {
		return;
	}
	const re = new RegExp( '(' + this.searchTerm + ')', 'gi' );
	this.basename = this.basename.replace( re, '<strong>$1</strong>' );
};
