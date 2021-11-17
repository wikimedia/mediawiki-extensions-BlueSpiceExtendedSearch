( function( mw, $ ) {
	var $input = $('#bs-extendedsearch-input');
	var $searchButton = $('button#mw-searchButton' );
	$( $input ).focus( function () {
		$searchButton.addClass('focusBorder');
		$input.addClass('focusBorder');
	});
	$( $input ).focusout( function () {
		$searchButton.removeClass('focusBorder');
		$input.removeClass('focusBorder');
	});

} ) ( mediaWiki, jQuery );