jQuery( function( $ ) {

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
