<?php
/**
 * Admin View: Notice - WooCommerce Extra Checkout Fields for Brazil missing.
 *
 *
 * @package Woo_Packet/Notices
 */
defined( "ABSPATH" ) || exit;

$is_installed = false;

if ( function_exists( "get_plugins" ) )
{
	$all_plugins  = get_plugins();
	$key          = "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php";
	$is_installed = array_key_exists( $key, $all_plugins );
}

?>

<div class="error">
	<p>
		<strong>
			<?php esc_html_e( "Woo Packet", "woo_packet" ); ?>
		</strong>
		<?php esc_html_e( "depende da última versão do Extra Checkout Fields for Brazil para funcionar!", "woo_packet" ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( "plugins.php?action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php&plugin_status=active" ), "activate-plugin_woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) ); ?>" class="button button-primary">
				<?php esc_html_e( "Ative Extra Checkout Fields for Brazil", "woo_packet" ); ?>
			</a>
		</p>
	<?php else :
		if ( current_user_can( "install_plugins" ) )
		{
			$url = wp_nonce_url( self_admin_url( "update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil" ), "install-plugin_woocommerce-extra-checkout-fields-for-brazil" );
		}
		else
		{
			$url = "http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/";
		}
	?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary">
				<?php esc_html_e( "Instale Extra Checkout Fields for Brazil", "woo_packet" ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
