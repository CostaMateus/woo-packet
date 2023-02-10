<?php
/**
 * Plugin's API class
 *
 * @package Woo_Packet/Classes
 * @since   1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Woo_Packet_Api class.
 */
class Woo_Packet_Api
{
	private $id         = WOO_PACKET_DOMAIN;

	private $api        = null;

    private $url        = "https://api.sunlogcargo.com/declaracao/";
    private $header     = [
        "Content-Type: text/plain",
        "Cookie: PHPSESSID=0b73a14e5831d36f3c43daf6ff6cd6e8",
    ];

    private $user       = null;
    private $userId     = null;
    private $userPsw    = null;

    private $log        = null;
    private $status     = null;

    private $tagPrefix  = null;
    private $shopName   = null;
    private $shopEmail  = null;
    private $shopPhone  = null;
    private $shopAddr   = null;
    private $shopComplt = null;
    private $shopNumber = null;
    private $shopState  = null;
    private $shopCity   = null;
    private $shopCep    = null;

    public function __construct()
    {
		$this->log    = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
        $this->api    = new Woo_Packet_Webservice();

        $this->status = get_option( "woo_packet_api_status" );

        if ( $this->status == "on" )
        {
            $this->user       = get_option( "woo_packet_api_user"            );
            $this->userId     = get_option( "woo_packet_api_user_id"         );
            $this->userPsw    = get_option( "woo_packet_api_user_pass"       );

			$this->tagPrefix  = get_option( "woo_packet_tag_prefix"          );
			$this->shopName   = get_option( "woo_packet_shop_name"           );
			$this->shopEmail  = get_option( "woo_packet_shop_email"          );
			$this->shopPhone  = get_option( "woo_packet_shop_phone"          );
			$this->shopAddr   = get_option( "woo_packet_shop_address"        );
			$this->shopComplt = get_option( "woo_packet_shop_address_2"      );
			$this->shopNumber = get_option( "woo_packet_shop_address_number" );
			$this->shopState  = get_option( "woo_packet_shop_state"          );
			$this->shopCity   = get_option( "woo_packet_shop_city"           );
			$this->shopCep    = get_option( "woo_packet_shop_zipcode"        );
        }
    }

    /**
     *
     *
	 * @since 	1.0.0
     * @param   integer|string    $order_id
     * @return  void
     */
    public function generate_tag( $order_id )
    {
        if ( $this->status != "on" )
        {
            $link     = esc_url( admin_url( "admin.php?page=" . WOO_PACKET_DOMAIN ) );
            $response = [
                "code"    => 400,
                "success" => false,
                "message" => "Falha ao gerar etiqueta do pedido #{$order_id}, o plugin está desativado. <a href='{$link}' >Clique aqui</a> para ativar.",
                "error"   => 4001,
                "data"    => null,
            ];

            $this->log( $response, "critical" );

            return $response;
        }

        try
        {
            $order   = wc_get_order( $order_id );

            if ( !$order ) throw new Exception( "Pedido inválido #{$order_id}." );

            $this->get_shipping( $order );

            $sCep    = preg_replace( "/\D/", "", $this->shopCep   );
            $sPhone  = preg_replace( "/\D/", "", $this->shopPhone );

            $name    = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
            $cpf     = preg_replace( "/\D/", "", $order->get_meta( "_billing_cpf", true ) );
            $rCep    = preg_replace( "/\D/", "", $order->get_shipping_postcode() );
            $rPhone  = preg_replace( "/\D/", "", $order->get_billing_phone() );

            $address = explode( ",", $order->get_billing_address_1() );
            $rAddr   = trim( $address[ 0 ] );
            $rNumber = trim( $address[ 1 ] );

            $items   = [];

            foreach ( $order->get_items() as $item )
            {
                $product = $item->get_product();

                if ( !$product->meta_exists( "_custom_product_ncm" ) || empty( $product->get_meta( "_custom_product_ncm", true ) ) )
                    throw new Exception( "Há produtos neste pedido (#{$order_id}) que não tem NCM vinculado. Por favor, edite o produto para gerar a etiqueta." );

                $items[] = [
                    "hsCode"      => $product->get_meta( "_custom_product_ncm", true ),
                    "description" => substr( $product->get_name(), 0, 30 ),
                    "quantity"    => $item->get_quantity(),
                    "value"       => ( float ) $item->get_total(),
                ];
            }

            $data   = [
                "packageList" => [
                    [
                        "idusuario"                  => $this->userId,
                        "username"                   => $this->user,
                        "password"                   => $this->userPsw,

                        "customerControlCode"        => $this->tagPrefix . $order_id,

                        "senderName"                 => $this->shopName,   // "ShopToday INC",
                        "senderEmail"                => $this->shopEmail,  // "sales@shoptoday.com",
                        "senderPhone"                => $sPhone,           // "4072352525",
                        "senderAddress"              => $this->shopAddr,   // "Sequel Ave",
                        "senderAddressNumber"        => $this->shopNumber, // "4558",
                        "senderAddressComplement"    => $this->shopComplt, // "",
                        "senderState"                => $this->shopState,  // "Fl",
                        "senderCityName"             => $this->shopCity,   // "Orlando",
                        "senderZipCode"              => $sCep,             // "34746",

                        "recipientDocumentType"      => "CPF",
                        "recipientDocumentNumber"    => $cpf,
                        "recipientName"              => $name,
                        "recipientEmail"             => $order->get_billing_email(),
                        "recipientPhoneNumber"       => $rPhone,
                        "recipientAddressNumber"     => $rNumber,
                        "recipientAddress"           => $rAddr,
                        "recipientAddressComplement" => $order->get_billing_address_2(),
                        "recipientBairro"            => $order->get_meta( "_billing_neighborhood", true ),
                        "recipientState"             => $order->get_shipping_state(),
                        "recipientCityName"          => $order->get_shipping_city(),
                        "recipientZipCode"           => $rCep,

                        "totalWeight"                => ( float ) str_replace( ",", ".", $this->api->get_weight() ),

                        "packagingHeight"            => ( float ) str_replace( ",", ".", $this->api->get_height() ), // "6.0000000",
                        "packagingWidth"             => ( float ) str_replace( ",", ".", $this->api->get_width()  ), // "13.0000000",
                        "packagingLength"            => ( float ) str_replace( ",", ".", $this->api->get_length() ), // "18.0000000",

                        "freightPaidValue"           => $this->get_freight(),

                        "modal"   => "S",
                        "battery" => "N",
                        "liquid"  => "N",
                        "cream"   => "N",
                        "items"   => $items
                    ]
                ]
            ];

            return $this->exec( $data );
        }
        catch ( Exception $e )
        {
            $response = [
                "code"    => 500,
                "success" => false,
                "message" => $e->getMessage(),
                "error"   => 5001,
                "data"    => null,
            ];

            $this->log( $response, "critical" );

            return $response;
        }
    }

	/**
	 * Make request to the API
	 *
	 * @since 	1.0.0
	 * @param   array $params Body of request
	 * @return  array
	 */
    private function exec( array $params )
    {
        try
        {
            $curl     = curl_init();

            if ( !$curl )
            {
                $err = [
                    "code"    => "500.1",
                    "success" => false,
                    "message" => "Falha na comunicação com a API.",
                    "error"   => "Couldn't initialize a cURL handle",
                ];

                $this->log->add( $this->id, json_encode( $err ), "critical" );

                return $err;
            }

            $params   = ( !empty( $params ) ) ? json_encode( $params ) : null;

            curl_setopt_array( $curl, [
                CURLOPT_URL            => $this->url,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_SSL_VERIFYPEER => TRUE,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_POSTFIELDS     => $params,
                CURLOPT_HTTPHEADER     => $this->header,
            ] );

            $response = curl_exec( $curl );

            if ( empty( $response ) )
            {
                // some kind of an error happened
                $err      = curl_error( $curl );
                $response = [
                    "code"    => "500.2",
                    "success" => false,
                    "message" => "Falha na comunicação com a API.",
                    "error"   => $err,
                    "data"    => null,
                ];

                $this->log( $response, "critical" );
            }
            else
            {
                $info = curl_getinfo( $curl );

                if ( empty( $info[ "http_code" ] ) )
                {
                    $response = [
                        "code"    => "500.3",
                        "success" => false,
                        "message" => "Falha na comunicação com a API.",
                        "error"   => "No HTTP code was returned.",
                        "data"    => json_decode( $response, true ),
                    ];

                    $this->log( $response, "critical" );
                }
                else if ( $info[ "http_code" ] < 200 || $info[ "http_code" ] >= 300 )
                {
                    $response = [
                        "code"    => $info[ "http_code" ],
                        "success" => false,
                        "message" => "Falha na comunicação com a API.",
                        "error"   => self::HTTP_CODES[ $info[ "http_code" ] ],
                        "data"    => json_decode( $response, true ),
                    ];

                    $this->log( $response, "critical" );
                }
                else
                {
                    $response = [
                        "code"    => $info[ "http_code" ],
                        "success" => true,
                        "message" => null,
                        "error"   => null,
                        "data"    => json_decode( $response, true ),
                    ];
                }
            }

            curl_close( $curl );
        }
        catch ( Exception $e )
        {
            $response = [
                "code"    => 500,
                "success" => false,
                "message" => $e->getMessage(),
                "error"   => 5000,
                "data"    => null,
            ];

            $this->log( $response, "critical" );
        }

        return $response;
    }

    /**
     *
     *
	 * @since 	1.0.0
     * @param   object  $order
     * @return  void
     */
    private function get_shipping( $order )
    {
        $items   = $order->get_items();

        $sCep    = preg_replace( "/\D/", "", $this->shopCep                  );
        $rCep    = preg_replace( "/\D/", "", $order->get_shipping_postcode() );

        $package = [
            "contents"    => [],
            "destination" => [
                "postcode" => $rCep,
            ]
        ];

        foreach ( $items as $item )
        {
            $package[ "contents" ][] = [
                "data"     => $item->get_product(),
                "quantity" => $item->get_quantity(),
            ];
        }

        $this->api->set_service( "33227" );
        $this->api->set_package( $package );
        // $this->api->set_origin_postcode( $sCep );
        // $this->api->set_destination_postcode( $package[ "destination" ][ "postcode" ] );
        // $this->api->set_own_hands( "N" );
        // $this->api->set_receipt_notice( "N" );
        // $this->api->set_minimum_height();
        // $this->api->set_minimum_width();
        // $this->api->set_minimum_length();

        // return $api->get_shipping();
    }

    /**
     *
     *
	 * @since 	1.0.0
     * @return  void
     */
    private function get_freight()
    {
        $weight = str_replace( ",", ".", $this->api->get_weight() );
        $weight = 1000 * ( float ) $weight;

        if ( $weight >= 0 && $weight <= 200 )
        {
            // 0 a 200 = 19,30
            return 19.30;
        }
        else if ( $weight >= 201 && $weight <= 500 )
        {
            // 201 a 500 = 28,60
            return 28.60;
        }
        else if ( $weight >= 501 && $weight <= 1000 )
        {
            // 501 a 1000 = 32,10
            return 32.10;
        }
        else if ( $weight >= 1001 && $weight <= 1500 )
        {
            // 1001 a 1.500 = 35,60
            return 35.60;
        }
        else if ( $weight >= 1501 && $weight <= 2000 )
        {
            // 1.501 a 2.000 = 39,10
            return 39.10;
        }
        else if ( $weight >= 2001 && $weight <= 2500 )
        {
            // 2.001 a 2.500 = 42,60
            return 42.60;
        }
        else if ( $weight >= 2501 && $weight <= 3000 )
        {
            // 2.501 a 3.000 = 46,10
            return 46.10;
        }
        else if ( $weight >= 3001 && $weight <= 3500 )
        {
            // 3.001 a 3.500 = 49,60
            return 45.60;
        }
        else if ( $weight >= 3501 && $weight <= 4000 )
        {
            // 3.501 a 4.000 = 53,10
            return 53.10;
        }
        else if ( $weight >= 4001 && $weight <= 4500 )
        {
            // 4.001 a 4.500 = 56,60
            return 56.60;
        }
        else if ( $weight >= 4501 && $weight <= 5000 )
        {
            // 4.501 a 5.000 = 60,10
            return 60.10;
        }
        else
        {
            // 1/2 adicional = 3,50

            $value = ( $weight - 5000 ) / 500;
            $value = ceil( $value ) * 3.5;

            return 60.10 + $value;
        }
    }

    /**
     * Register log
     *
	 * @since 	1.0.0
     * @param   array   $data
     * @param   string  $type
     * @return  void
     */
    public function log( array $data, string $type )
    {
        $this->log->add( $this->id, json_encode( $data ), $type );
    }
}
