( function ( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

	bs.extendedSearch.ResultRedirectWidget = function ( cfg, mobile ) {
		cfg = cfg || {};

		bs.extendedSearch.ResultRedirectWidget.parent.call( this, cfg, mobile );
		this.redirectTargetAnchor = cfg.redirect_target_anchor || null;

		this.$redirectTargetAnchor = $( this.redirectTargetAnchor );

		this.redirectIcon = new OO.ui.IconWidget( {
			icon: 'share',
			title: mw.message( 'bs-extendedsearch-redirect-target-label' ).text()
		} );

		this.$redirectTargetContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-redirect-target-container' )
			.append( this.redirectIcon.$element, this.$redirectTargetAnchor );

		this.$redirectTargetContainer.insertAfter( this.$header );
		this.$element.addClass( 'redirect' );
	};

	OO.inheritClass( bs.extendedSearch.ResultRedirectWidget, bs.extendedSearch.ResultWidget );

}( mediaWiki, jQuery, blueSpice ) );
