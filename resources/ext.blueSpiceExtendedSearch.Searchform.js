( function ( mw, $ ) {
	const $input = $( '#bs-extendedsearch-input' );
	const $searchButton = $( 'button#mw-searchButton' );
	$( $input ).on( 'focus', () => {
		$searchButton.addClass( 'focusBorder' );
		$input.addClass( 'focusBorder' );
	} );
	$( $input ).on( 'focusout', () => {
		$searchButton.removeClass( 'focusBorder' );
		$input.removeClass( 'focusBorder' );
	} );

}( mediaWiki, jQuery ) );
