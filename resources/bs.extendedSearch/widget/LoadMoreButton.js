( function( mw, $, bs, d, undefined ){

	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.LoadMoreButtonWidget = function( cfg ) {
		cfg = cfg || {};

		this.$element = $( '<div>' );

		bs.extendedSearch.LoadMoreButtonWidget.parent.call( this, cfg );

		this.$element.addClass( 'bs-extendedsearch-loadmore' );

		var text = mw.message( 'bs-extendedsearch-loadmore-label' ).plain();
		this.$anchor = $( '<a>' ).attr( 'href', '#' ).html( text );
		this.$anchor.on( 'click', this.onClick.bind( this ) );

		this.$element.append( this.$anchor );
	}

	OO.inheritClass( bs.extendedSearch.LoadMoreButtonWidget, OO.ui.Widget );

	bs.extendedSearch.LoadMoreButtonWidget.prototype.onClick = function( e ) {
		e.preventDefault();

		this.$element.trigger( 'loadMore' );
	}

	bs.extendedSearch.LoadMoreButtonWidget.prototype.error = function() {
		var message = mw.message( 'bs-extendedsearch-loadmore-error' ).plain();
		this.$element.html( $( '<span>' ).html( message ) );
	}

	bs.extendedSearch.LoadMoreButtonWidget.prototype.destroy = function() {
		this.$element.remove();
	}

	bs.extendedSearch.LoadMoreButtonWidget.prototype.showLoading = function() {
		var pbWidget = new OO.ui.ProgressBarWidget({
			progress: false
		} );

		pbWidget.$element.addClass( 'bs-extendedsearch-loadmore-loader' );

		this.$element.html( pbWidget.$element );
	}

} )( mediaWiki, jQuery, blueSpice, document );