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

    window.wooPacketGenerateTag = function ( e, id )
    {
        $( "#woo_packet_settings" ).remove();

        $( e ).find( "div" ).removeClass( "woo-packet-d-none" );

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
                {
                    let content = wooPacketShowMessage( "error", result.message );
                    $( content ).insertAfter( ".wp-header-end" );
                    $( e ).find( "div" ).addClass( "woo-packet-d-none" );

                    console.log( result );
                }
                else
                {
                    window.location.reload();
                }
            },
            error : function( err ) {
                console.log( err );

                let message = "Algo deu errado! Recarregue a página e tente novamente.";
                let content = wooPacketShowMessage( "error", message );

                $( content ).insertAfter( ".wp-header-end" );
                $( e ).find( "div" ).addClass( "woo-packet-d-none" );
            }
        } );
    }

    window.wooPacketShowMessage = function ( type, message )
    {
        return `<div id="woo_packet_settings" class="notice notice-${type} is-dismissible" >
            <p><strong>${message}</strong></p>
            <button type="button" class="notice-dismiss" onclick="return wooPacketDismissNotice(this);" ></button>
        </div>`;
    }

    window.wooPacketDismissNotice = function ( e )
    {
        $( e ).parent().remove();
    }

} )( jQuery );