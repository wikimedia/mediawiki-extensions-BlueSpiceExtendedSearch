( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.ResultWidget = function( cfg, mobile ) {
		cfg = cfg || {};

		this.mobile = mobile || false;

		bs.extendedSearch.ResultWidget.parent.call( this, cfg );

		this.headerText = cfg.headerText;
		this.headerUri = cfg.headerUri;
		this.headerAnchor = cfg.page_anchor || null;
		this.secondaryInfos = cfg.secondaryInfos || [];
		this.highlight = cfg.highlight || '';
		this.featured = cfg.featured || false;

		this.isExternal = cfg.isExternal || false;

		// If we are in desktop mode, show sections of secondaryInfos
		if( !this.mobile && this.secondaryInfos.top.items.length > 0 ) {
			for( var idx in this.secondaryInfos.top.items ) {
				if( this.secondaryInfos.top.items[ idx ].name === 'sections' ) {
					this.sections = this.secondaryInfos.top.items[ idx ];
					this.secondaryInfos.top.items.splice( idx, 1 );
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

		if( this.headerAnchor && !this.isExternal ) {
			this.$header = $( this.headerAnchor );
		} else {
			this.$header = $( '<a>' )
				.attr( 'href', this.headerUri )
				.html( this.headerText );
			if( this.isExternal ) {
				this.$header.attr( 'target', '_blank' );
			}
		}

		this.$header.addClass( 'bs-extendedsearch-result-header' );
		this.$image.on( 'click', { pageAnchor: this.$header }, this.onImageClick );

		this.$headerContainer.append( this.$header, this.$originalTitle );

		this.$highlightContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-highlight-container' )
			.append(
				$( '<span>' ).html( this.highlight )
			);

		this.$dataContainer.append( this.$headerContainer, this.$topSecondaryInfo, this.$highlightContainer, this.$bottomSecondaryInfo );

		this.$element = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-container' )
			.append( this.$image, this.$dataContainer, this.$relevanceControl );

		if( this.sections ) {
			this.$linksContainer = $( '<div>' )
				.addClass( 'bs-extendedsearch-result-links-container' );

			if( this.sections ) {
				this.$dataContainer.addClass( 'short' );
				this.$sectionContainer = $( '<div>' )
					.addClass( 'bs-extendedsearch-result-section-container' );
				this.$sectionContainer.append( new OO.ui.LabelWidget( {
					label: mw.message( this.sections.labelKey ).plain()
				} ).$element );
				this.$sectionContainer.append( this.sections.value );
				this.$linksContainer.append( this.$sectionContainer );
			}

			this.$element.append( this.$linksContainer );
		}

		if( this.featured ) {
			this.$element.addClass( 'bs-extendedsearch-result-featured' );
		}

		if( this.mobile ) {
			this.$element.addClass( 'bs-extendedsearch-result-mobile' );
		}
	}

	OO.inheritClass( bs.extendedSearch.ResultWidget, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultImage );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultSecondaryInfo );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultRelevanceControl );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultOriginalTitle );

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

	bs.extendedSearch.ResultWidget.prototype.onImageClick = function( e ) {
		var anchor = e.data.pageAnchor;
		window.location = anchor.attr( 'href' );
	}

	//Experimental
	bs.extendedSearch.ResultWidget.prototype.onRelevant = function( e ) {
		this.userRelevance = this.userRelevance == 1 ? 0 : 1;
		this.makeChangeRelevanceCall();
	}

	bs.extendedSearch.ResultWidget.prototype.onNotRelevant = function( e ) {
		this.userRelevance = this.userRelevance == -1 ? 0 : -1;
		this.makeChangeRelevanceCall();
	}

	bs.extendedSearch.ResultWidget.prototype.makeChangeRelevanceCall = function() {
		var queryData = {
			relevanceData: JSON.stringify( {
				resultId: this.getId(),
				value: this.userRelevance
			} )
		}

		var promise = bs.extendedSearch.SearchCenter.runApiCall(
			queryData,
			'bs-extendedsearch-resultrelevance'
		);

		var me = this;
		promise.done( function( response ) {
			if( response.status && response.status == 1 ) {
				me.updateRelevanceButtons();
			}
		} );
	}

	bs.extendedSearch.ResultWidget.prototype.updateRelevanceButtons = function() {
		if( this.userRelevance == -1 ) {
			//this.notRelevantButton.setIcon( 'unBlock' );
			this.relevantButton.setIcon( 'star' );
		} else if ( this.userRelevance == 1 ) {
			//this.notRelevantButton.setIcon( 'block' );
			this.relevantButton.setIcon( 'unStar' );
		} else {
			//this.notRelevantButton.setIcon( 'block' );
			this.relevantButton.setIcon( 'star' );
		}
	}
	//End Experimental

} )( mediaWiki, jQuery, blueSpice, document );
