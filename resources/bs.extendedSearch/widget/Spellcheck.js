( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.SpellcheckWidget = function( cfg ) {
		cfg = cfg || {};
		this.action = cfg.action;
		this.alternative = cfg.alternative || {};
		this.original = cfg.original || {};

		this.$element = $( '<div>' ).addClass( 'bs-extendedsearch-search-center-alt-search' );

		if( this.action == 'replaced' ) {
			var $originalTermAnchor = $( '<a>' )
				.html( this.original.term )
				.on( 'click', { term: this.original.term }, this.changeSearchTerm.bind( this ) );

			var message = mw.message(
				"bs-extendedsearch-search-center-hitcount-replaced",
				"##ALTERNATIVETERM##",
				"##ORIGINALTERM##",
				this.original.count
			).escaped();

			message = message.replace( "##ALTERNATIVETERM##", '<b>' + this.alternative.term + '</b>' );
			message = message.replace( '##ORIGINALTERM##', "<a id='bs-replace'>" + this.original.term + "</a>" );
			this.$element.append(
				$( '<span>' ).addClass( 'bs-extendedsearch-search-center-alt-search-replaced' )
					.append( message )
			);
			this.$element.find( '#bs-replace' ).replaceWith( $originalTermAnchor );
		} else if ( this.action == 'suggest' ){
			var $alternativeTermAnchor = $( '<a>' )
				.html( this.alternative.term )
				.on( 'click', { term: this.alternative.term }, this.changeSearchTerm.bind( this ) );

			var message = mw.message(
				"bs-extendedsearch-search-center-hitcount-suggest",
				"##TERM##",
				this.alternative.count
			).escaped();

			message = message.replace( '##TERM##', "<a id='bs-suggest'>" + this.alternative.term + "</a>" );
			this.$element.append(
				$( "<span>" ).addClass( 'bs-extendedsearch-search-center-alt-search-suggest' )
				.append( message )
			);
			this.$element.find( '#bs-suggest' ).replaceWith( $alternativeTermAnchor );
		}
	}

	OO.inheritClass( bs.extendedSearch.SpellcheckWidget, OO.ui.Widget );

	bs.extendedSearch.SpellcheckWidget.prototype.changeSearchTerm = function( e ) {
		this.$element.trigger( 'forceSearchTerm', {
			term: e.data.term,
			force: true
		} );
	}

} )( mediaWiki, jQuery, blueSpice, document );