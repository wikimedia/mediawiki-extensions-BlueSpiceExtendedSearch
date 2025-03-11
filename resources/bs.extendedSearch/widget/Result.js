( function ( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

	bs.extendedSearch.ResultWidget = function ( cfg, mobile ) {
		cfg = cfg || {};

		this.mobile = mobile || false;

		bs.extendedSearch.ResultWidget.parent.call( this, cfg );

		this.headerText = cfg.headerText;
		this.headerUri = cfg.headerUri;
		this.source = cfg.source || null;
		this.namespaceText = cfg.namespace_text || '';
		this.breadcrumbs = cfg.breadcrumbs || '';
		this.headerAnchor = cfg.page_anchor || null;
		this.secondaryInfos = cfg.secondaryInfos || [];
		this.highlight = cfg.highlight || '';
		this.featured = cfg.featured || false;

		this.isExternal = cfg.isExternal || false;

		this.rightLinks = [];
		// If we are in desktop mode, show right links

		if ( !this.mobile && this.secondaryInfos.hasOwnProperty( 'top' ) ) {
			for ( let i = 0; i < this.secondaryInfos.top.items.length; i++ ) {
				if ( this.secondaryInfos.top.items[ i ].showInRightLinks === true ) {
					this.rightLinks.push( this.secondaryInfos.top.items[ i ] );
					this.secondaryInfos.top.items.splice( i, 1 );
					break;
				}
			}
		}

		bs.extendedSearch.mixin.ResultImage.call( this, cfg );
		bs.extendedSearch.mixin.ResultSecondaryInfo.call( this, cfg );
		bs.extendedSearch.mixin.ResultRelevanceControl.call( this, cfg );
		bs.extendedSearch.mixin.ResultOriginalTitle.call( this, cfg );

		this.id = cfg._id;
		this.rawResult = cfg.raw_result || {};

		this.$dataContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-data-container' );

		this.$headerContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-header-container' );

		if ( this.headerAnchor && !this.isExternal ) {
			this.$header = $( this.headerAnchor );
		} else {
			this.$header = $( '<a>' )
				.attr( 'href', this.headerUri )
				.html( this.headerText );
			if ( this.isExternal ) {
				this.$header.attr( 'target', '_blank' );
			}
		}

		this.$header.addClass( 'bs-extendedsearch-result-header' );
		this.$headerContainer.append( this.$header, this.$originalTitle );

		this.$headerPathInfo = $( '<div>' ).addClass( 'bs-extendedsearch-result-header-path' );
		if ( this.source ) {
			this.$headerPathInfo.append( $( '<span>' )
				.addClass( 'bs-extendedsearch-result-header-source' )
				.text( this.source )
			);
		}
		if ( this.namespaceText ) {
			this.$headerPathInfo.append( $( '<span>' )
				.addClass( 'bs-extendedsearch-result-header-namespace' )
				.text( this.namespaceText )
			);
		}
		if ( this.breadcrumbs ) {
			this.$headerPathInfo.append( $( '<span>' )
				.addClass( 'bs-extendedsearch-result-header-breadcrumbs' )
				.text( this.breadcrumbs )
			);
		}
		this.$headerContainer.append( this.$headerPathInfo );
		this.$image.on( 'click', { pageAnchor: this.$header }, this.onImageClick );

		this.$highlightContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-highlight-container' )
			.append(
				$( '<span>' ).html( this.highlight )
			);

		this.$dataContainer.append( this.$headerContainer, this.$topSecondaryInfo, this.$highlightContainer, this.$bottomSecondaryInfo );

		this.$element = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-container' )
			.attr( 'id', 'bs-es-result-' + this.getId() )
			.append( this.$image, this.$dataContainer, this.$relevanceControl );

		if ( this.rightLinks.length > 0 ) {
			this.$linksContainer = $( '<div>' )
				.addClass( 'bs-extendedsearch-result-links-container' );

			this.$dataContainer.addClass( 'short' );
			for ( let idx = 0; idx < this.rightLinks.length; idx++ ) {
				if ( !this.rightLinks[ idx ].hasOwnProperty( 'labelKey' ) ) {
					// Do not show the entry without any label
					continue;
				}
				this.$innerContainer = $( '<div>' )
					.addClass( 'bs-extendedsearch-result-links-inner' );

				this.$innerContainer.append( new OO.ui.LabelWidget( {
					label: mw.message( this.rightLinks[ idx ].labelKey ).plain() // eslint-disable-line mediawiki/msg-doc
				} ).$element );
				this.$innerContainer.append( this.rightLinks[ idx ].value );
				this.$linksContainer.append( this.$innerContainer );
			}

			this.$element.append( this.$linksContainer );
		}

		if ( this.featured ) {
			this.$element.addClass( 'bs-extendedsearch-result-featured' );
		}

		if ( this.mobile ) {
			this.$element.addClass( 'bs-extendedsearch-result-mobile' );
		}
	};

	OO.inheritClass( bs.extendedSearch.ResultWidget, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultImage );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultSecondaryInfo );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultRelevanceControl );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultOriginalTitle );

	bs.extendedSearch.ResultWidget.prototype.getId = function () {
		return this.id;
	};

	bs.extendedSearch.ResultWidget.prototype.getRawValue = function ( field ) {
		if ( field in this.rawResult ) {
			return this.rawResult[ field ];
		}

		return '';
	};

	bs.extendedSearch.ResultWidget.prototype.getRawResult = function () {
		return this.rawResult;
	};

	bs.extendedSearch.ResultWidget.prototype.onImageClick = function ( e ) {
		const anchor = e.data.pageAnchor;
		window.location = anchor.attr( 'href' );
	};

	bs.extendedSearch.ResultWidget.prototype.onRelevant = function ( e ) { // eslint-disable-line no-unused-vars
		this.isRelevantForUser = !this.isRelevantForUser;
		this.makeChangeRelevanceCall();
		this.updateRelevanceButtons();
	};

	bs.extendedSearch.ResultWidget.prototype.makeChangeRelevanceCall = function () {
		const queryData = {
			relevanceData: JSON.stringify( {
				resultId: this.getId(),
				value: this.isRelevantForUser
			} )
		};

		bs.extendedSearch.SearchCenter.runApiCall(
			queryData,
			'bs-extendedsearch-resultrelevance'
		);
	};

	bs.extendedSearch.ResultWidget.prototype.updateRelevanceButtons = function () {
		if ( this.isRelevantForUser ) {
			this.relevantButton.setFlags( [ 'progressive' ] );
		} else {
			this.relevantButton.clearFlags();
		}
		this.relevantButton.$button.attr( 'aria-pressed', this.isRelevantForUser ? 'true' : 'false' );

	};

}( mediaWiki, jQuery, blueSpice ) );
