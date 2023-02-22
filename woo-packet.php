<?php
/**
 * Plugin Name:          WooPacket
 * Text Domain:          woo_packet
 * Plugin URI:           https://costamateus.com.br/
 * Description:          Envios para o Brasil via Correios
 * Version:              1.0.1
 *
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 *
 * RequiresPHP:          7.4
 * RequiresWP:           6.0
 * WC requires at least: 6.3
 * WC tested up to:      6.9
 *
 * @since	1.0.0
 * @package	Woo_Packet
 */
defined( "ABSPATH" ) || exit;

if ( !in_array( "woocommerce/woocommerce.php", apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) ) return;

// Plugin constants.
define( "WOO_PACKET_VERSION", "1.0.1"      );
define( "WOO_PACKET_DOMAIN",  "woo_packet" );
define( "WOO_PACKET_FILE",    __FILE__     );

if ( !class_exists( "Woo_Packet" ) )
{
	include_once dirname( __FILE__ ) . "/includes/class-woo-packet.php";
	add_action( "plugins_loaded", [ "Woo_Packet", "init" ], 111 );
}
