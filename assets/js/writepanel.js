jQuery( function( $ ) {

	$( '.hide_if_accommodation_booking' ).hide();
	$( '.show_if_accommodation_booking' ).hide();

	$( 'select#product-type' ).change( function () {
		var select_val = $( this ).val();

		if ( 'accommodation-booking' === select_val ) {
			$( '.show_if_accommodation_booking' ).show();
			$( '.hide_if_accommodation_booking' ).hide();
		} else {
			$( '.show_if_accommodation_booking' ).hide();
			$( '.hide_if_accommodation_booking' ).show();
		}
	} ).change();

} );
