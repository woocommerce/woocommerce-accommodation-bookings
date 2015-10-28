<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for the accommodation booking product type
 */
class WC_Product_Accommodation_Booking extends WC_Product_Booking {

	/**
	 * Constructor
	 */
	public function __construct( $product ) {
		$this->product_type = 'accommodation-booking';
		parent::__construct( $product );
	}

}