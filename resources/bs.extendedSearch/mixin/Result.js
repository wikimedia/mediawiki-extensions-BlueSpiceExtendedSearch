( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.mixin.ResultImage = function( cfg ) {
		cfg = cfg || {};

		this.imageUri = cfg.imageUri || '';
		if( !this.imageUri ) {
			this.$image = null;
			return;
		}

		//We need div inside a div because of flex layout,
		//inner div must set size. Using background property
		//because of more cross-browser support for fit-to-box features
		this.$image = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-image' )
			.append( $( '<div>' )
				.addClass( 'bs-extendedsearch-result-image-inner' )
				.attr( 'style', "background-image: url(" + this.imageUri + ")" )
			);
	}

	OO.initClass( bs.extendedSearch.mixin.ResultImage );

	bs.extendedSearch.mixin.ResultSecondaryInfo = function( cfg ) {
		cfg = cfg || {};

		this.secondaryInfos = cfg.secondaryInfos || [];
		if( this.secondaryInfos == [] ) {
			return;
		}

		this.topSecondaryInfo = cfg.secondaryInfos.top || [];
		this.bottomSecondaryInfo = cfg.secondaryInfos.bottom || [];

		this.setTopSecondaryInfo( this.topSecondaryInfo.items );
		this.setBottomSecondaryInfo( this.bottomSecondaryInfo.items );
	}

	OO.initClass( bs.extendedSearch.mixin.ResultSecondaryInfo );

	bs.extendedSearch.mixin.ResultSecondaryInfo.prototype.setTopSecondaryInfo = function( items ) {
		this.$topSecondaryInfo = this.getSecondaryInfoMarkup( items );
	}

	bs.extendedSearch.mixin.ResultSecondaryInfo.prototype.setBottomSecondaryInfo = function( items ) {
		this.$bottomSecondaryInfo = this.getSecondaryInfoMarkup( items );
	}

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
	}

	bs.extendedSearch.mixin.ResultSecondaryInfo.prototype.getSecondaryInfoItemMarkup = function( item ) {
		var $label = null;
		if( !item.nolabel ) {
			var label = mw.message( item.labelKey ).plain();
			$label = $( '<span>' )
				.html( label );
		}

		$value = $( '<span>' )
			.html( item.value );

		return $( '<div>' )
			.addClass( 'bs-extendedsearch-secondaryinfo-item' )
			.append( $label, $value );
	}

} )( mediaWiki, jQuery, blueSpice, document );
