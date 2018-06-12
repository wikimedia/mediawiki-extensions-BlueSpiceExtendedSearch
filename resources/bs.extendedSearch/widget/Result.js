( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.ResultWidget = function( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.ResultWidget.parent.call( this, cfg );

		bs.extendedSearch.mixin.ResultImage.call( this, cfg );
		bs.extendedSearch.mixin.ResultSecondaryInfo.call( this, cfg );

		this.headerText = cfg.headerText;
		this.headerUri = cfg.headerUri;
		this.headerAnchor = cfg.page_anchor || null;
		this.secondaryInfos = cfg.secondaryInfos || [];
		this.highlight = cfg.highlight || '';
		this.featured = cfg.featured || false;

		this.id = cfg._id;
		this.rawResult = cfg.raw_result || {};

		this.$dataContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-data-container' );

		this.$headerContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-header-container' );

		if( this.headerAnchor ) {
			this.$header = $( this.headerAnchor );
		} else {
			this.$header = $( '<a>' )
				.attr( 'href', this.headerUri )
				.html( this.headerText );
		}

		this.$header.addClass( 'bs-extendedsearch-result-header' );

		this.$headerContainer.append( this.$header );

		this.$highlightContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-highlight-container' )
			.append(
				$( '<span>' ).html( this.highlight )
			);

		this.$dataContainer.append( this.$headerContainer, this.$topSecondaryInfo, this.$highlightContainer, this.$bottomSecondaryInfo );
		this.$element = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-container' )
			.append( this.$image, this.$dataContainer );

		if( this.featured ) {
			this.$element.addClass( 'bs-extendedsearch-result-featured' );
		}
	}

	OO.inheritClass( bs.extendedSearch.ResultWidget, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultImage );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultSecondaryInfo );

	bs.extendedSearch.ResultWidget.prototype.getId = function() {
		return this.id;
	}

	bs.extendedSearch.ResultWidget.prototype.getRawValue = function( field ) {
		if( field in this.rawResult ) {
			return this.rawResult[field];
		}

		return '';
	}

	bs.extendedSearch.ResultWidget.prototype.getRawResult = function() {
		return this.rawResult;
	}

} )( mediaWiki, jQuery, blueSpice, document );
