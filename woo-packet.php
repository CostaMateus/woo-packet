<?php
/**
 * Plugin Name:          WooPacket
 * Plugin URI:           https://www.roadpass.com.br/
 * Description:          Envios para o Brasil via Correios
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 * Version:              1.0.0
 * Text Domain:          woo_packet
 * RequiresPHP:          7.4
 * RequiresWP:           6.0
 * WC requires at least: 6.3
 * WC tested up to:      6.9
 *
 * @package Woo_Packet
 */
defined( "ABSPATH" ) || exit;

// Plugin constants.
define( "WPACKET_VERSION",     "1.0.0"  );
define( "WPACKET_PLUGIN_FILE", __FILE__ );

if ( !class_exists( "Woo_Packet" ) )
{
	include_once dirname( __FILE__ ) . "/includes/class-woo-packet.php";
	add_action( "plugins_loaded", [ "Woo_Packet", "init" ] );
}
