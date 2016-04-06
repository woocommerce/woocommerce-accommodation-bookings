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

    $('#rates_rows').sortable({
        items:'tr',
        cursor:'move',
        axis:'y',
        handle: '.sort',
        scrollSensitivity:40,
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.65,
        placeholder: 'wc-metabox-sortable-placeholder',
        start:function(event,ui){
            ui.item.css('background-color','#f6f6f6');
        },
        stop:function(event,ui){
            ui.item.removeAttr('style');
        }
    });

} );
