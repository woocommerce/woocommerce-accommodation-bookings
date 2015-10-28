jQuery( function( $ ) {

	$( 'select#product-type' ).change( function () {
		var select_val = $( this ).val();

		if ( 'accommodation-booking' === select_val ) {
			$( '.hide_if_accommodation_booking' ).hide();
		} else {
			$( '.hide_if_accommodation_booking' ).show();
		}
	} ).change();

} );
