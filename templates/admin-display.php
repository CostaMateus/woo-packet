<?php
/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Packet
 * @subpackage Woo_Packet/templates
 * @author     Mateus Costa <mateus@costamateus.com.br>
 */
?>

<div class="wrap">
	<h2>Woo Packet</h2>
	<h3 style="margin-top:0;" >Envios para o Brasil via Correios</h3>

	<?php
		$instance = null;
		$shipping = new WC_Shipping_Zones();
		$zones    = $shipping->get_zones();
		$index    = array_key_first( $zones);
		$methods  = $zones[ $index ][ "shipping_methods" ];

		foreach ( $methods as $key => $method )
			if ( $method && $method->id === Woo_Packet_Shipping::ID )
				$instance = $method->instance_id;

        $url      = esc_url( admin_url( "admin.php?page=wc-settings&tab=shipping&instance_id={$instance}" ) );
        $text     = __( "Configurações método de entrega", Woo_Packet_Shipping::ID );
        $link     = "<a href='{$url}' >{$text}</a>";

        echo "<p>{$link}</p>";
    ?>

	<hr>
	<?php settings_errors(); ?>

	<form method="POST" action="options.php">
		<?php
			settings_fields( "woo_packet_general_settings" );
			do_settings_sections( "woo_packet_general_settings" );
			submit_button();
		?>
	</form>

</div>
