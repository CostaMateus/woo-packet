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
