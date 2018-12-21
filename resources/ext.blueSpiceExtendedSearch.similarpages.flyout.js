( function( mw, $, bs, undefined ) {
	bs.util.registerNamespace( 'bs.extendedsearch.flyout' );

	bs.extendedsearch.flyout.makeSimilarPages = function( flyout, basicData ) {
		if( mw.config.get( 'bsgESSimilarPages' ) !== null ) {
			var similarPages = JSON.parse( mw.config.get( 'bsgESSimilarPages' ) );
			return {
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
			}
		}
		return {};
	};

} )( mediaWiki, jQuery, blueSpice );
