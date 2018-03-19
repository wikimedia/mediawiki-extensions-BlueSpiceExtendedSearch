( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.ResultWidget = function( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.ResultWidget.parent.call( this, cfg );

		bs.extendedSearch.mixin.ResultImage.call( this, cfg );
		bs.extendedSearch.mixin.ResultSecondaryInfo.call( this, cfg );

		this.headerText = cfg.headerText;
		this.headerUri = cfg.headerUri;
		this.secondaryInfos = cfg.secondaryInfos || [];
		this.highlight = cfg.highlight || '';
		this.featured = cfg.featured || false;

		this.$dataContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-data-container' );

		this.$headerContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-header-container' );

		this.$header = $( '<a>' )
			.addClass( 'bs-extendedsearch-result-header' )
			.attr( 'href', this.headerUri )
			.html( this.headerText );

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

} )( mediaWiki, jQuery, blueSpice, document );