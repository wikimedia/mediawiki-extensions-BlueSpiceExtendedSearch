( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.mixin.ResultImage = function( cfg ) {
		cfg = cfg || {};

		this.$image = $( '<div>' );

		this.imageUri = cfg.imageUri || '';
		if( !this.imageUri ) {
			return;
		}

		//We need div inside a div because of flex layout,
		//inner div must set size. Using background property
		//because of more cross-browser support for fit-to-box features
		this.$image.addClass( 'bs-extendedsearch-result-image' )
			.append( $( '<div>' )
				.addClass( 'bs-extendedsearch-result-image-inner' )
				.attr( 'style', "background-image: url(" + this.imageUri + ")" )
			);
	};

	OO.initClass( bs.extendedSearch.mixin.ResultImage );

	bs.extendedSearch.mixin.ResultSecondaryInfo = function( cfg ) {
		cfg = cfg || {};

		this.secondaryInfos = this.secondaryInfos || [];
		if( this.secondaryInfos == [] ) {
			return;
		}

		this.topSecondaryInfo = this.secondaryInfos.top || [];
		this.bottomSecondaryInfo = this.secondaryInfos.bottom || [];

		this.setTopSecondaryInfo( this.topSecondaryInfo.items );
		this.setBottomSecondaryInfo( this.bottomSecondaryInfo.items );
	};

	OO.initClass( bs.extendedSearch.mixin.ResultSecondaryInfo );

	bs.extendedSearch.mixin.ResultSecondaryInfo.prototype.setTopSecondaryInfo = function( items ) {
		this.$topSecondaryInfo = this.getSecondaryInfoMarkup( items );
	};

	bs.extendedSearch.mixin.ResultSecondaryInfo.prototype.setBottomSecondaryInfo = function( items ) {
		this.$bottomSecondaryInfo = this.getSecondaryInfoMarkup( items );
	};

	bs.extendedSearch.mixin.ResultSecondaryInfo.prototype.getSecondaryInfoMarkup = function( items ) {
		var container = $( '<div>' )
			.addClass( 'bs-extendedsearch-secondaryinfo-container' )

		var me = this;

		$.each( items, function( idx, item ) {
			container.append( me.getSecondaryInfoItemMarkup( item ) );
		} );

		$.each( $( container ).children(), function( idx, child ) {
			if( idx === 0 ) {
				return;
			}
			$( '<div>' ).addClass( 'bs-extendedsearch-result-secondaryinfo-separator' ).insertBefore( $( child ) );
		});

		return container;
	};

	bs.extendedSearch.mixin.ResultSecondaryInfo.prototype.getSecondaryInfoItemMarkup = function( item ) {
		var $label = null;
		if( !item.nolabel ) {
			var labelKey = item.labelKey || '';
			var label = mw.message( labelKey ).plain();
			$label = $( '<span>' )
				.html( label );
		}

		var $value = $( '<span>' )
			.html( item.value );

		return $( '<div>' )
			.addClass( 'bs-extendedsearch-secondaryinfo-item' )
			.append( $label, $value );
	};

	/**
	 * Experimental
	 */
	bs.extendedSearch.mixin.ResultRelevanceControl = function( cfg ) {
		cfg = cfg || {};

		this.isRelevantForUser = cfg.user_relevance === 1;
		this.$relevanceControl = $( '<div>' ).addClass( 'bs-extendedsearch-result-relevance-cnt' );

		if( !mw.config.get( 'wgUserId' ) ) {
			return;
		}

		this.relevantButton = new OO.ui.ButtonWidget( {
			framed: false,
			icon: 'pushPin',
			title: mw.message( 'bs-extendedsearch-result-relevance-relevant' ).plain()
		} );
		if ( this.isRelevantForUser ) {
			this.relevantButton.setFlags( [ 'progressive' ] );
		}
		this.relevantButton.$button.attr( 'aria-pressed', this.isRelevantForUser );
		this.relevantButton.connect( this, {
			click: 'onRelevant'
		} );

		this.$relevanceControl.append( this.relevantButton.$element );
	};

	OO.initClass( bs.extendedSearch.mixin.ResultRelevanceControl );

	bs.extendedSearch.mixin.ResultOriginalTitle = function( cfg ) {
		cfg = cfg || {};

		this.$originalTitle = $( '<div>' );

		this.originalTitle = cfg.original_title || '';
		if( !this.originalTitle ) {
			return;
		}

		var originalTitleText = mw.message( "bs-extendedsearch-wikipage-title-original", this.originalTitle ).text();
		this.$originalTitle
			.addClass( 'bs-extendedsearch-result-original-title' )
			.append( new OO.ui.LabelWidget( { label: originalTitleText } ).$element );
	};

	OO.initClass( bs.extendedSearch.mixin.ResultOriginalTitle );
} )( mediaWiki, jQuery, blueSpice, document );
