( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.ResultRedirectWidget = function( cfg, mobile ) {
		cfg = cfg || {};

		this.mobile = mobile || false;
		this.id = cfg._id;
		this.rawResult = cfg.raw_result || {};
		this.headerAnchor = cfg.page_anchor || null;
		this.redirectTargetAnchor = cfg.redirect_target_anchor || null;

		bs.extendedSearch.mixin.ResultImage.call( this, { imageUri: cfg.image_uri || '' } );

		this.$dataContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-data-container' );

		this.$headerContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-header-container' );

		this.$header = $( this.headerAnchor );

		this.$header.addClass( 'bs-extendedsearch-result-header' );

		this.$headerContainer.append( this.$header );

		this.$redirectTargetAnchor = $( this.redirectTargetAnchor );

		this.redirectLabel = new OO.ui.LabelWidget( {
			label: mw.message( 'bs-extendedsearch-redirect-target-label' ).text()
		} );

		this.$redirectTargetContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-redirect-target-container' )
			.append( this.redirectLabel.$element, this.$redirectTargetAnchor );

		this.$dataContainer.append( this.$headerContainer, this.$redirectTargetContainer );

		this.$image.on( 'click', { pageAnchor: this.$redirectTargetAnchor }, this.onImageClick );

		this.$element = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-container redirect' )
			.append( this.$image, this.$dataContainer );

		if( this.mobile ) {
			this.$element.addClass( 'bs-extendedsearch-result-mobile' );
		}
	};

	OO.inheritClass( bs.extendedSearch.ResultRedirectWidget,  bs.extendedSearch.ResultWidget );
	OO.mixinClass( bs.extendedSearch.ResultRedirectWidget, bs.extendedSearch.mixin.ResultImage );

} )( mediaWiki, jQuery, blueSpice, document );
