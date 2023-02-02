<?php
/**
 * Plugin's main class
 *
 * @package Woo_Packet
 * @version 1.0
 */

/**
 * Woo_Packet bootstrap class.
 */
class Woo_Packet
{
	/**
	 * Instance of this class.
	 *
	 * @since 	1.0.0
	 * @var 	object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since 	1.0.0
	 * @return 	object A single instance of this class.
	 */
	public static function init()
    {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) self::$instance = new self;
		return self::$instance;
	}

	private function __construct()
    {
		if ( class_exists( "WooCommerce" ) )
		{
			// API Class
			include_once plugin_dir_path( dirname( __FILE__ ) ) . "/includes/class-woo-packet-api.php";

			add_action( "admin_menu",                            [ $this, "add_admin_menu"            ], 11 );
			add_action( "admin_init",                            [ $this, "register_and_build_fields" ]     );
			add_action( "manage_shop_order_posts_custom_column", [ $this, "add_column_content_order"  ]     );
			// add_action( "admin_print_styles",                    [ $this, "add_style_column"          ]     );
			add_action( "wp_ajax_generate_tag_correios",         [ $this, "generate_tag_correios"     ]     );

			add_filter( "plugin_action_links_" . plugin_basename( WOO_PACKET_FILE ), [ $this, "plugin_action_links"    ]     );
			add_filter( "manage_edit-shop_order_columns",                            [ $this, "add_column_title_order" ], 20 );

			wp_enqueue_style(  WOO_PACKET_DOMAIN, plugin_dir_url( dirname( __FILE__ ) ) . "assets/css/admin.css", [],           WOO_PACKET_VERSION, "all" );
			wp_enqueue_script( WOO_PACKET_DOMAIN, plugin_dir_url( dirname( __FILE__ ) ) . "assets/js/admin.js",   [ "jquery" ], WOO_PACKET_VERSION, false );
		}
		else
		{
			add_action( "admin_notices", [ $this, "woocommerce_missing_notice" ] );
		}
	}

	/**
	 * Action links.
	 *
	 * @since 	1.0.0
	 * @param 	array $links
	 * @return 	array
	 */
	public static function plugin_action_links( array $links )
    {
        $url      = esc_url( admin_url( "admin.php?page=" . WOO_PACKET_DOMAIN ) );
		$woo_link = [ "<a href='{$url}' >Configurações</a>" ];

		return array_merge( $woo_link, $links );
	}

	/**
	 * WooCommerce missing notice.
	 *
	 * @since	1.0.0
	 */
	public static function woocommerce_missing_notice()
	{
		include_once plugin_dir_path( dirname( __FILE__ ) ) . "/template/missing-woocommerce.php";
	}

	/**
	 * Add submenu on WP Settings menu
	 *
	 * @since	1.0.0
	 */
	public function add_admin_menu()
	{
		add_menu_page(
			"Woo Packet",
			"Woo Packet",
			"manage_options",
			WOO_PACKET_DOMAIN,
			[ $this, "display_admin_menu" ],
			plugins_url( "assets/images/blu_20.png", WOO_PACKET_FILE ),
            65
		);
	}

	public function display_admin_menu()
	{
		if ( !current_user_can( "manage_options" ) ) wp_die( __( "Você não tem permissões suficientes para acessar esta página." ) );

		// $active_tab = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "general";

		if ( isset( $_GET[ "error_message" ] ) ) do_action( "admin_notices", $_GET[ "error_message" ] );

		require_once ( plugin_dir_path( dirname( __FILE__ ) ) . "templates/admin-display.php" );
	}


	/**
	 * Register and build the plugin fields
	 *
	 * @since 	1.0.0
	 */
	public function register_and_build_fields()
	{
		$page    = WOO_PACKET_DOMAIN . "_general_settings";
		$section = WOO_PACKET_DOMAIN . "_section";

		$fields  = [
			[
				"key"   => WOO_PACKET_DOMAIN . "_status",
				"title" => "Ativar / Desativar",
				"label" => "Ativar API Gateway",
			],[
				"key"   => WOO_PACKET_DOMAIN . "_id",
				"title" => "ID usuário",
			],[
				"key"   => WOO_PACKET_DOMAIN . "_user",
				"title" => "Usuário",
			],[
				"key"   => WOO_PACKET_DOMAIN . "_pass",
				"title" => "Senha",
			],
		];

		register_setting( $page, WOO_PACKET_DOMAIN . "_options" );

		$this->add_allowed_options( $page, $fields );

		add_settings_section( $section, "API Gateway", [ $this, "message_section" ], $page );

		foreach ( $fields as $index => $field )
		{
			$this->add_settings_field( $index, $field, $page, $section );
			$this->register_settings( $field, $page );
		}
	}

	/**
	 * Adds plugin fields to allowed options
	 *
	 * @since 	1.0.0
	 *
	 * @param 	string $prefix
	 * @param 	string $page
	 * @param 	array $data
	 */
	public function add_allowed_options( $page, $data )
	{
		$new_options[ $page ] = [];

		foreach ( $data as $field )
			$new_options[ $page ][] = $field[ "key" ];

		add_allowed_options( $new_options );
	}

	/**
	 * Echos out any content at the top of the section (between heading and fields).
	 *
	 * @since 	1.0.0
	 *
	 * @param 	array $args
	 */
	public function message_section( $args )
	{
		echo "<p>Configurações da API</p>";
	}

	/**
	 * Configure each plugin field
	 *
	 * @since	1.0.0
	 *
	 * @param 	string $index
	 * @param 	array $field
	 * @param 	string $page
	 */
	public function add_settings_field( $index, $field, $page, $section )
	{
		$subtype = ( $index == 0 ) ? "checkbox" : ( ( in_array( $index, [ 1, 2, 3 ] ) ) ? "password" : "text" ) ;

		add_settings_field(
            $field[ "key" ], $field[ "title" ], [ $this, "render_settings_field" ], $page, $section,
			[
				"type"             => "input",
				"subtype"          => $subtype,
				"label"            => ( $index == 0 ) ? $field[ "label" ] : "",
				"value"            => "",
				"id"               => $field[ "key" ],
				"name"             => $field[ "key" ],
				"required"         => true,
				"get_options_list" => "",
				"value_type"       => "normal",
				"wp_data"          => "option"
			]
		);
	}

	/**
	 * Renders each field according to the configured args
	 *
	 * @since 	1.0.0
	 *
	 * @param 	array $args
	 */
	public function render_settings_field( $args )
	{
		$value  = get_option( $args[ "name" ] );
		$status = get_option( WOO_PACKET_DOMAIN . "_status" );

		if ( $args[ "subtype" ] != "checkbox" )
		{
			$required = ( $args[ "required" ] && $status    ) ? "required='required'" : "";

			if ( isset( $args[ "disabled" ] ) )
			{
				echo "<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}_disabled' name='{$args[ "name" ]}_disabled' disabled value='" . esc_attr( $value ) . "' />
					  <input type='hidden' id='{$args[ "id" ]}' name='{$args[ "name" ]}' value='" . esc_attr( $value ) . "' />";
			}
			else
			{
				echo "<input class='input-woopacket' type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' {$required} name='{$args[ "name" ]}' value='" . esc_attr( $value ) . "' />";
			}
		}
		else
		{
			$checked = ( $value ) ? "checked" : "";
			echo "<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' name='{$args[ "name" ]}' {$checked} />";
			echo "<label for='{$args[ "id" ]}' >{$args[ "label" ]}</label>";
		}
	}

	/**
	 * Register each plugin field
	 *
	 * @since 	1.0.0
	 *
	 * @param 	array $field
	 * @param 	string $page
	 */
	public function register_settings( $field, $page )
	{
		register_setting( $page, $field[ "key" ] );
	}

	/**
	 * Adds 'WooPacket' column header to 'Orders' page immediately after 'Status' column.
	 *
	 * @since 	1.0.0
	 *
	 * @param 	array $columns
	 * @return 	array $new_columns
	 */
	function add_column_title_order( $columns )
	{
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info )
		{
			$new_columns[ $column_name ] = $column_info;
			$name                        = "order_" . WOO_PACKET_DOMAIN;

			if ( "order_status" === $column_name )
				$new_columns[ $name ] = "WooPacket";
		}

		return $new_columns;
	}

	/**
	 * Adds 'WooPacket' column content to 'Orders' page immediately after 'Status' column.
	 *
	 * @since 	1.0.0
	 *
	 * @param 	array $column name of column being displayed
	 */
	function add_column_content_order( $column )
	{
		global $post;

		$name = "order_" . WOO_PACKET_DOMAIN;

		if ( $name === $column )
		{
			$url  = home_url()     . "/wp-content/uploads/woo-packet-tags/order_{$post->ID}.pdf";
			$path = WP_CONTENT_DIR . "/uploads/woo-packet-tags/order_{$post->ID}.pdf";

			$text = ( file_exists( $path ) )
						? "<a href='{$url}' target='_blank' download >Baixar etiqueta</a>"
						: "<a onclick='return wooPacketGenerateTag({$post->ID});' >Gerar etiqueta</a>";

			echo $text;
		}
	}

	// /**
	//  * Adjusts the styles for the new 'WooPacket' column.
	//  *
	//  * @since 	1.0.0
	//  */
	// function add_style_column()
	// {
	// 	$css = ".widefat .column-order_date, .widefat .column-order_  . WOO_PACKET_DOMAIN{ width: 9%; }";
	// 	wp_add_inline_style( "woocommerce_admin_styles", $css );
	// }

	/**
	 * Generate tag Correios
	 *
	 * @return void
	 */
	function generate_tag_correios()
	{
        $order_id = $_POST[ "order_id" ];

		$path     = WP_CONTENT_DIR . "/uploads/woo-packet-tags/";

		if ( !file_exists( $path ) ) mkdir( $path, 0755 );

		$location = $path . "/order_{$order_id}.log";

		if ( file_put_contents( $location, file_get_contents( "https://cdn-icons-png.flaticon.com/512/3135/3135715.png" ) ) )
		{
			$result = [
				"code"    => 200,
				"error"   => false,
				"message" => "Etiqueta gerada com sucesso!"
			];
		}
		else
		{
			$result = [
				"code"    => 400,
				"error"   => true,
				"message" => "Falha na criação da etiqueta"
			];
		}

        echo json_encode( $result );
        die();
	}
}
