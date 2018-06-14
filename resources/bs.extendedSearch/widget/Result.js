( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.ResultWidget = function( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.ResultWidget.parent.call( this, cfg );

		bs.extendedSearch.mixin.ResultImage.call( this, cfg );
		bs.extendedSearch.mixin.ResultSecondaryInfo.call( this, cfg );
		bs.extendedSearch.mixin.ResultRelevanceControl.call( this, cfg );

		this.headerText = cfg.headerText;
		this.headerUri = cfg.headerUri;
		this.headerAnchor = cfg.page_anchor || null;
		this.secondaryInfos = cfg.secondaryInfos || [];
		this.highlight = cfg.highlight || '';
		this.featured = cfg.featured || false;

		this.id = cfg._id;
		this.rawResult = cfg.raw_result || {};

		this.$dataContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-data-container' );

		this.$headerContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-header-container' );

		if( this.headerAnchor ) {
			this.$header = $( this.headerAnchor );
		} else {
			this.$header = $( '<a>' )
				.attr( 'href', this.headerUri )
				.html( this.headerText );
		}

		this.$header.addClass( 'bs-extendedsearch-result-header' );

		this.$headerContainer.append( this.$header );

		this.$highlightContainer = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-highlight-container' )
			.append(
				$( '<span>' ).html( this.highlight )
			);

		this.$dataContainer.append( this.$headerContainer, this.$topSecondaryInfo, this.$highlightContainer, this.$bottomSecondaryInfo );
		this.$element = $( '<div>' )
			.addClass( 'bs-extendedsearch-result-container' )
			.append( this.$image, this.$dataContainer, this.$relevanceControl );

		if( this.featured ) {
			this.$element.addClass( 'bs-extendedsearch-result-featured' );
		}
	}

	OO.inheritClass( bs.extendedSearch.ResultWidget, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultImage );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultSecondaryInfo );
	OO.mixinClass( bs.extendedSearch.ResultWidget, bs.extendedSearch.mixin.ResultRelevanceControl );

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
