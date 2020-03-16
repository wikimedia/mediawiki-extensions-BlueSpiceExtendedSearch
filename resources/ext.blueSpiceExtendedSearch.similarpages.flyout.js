( function( mw, $, bs, undefined ) {
	bs.util.registerNamespace( 'bs.extendedsearch.flyout' );

	bs.extendedsearch.flyout.makeSimilarPages = function( flyout, basicData ) {
		var result = $.Deferred();

		bs.config.getDeferred( 'ESSimilarPages' )
			.done( function( similarPages ) {
				if ( !Array.isArray( similarPages ) || similarPages.length === 0 ) {
					result.resolve( {} );
				}
				result.resolve( {
					centerLeft: [
						// This is a hard dependency to BSArticleInfo, but this cannot be called
						// unless BSArticleInfo is present
						Ext.create( 'BS.ArticleInfo.panel.LinkList', {
							linkList: similarPages,
							storeField: 'page_anchor',
							title: mw.message( 'bs-extendedsearch-flyout-similar-pages-title' ).plain(),
							emptyText: mw.message( 'bs-extendedsearch-flyout-similar-pages-emptytext' ).plain(),
							listType: 'pills'
						} ),
					]
				} );
			} )
			.fail( function() {
				result.resolve( {} );
			} );

		return result.promise();
	};

} )( mediaWiki, jQuery, blueSpice );
