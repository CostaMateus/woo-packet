<?php
/**
 * Plugin Shipping Class.
 *
 * @package Woo_Packet/Classes
 * @since   1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Woo Packet shipping method class.
 */
class Woo_Packet_Shipping extends WC_Shipping_Method
{
    const ID = WOO_PACKET_DOMAIN;
}
