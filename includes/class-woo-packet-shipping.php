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
 * Shipping method class.
 */
class Woo_Packet_Shipping extends WC_Shipping_Method
{
    const ID = WOO_PACKET_DOMAIN;

    protected $api                = null;
    protected $log                = null;
    protected $shipping_class_id  = null;
    protected $show_delivery_time = null;
    protected $additional_time    = null;

    /**
     * Initialize the Correios shipping method.
     *
     * @param int $instance_id Shipping zone instance ID.
     */
    public function __construct( $instance_id = 0 )
    {
        $this->instance_id        = absint( $instance_id );
        $this->api                = new Woo_Packet_Webservice();
        $this->log                = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

        $this->id                 = WOO_PACKET_DOMAIN;

        $this->method_title       = __( "Woo Packet",            $this->id );
        $this->method_description = __( "Entrega internacional", $this->id );

        $this->enabled            = "yes";
        $this->title              = "Woo Packet";

        $this->supports           = [
            "shipping-zones",
            "instance-settings",
        ];

        $this->init();
    }

    /**
     * Init your settings
     *
     * @since   1.0.0
     * @access  public
     * @return  void
     */
    function init()
    {
        // Load the settings API
        // This is part of the settings API.
        // Override the method to add your own settings
        $this->init_form_fields();

        // Define user set variables.
        $this->enabled            = $this->get_option( "enabled"                );
        $this->title              = $this->get_option( "title"                  );
        $this->shipping_class_id  = $this->get_option( "shipping_class_id", "0" );
        $this->show_delivery_time = $this->get_option( "show_delivery_time"     );
        $this->additional_time    = $this->get_option( "additional_time"        );

        // Save settings in admin if you have any defined
        add_action( "woocommerce_update_options_shipping_" . $this->id, [ $this, "process_admin_options" ] );
    }

    /**
     * Admin options fields.
     *
     * @since   1.0.0
     */
    public function init_form_fields()
    {
        $this->instance_form_fields = [
            "enabled"            => [
                "type"        => "checkbox",
                "title"       => __( "Habilitar/Desabilitar",                                                   $this->id ),
                "label"       => __( "Ativar este método de envio",                                             $this->id ),
                "default"     => "yes",
            ],
            "title"              => [
                "type"        => "text",
                "title"       => __( "Título",                                                                  $this->id ),
                "description" => __( "Isto controla o título que o usuário vê durante o checkout.",             $this->id ),
                "desc_tip"    => true,
                "default"     => $this->method_title,
            ],
            "behavior_options"   => [
                "title"       => __( "Opções de comportamento",                                                 $this->id ),
                "type"        => "title",
                "default"     => "",
            ],
            "shipping_class_id"  => [
                "type"        => "multiselect",
                "title"       => __( "Classe de entrega",                                                       $this->id ),
                "description" => __( "Se necessário, selecione uma classe de envio para aplicar neste método.", $this->id ),
                "desc_tip"    => true,
                "default"     => "",
                "class"       => "wc-enhanced-select",
                "options"     => $this->get_shipping_classes_options(),
            ],
            "show_delivery_time" => [
                "type"        => "checkbox",
                "title"       => __( "Tempo de entrega",                                                        $this->id ),
                "label"       => __( "Exibir estimativa de entrega",                                            $this->id ),
                "description" => __( "Exibir o tempo estimado de entrega em dias úteis.",                       $this->id ),
                "desc_tip"    => true,
                "default"     => "no",
            ],
            "additional_time"    => [
                "type"        => "text",
                "title"       => __( "Dias adicionais",                                                         $this->id ),
                "description" => __( "Dias úteis adicionais para a estimativa de entrega.",                     $this->id ),
                "desc_tip"    => true,
                "default"     => "0",
                "placeholder" => "0",
            ],
        ];
    }

    /**
     * Get shipping classes options.
     *
     * @since   1.0.0
     * @return  array
     */
    protected function get_shipping_classes_options()
    {
        $shipping_classes = WC()->shipping->get_shipping_classes();
        $options          = [
            "-1" => __( "Qualquer classe", $this->id ),
            "0"  => __( "Nenhuma classe",  $this->id ),
        ];

        if ( !empty( $shipping_classes ) ) $options += wp_list_pluck( $shipping_classes, "name", "term_id" );

        return $options;
    }

    /**
     * Check if package uses only the selected shipping class.
     *
     * @since   1.0.0
     * @param   array $package Cart package.
     * @return  bool
     */
    protected function has_only_selected_shipping_class( $package )
    {
        $only_selected = true;

        if ( in_array( -1, $this->shipping_class_id ) ) return $only_selected;

        foreach ( $package[ "contents" ] as $item_id => $values )
        {
            $product = $values[ "data" ];
            $qty     = $values[ "quantity" ];

            if ( $qty > 0 && $product->needs_shipping() )
            {
                if ( !in_array( $product->get_shipping_class_id(), $this->shipping_class_id ) )
                {
                    $only_selected = false;
                    break;
                }
            }
        }

        return $only_selected;
    }

    /**
     * calculate_shipping function.
     *
     * @since   1.0.0
     * @access  public
     * @param   mixed $package
     * @return  void
     */
    public function calculate_shipping( $package = [] )
    {
        // Check if valid to be calculeted
        if ( "" === $package[ "destination" ][ "postcode" ] || "BR" !== $package[ "destination" ][ "country" ] ) return;

        // Check if complete postcode
        $postcode = strlen($package[ "destination" ][ "postcode" ] );
        if ( $postcode < 8 || $postcode > 9 ) return;

        // Check for shipping classes.
        if ( !$this->has_only_selected_shipping_class( $package ) ) return;

        $this->log( $package, "critical" );

        // executa a consulta a api
        $shipping = $this->get_rate( $package );

        if ( $shipping[ "errorCode" ] )
        {
            $this->log( $shipping, "critical" );
            return;
        }

        $meta  = [];
        $costs = [];
        $time  = 0;

        foreach ( $shipping[ "data" ] as $data )
        {
            if ( $data[ "errorCode" ] ) return;

            $item         = $data[ "data"       ];
            $id           = $item[ "product_id" ];
            $costs[ $id ] = ( float ) $item[ "deliveryPrice" ];
            $time         = ( $item[ "deliveryTime" ] > $time ) ? $item[ "deliveryTime" ] : $time;
        }

        // Display delivery
        if ( "yes" === $this->show_delivery_time )
            $meta = [ "_delivery_forecast" => intval( $time ) + intval( $this->additional_time ), ];

        // Create the rate
        $rate = [
            "id"        => $this->id . $this->instance_id,
            "label"     => $this->title,
            "cost"      => $costs,
            "calc_tax"  => "per_item",
            "meta_data" => $meta,
            "package"   => $package
        ];

        // Register the rate
        $this->add_rate( $rate );
    }

	/**
	 * Get shipping rate.
	 *
     * @since   1.0.0
	 * @param   array $package Cart package.
	 * @return  array|null
	 */
	protected function get_rate( $package )
    {
        $cep = get_option( "woo_packet_shop_zipcode" );
        $cep = preg_replace( "/\D/", "", $cep );

        $this->log( $package, "critical" );

        $this->api->set_service( "33227" );
        $this->api->set_package( $package );
        $this->api->set_origin_postcode( $cep );
        $this->api->set_destination_postcode( $package[ "destination" ][ "postcode" ] );
        $this->api->set_own_hands( "N" );
        $this->api->set_receipt_notice( "N" );
        $this->api->set_minimum_height();
        $this->api->set_minimum_width();
        $this->api->set_minimum_length();

        $shipping = $this->api->get_shipping();

        $this->log( $shipping, "critical" );

        return $shipping;
	}

    /**
     * Process the package
     *
     * @param array $package
     * @return array
     */
    private function process_package( $package )
    {
        $postalCode = $package[ "destination" ][ "postcode" ];
        $items      = [];

        foreach ( $package[ "contents" ] as $content )
        {
            $product = wc_get_product( $content[ "product_id" ] );
            $items[] = [
                "sku"      => $product->get_sku(),
                "quantity" => $content["quantity"]
            ];
        }

        return [
            "postalCode" => $postalCode,
            "items"      => $items
        ];
    }

    /**
     * Register log
     *
	 * @since 	1.0.0
     * @param   array   $data
     * @param   string  $type
     * @return  void
     */
    public function log( $data, string $type )
    {
        $this->log->add( $this->id, json_encode( $data ), $type );
    }
}
