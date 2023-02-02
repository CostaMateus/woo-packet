( function ( $ ) {
	"use strict";

    $( document ).ready( function() {
        var open  = "open";
        var close = "close";

        $( "<button class='close show-hide' style='line-height:24px;' type='button'>Ver</button>" ).insertAfter( $( ".input-woopacket" ) );
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

    window.wooPacketGenerateTag = function ( id )
    {
        $.ajax( {
            url  : "/wp-admin/admin-ajax.php",
            type : "POST",
            data : {
                action   : "generate_tag_correios",
                order_id : id,
            },
            success : function( data ) {
                let result = JSON.parse( data );

                if ( result.error )
                    console.log( result );
                else
                    window.location.reload();
            },
            error : function( err ) {
                console.log( err );
            }
        } );
    }

} )( jQuery );