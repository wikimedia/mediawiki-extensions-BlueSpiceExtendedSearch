( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.HitCountWidget = function( cfg ) {
		cfg = cfg || {};

		this.term = cfg.term || '';
		this.count = cfg.count || 0;
		this.spellcheck = cfg.spellcheck;

		this.$counter = $( '<p>' )
			.html( mediaWiki.message( 'bs-extendedsearch-search-center-hitcount-widget', this.count, this.term ).parse() );

		this.$element = $( '<div>' ).addClass( 'bs-extendedsearch-search-center-hitcount' ).append( this.$counter );

		if( this.spellcheck.action == 'replaced' ) {
			var $originalTermAnchor = $( '<a>' )
				.addClass( 'bs-extendedsearch-search-center-hitcount-replaced-link' )
				.html( this.spellcheck.original.term )
				.on( 'click', { term: this.spellcheck.original.term }, this.changeSearchTerm.bind( this ) );

			var message = mw.message(
				"bs-extendedsearch-search-center-hitcount-replaced",
				this.spellcheck.alternative.term,
				this.spellcheck.original.term,
				this.spellcheck.original.count
			).parse();

			//Since no HTML should be in the message
			message = message.replace( this.spellcheck.alternative.term, '<b>' + this.spellcheck.alternative.term + '</b>' );
			//Hacky - but only way events work
			message = message.split( this.spellcheck.original.term );
			this.$element.append(
				$( '<div>' ).addClass( 'bs-extendedsearch-search-center-hitcount-replaced' )
					.append( message[0], $originalTermAnchor, message[1] )
			);
		} else if ( this.spellcheck.action == 'suggest' ){
			var $alternativeTermAnchor = $( '<a>' )
				.addClass( 'bs-extendedsearch-search-center-hitcount-suggest-link' )
				.html( this.spellcheck.alternative.term )
				.on( 'click', { term: this.spellcheck.alternative.term }, this.changeSearchTerm.bind( this ) );

			var message = mw.message(
				"bs-extendedsearch-search-center-hitcount-suggest",
				this.spellcheck.alternative.term,
				this.spellcheck.alternative.count
			).parse();

			//Hacky - but only way events work
			message = message.split( this.spellcheck.alternative.term );
			this.$element.append(
				$( '<div>' ).addClass( 'bs-extendedsearch-search-center-hitcount-suggest' )
				.append( message[0], $alternativeTermAnchor, message[1] )
			);
		}
	}

	OO.inheritClass( bs.extendedSearch.HitCountWidget, OO.ui.Widget );

	bs.extendedSearch.HitCountWidget.prototype.changeSearchTerm = function( e ) {
		this.$element.trigger( 'forceSearchTerm', {
			term: e.data.term,
			force: true
		} );
	}

} )( mediaWiki, jQuery, blueSpice, document );