( function ( $ ) {
	"use strict";

    $( document ).ready( function() {
        var open  = "open";
        var close = "close";

        $( "<button class='close show-hide' style='line-height:24px;' type='button'>Ver</button>" ).insertAfter( $( "input[type='password']" ) );
        $( ".show-hide" ).on( "click", function() {
            if ( $( this ).hasClass( close ) )
            {
                $( this ).prev( "input[type='password']" ).prop( "type", "text"     );
                $( this ).removeClass( close );
                $( this ).addClass( open );
            }
            else
            {
                $( this ).prev( "input[type='text']"     ).prop( "type", "password" );
                $( this ).removeClass( open );
                $( this ).addClass( close );
            }
        } );
    } );
} )( jQuery );