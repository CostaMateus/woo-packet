<?php
/**
 * Plugin Correios Package.
 *
 * @package Woo_Packet/Classes
 * @since   1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Woo_Packet_Package class.
 */
class Woo_Packet_Package
{
	/**
	 * Order package.
	 *
	 * @since 	1.0.0
	 * @var 	array
	 */
	protected $package = [];

	/**
	 * Sets the package.
	 *
	 * @since 	1.0.0
	 * @param  	array $package Package to calcule.
	 * @return 	array
	 */
	public function __construct( $package = [] )
	{
		$this->package = $package;
	}

	/**
	 * Extracts the weight and dimensions from the package.
	 *
	 * @since 	1.0.0
	 * @return 	array
	 */
	protected function get_package_data()
	{
		$count  = 0;
		$height = [];
		$width  = [];
		$length = [];
		$weight = [];

		// Shipping per item.
		foreach ( $this->package[ "contents" ] as $item_id => $values )
		{
			$product = $values[ "data"     ];
			$qty     = $values[ "quantity" ];

			if ( $qty > 0 && $product->needs_shipping() )
			{
				$_height = wc_get_dimension( (float) $product->get_height(), "cm" );
				$_width  = wc_get_dimension( (float) $product->get_width(),  "cm" );
				$_length = wc_get_dimension( (float) $product->get_length(), "cm" );

				$_weight = wc_get_weight( (float) $product->get_weight(), "kg" );

				$height[ $count ] = $_height;
				$width [ $count ] = $_width;
				$length[ $count ] = $_length;
				$weight[ $count ] = $_weight;

				if ( $qty > 1 )
				{
					$n = $count;

					for ( $i = 0; $i < $qty; $i++ )
					{
						$height[ $n ] = $_height;
						$width [ $n ] = $_width;
						$length[ $n ] = $_length;
						$weight[ $n ] = $_weight;
						$n++;
					}

					$count = $n;
				}

				$count++;
			}
		}

		return [
			"height" => array_values( $height ),
			"width"  => array_values( $width  ),
			"length" => array_values( $length ),
			"weight" => array_sum( $weight ),
		];
	}

	/**
	 * Calculates the cubage of all products.
	 *
	 * @since 	1.0.0
	 * @param  	array 	$height 	Package	height.
	 * @param  	array 	$width  	Package	width.
	 * @param  	array 	$length 	Package	length.
	 * @return 	int
	 */
	protected function cubage_total( $height, $width, $length )
	{
		// Sets the cubage of all products.
		$total       = 0;
		$total_items = count( $height );

		for ( $i = 0; $i < $total_items; $i++ )
		{
			$total += $height[ $i ] * $width[ $i ] * $length[ $i ];
		}

		return $total;
	}

	/**
	 * Get the max values.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$height 	Package height.
	 * @param 	array 	$width  	Package width.
	 * @param 	array 	$length 	Package length.
	 * @return 	array
	 */
	protected function get_max_values( $height, $width, $length )
	{
		return [
			"height" => max( $height ),
			"width"  => max( $width  ),
			"length" => max( $length ),
		];
	}

	/**
	 * Calculates the square root of the scaling of all products.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$height 	Package height.
	 * @param 	array 	$width 		Package width.
	 * @param 	array 	$length 	Package length.
	 * @param 	array 	$max_values bigger values.
	 * @return 	float
	 */
	protected function calculate_root( $height, $width, $length, $max_values )
	{
		$cubage_total = $this->cubage_total( $height, $width, $length );
		$root         = 0;
		$biggest      = max( $max_values );

		if ( 0 !== $cubage_total && 0 < $biggest )
		{
			// Dividing the value of scaling of all products.
			// With the measured value of greater.
			$division = $cubage_total / $biggest;

			// Total square root.
			$root     = round( sqrt( $division ), 1 );
		}

		return $root;
	}

	/**
	 * Sets the final cubage.
	 *
	 * @since 	1.0.0
	 * @param  	array 	$height	Package height.
	 * @param  	array 	$width 	Package width.
	 * @param  	array 	$length	Package length.
	 * @return 	array
	 */
	protected function get_cubage( $height, $width, $length )
	{
		$max_values = $this->get_max_values( $height, $width, $length );
		$root       = $this->calculate_root( $height, $width, $length, $max_values );
		$greatest   = array_search( max( $max_values ), $max_values, true );

		switch ( $greatest )
		{
			case "height" :
				return [
					"height" => max( $height ),
					"width"  => $root,
					"length" => $root,
				];
			break;

			case "width" :
				return [
					"height" => $root,
					"width"  => max( $width ),
					"length" => $root,
				];
			break;

			case "length" :
				return [
					"height" => $root,
					"width"  => $root,
					"length" => max( $length ),
				];
			break;

			default :
				return [
					"height" => 0,
					"width"  => 0,
					"length" => 0,
				];
			break;
		}
	}

	/**
	 * Get the package data.
	 *
	 * @since 	1.0.0
	 * @return 	array
	 */
	public function get_data()
	{
		// Get the package data.
		$data = $this->get_package_data();

		if ( ! empty( $data[ "height" ] ) && ! empty( $data[ "width" ] ) && ! empty( $data[ "length" ] ) )
		{
			$cubage = $this->get_cubage( $data[ "height" ], $data[ "width" ], $data[ "length" ] );
		}
		else
		{
			$cubage = [
				"height" => 0,
				"width"  => 0,
				"length" => 0,
			];
		}

		return [
			"height" => $cubage[ "height" ],
			"width"  => $cubage[ "width"  ],
			"length" => $cubage[ "length" ],
			"weight" => $data[ "weight" ],
		];
	}
}
