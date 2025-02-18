<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Product_Accommodation_Booking' ) && class_exists( 'WC_Product_Booking' ) ) :

	/**
	 * Class that creates our new accommodation booking product type
	 * Mostly inheirted from WC_Product_Booking (code reuse!) but overrides a few methods
	 */
	class WC_Product_Accommodation_Booking extends WC_Product_Booking {

		/**
		 * The type of product we're creating
		 *
		 * @var string
		 */
		public $product_type = 'accommodation-booking';

		/**
		 * The type of duration we're using
		 *
		 * @var string
		 */
		public $wc_booking_duration_type = 'customer';

		/**
		 * The unit of duration we're using
		 *
		 * @var string
		 */
		public $wc_booking_duration_unit = 'night';

		/**
		 * The duration of the booking
		 *
		 * @var integer
		 */
		public $wc_booking_duration = 1;

		/**
		 * Set up our new type and fill out some basic info
		 *
		 * WC_Product_Accommodation_Booking constructor.
		 *
		 * @param $product
		 */
		public function __construct( $product ) {
			$this->product_type = $this->get_type();
			parent::__construct( $product );

			$this->wc_booking_duration_type = 'customer';
			$this->wc_booking_duration_unit = 'night';
			$this->wc_booking_duration      = 1;
		}


		/**
		 * Get resource by ID.
		 * Need to override this to return the proper resource class.
		 *
		 * @param  int $id
		 *
		 * @return WC_Product_Booking_Resource object
		 */
		public function get_resource( $id ) {
			$resource = parent::get_resource( $id );

			if ( $resource ) {
				$resource = new WC_Product_Accommodation_Booking_Resource( $id, $this->get_id() );
			}

			return $resource;
		}

		/**
		 * Override product type
		 *
		 * @return string
		 */
		public function get_type() {
			return 'accommodation-booking';
		}

		/**
		 * Get resources objects.
		 *
		 * @param WC_Product
		 *
		 * @return array(
		 *   type WC_Product_Accommodation_Booking_Resource
		 * )
		 */
		public function get_resources() {
			$product_resources = array();

			foreach ( $this->get_resource_ids() as $resource_id ) {
				$product_resources[] = new WC_Product_Accommodation_Booking_Resource( $resource_id, $this->get_id() );
			}

			return $product_resources;
		}

		/**
		 * Tells Bookings that this product type is a bookings addon.
		 *
		 * @return boolean
		 */
		public function is_bookings_addon() {
			return true;
		}

		/**
		 * Human readable version of the addon title
		 *
		 * @return string
		 */
		public function bookings_addon_title() {
			return __( 'Accommodation booking', 'woocommerce-accommodation-bookings' );
		}

		/**
		 * We want users to be able to select their range of dates
		 *
		 * @return boolean
		 */
		public function is_range_picker_enabled() {
			return apply_filters( 'woocommerce_accommodation_bookings_range_picker_enabled', true );
		}

		/**
		 * Customers define how many nights they want to stay. There is no concept
		 * of "fixed" durations for accommodations.
		 *
		 * @param  string $context
		 *
		 * @return string
		 */
		public function get_duration_type( $context = 'view' ) {
			return 'customer';
		}

		/**
		 * Our duration is nights instead of days
		 *
		 * @param  string $context
		 *
		 * @return string
		 */
		public function get_duration_unit( $context = 'view' ) {
			return 'night';
		}

		/**
		 * Costs can vary depending on rates (weekend rates, etc)
		 * In the future, addons like cots can also change cost.
		 *
		 * @return boolean
		 */
		public function has_additional_costs() {
			return true;
		}

		/**
		 * By default, rooms will be available.
		 *
		 * @return boolean
		 */
		public function get_default_availability() {
			return true;
		}

		/**
		 * Hotel rooms are a "virtual" product. No shipping is involved.
		 *
		 * @return boolean
		 */
		public function is_virtual() {
			return $this->get_prop( 'virtual' );
		}

		/**
		 * Get price HTML
		 *
		 * @param string $price
		 *
		 * @return string
		 */
		public function get_price_html( $price = '' ) {

			// If display cost is set - user wants that to be displayed
			$display_price = $this->get_display_cost();
			if ( ! $display_price ) {
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$display_price    = $tax_display_mode == 'incl' ? wc_get_price_including_tax(
					$this,
					array(
						'qty'   => 1,
						'price' => $this->get_price(),
					)
				) : wc_get_price_excluding_tax(
					$this,
					array(
						'qty'   => 1,
						'price' => $this->get_price(),
					)
				);
			}

			if ( $display_price ) {
				if ( $this->has_additional_costs() || $this->get_display_cost() ) {
					/* translators: %s: price */
					$price_html = sprintf( __( 'From %s per night', 'woocommerce-accommodation-bookings' ), wc_price( $display_price ) ) . $this->get_price_suffix();
				} else {
					$price_html = wc_price( $display_price ) . $this->get_price_suffix();
				}
			} elseif ( ! $this->has_additional_costs() ) {
				$price_html = __( 'Free', 'woocommerce-accommodation-bookings' );
			} else {
				$price_html = '';
			}
			return apply_filters( 'woocommerce_get_price_html', $price_html, $this );
		}

		/**
		 * Find available and booked blocks for specific resources (if any) and return them as array.
		 *
		 * @param  array   $blocks
		 * @param  array   $intervals
		 * @param  integer $resource_id
		 * @param  integer $from The starting date for the set of blocks
		 * @param  integer $to
		 * @return array
		 */
		public function get_time_slots( $blocks, $resource_id = 0, $from = 0, $to = 0, $include_sold_out = false ) {
			$bookable_product = $this;

			$product_id                   = $bookable_product->get_id();
			$transient_name               = 'book_ts_' . md5( http_build_query( array( $product_id, $resource_id, $from, $to ) ) );
			$available_slots              = get_transient( $transient_name );
			$booking_slots_transient_keys = array_filter( (array) get_transient( 'booking_slots_transient_keys' ) );

			if ( ! isset( $booking_slots_transient_keys[ $product_id ] ) ) {
				$booking_slots_transient_keys[ $product_id ] = array();
			}

			$booking_slots_transient_keys[ $product_id ][] = $transient_name;

			// Give array of keys a long ttl because if it expires we won't be able to flush the keys when needed.
			// We can't use 0 to never expire because then WordPress will autoload the option on every page.
			set_transient( 'booking_slots_transient_keys', $booking_slots_transient_keys, YEAR_IN_SECONDS );

			if ( false === $available_slots ) {
				if ( empty( $intervals ) ) {
					$interval  = $bookable_product->get_min_duration();
					$intervals = array( $interval, 1 );
				}

				list( $interval, $base_interval ) = $intervals;

				if ( version_compare( WC_BOOKINGS_VERSION, '1.15.0', '<' ) ) {
					$existing_bookings = WC_Bookings_Controller::get_all_existing_bookings( $bookable_product, $from, $to );
				} else {
					$existing_bookings = WC_Booking_Data_Store::get_all_existing_bookings( $bookable_product, $from, $to );
				}

				$booking_resource = $resource_id ? $bookable_product->get_resource( $resource_id ) : null;
				$available_slots  = array();
				$has_qty          = ! is_null( $booking_resource ) ? $booking_resource->has_qty() : false;
				$has_resources    = $bookable_product->has_resources();

				foreach ( $blocks as $block ) {
					$check_in  = self::get_check_times( 'in', $product_id );
					$check_out = self::get_check_times( 'out', $product_id );
					// Blocks for accommodation products are initially calculated as days but the actuall time blocks are shifted by check in and checkout times.
					$block_start_time = strtotime( "{$check_in}", $block );
					$block_end_time   = strtotime( "{$check_out}", strtotime( '+1 days', $block ) );
					$resources        = array();

					// Figure out how much qty have, either based on combined resource quantity,
					// single resource, or just product.
					if ( $has_resources && ( ! is_a( $booking_resource, 'WC_Product_Booking_Resource' ) || ! $has_qty ) ) {
						$available_qty = 0;

						foreach ( $bookable_product->get_resources() as $resource ) {

							if ( ! $bookable_product->check_availability_rules_against_time( $block_start_time, $block_end_time, $block, $resource->get_id() ) ) {
								continue;
							}

							$qty                              = $resource->get_qty();
							$available_qty                   += $qty;
							$resources[ $resource->get_id() ] = $qty;
						}
					} elseif ( $has_resources && $has_qty ) {
						// Only include if it is available for this selection. We set this block to be bookable by default, unless some of the rules apply.
						if ( ! $bookable_product->check_availability_rules_against_time( $block_start_time, $block_end_time, $booking_resource->get_id() ) ) {
							continue;
						}

						$qty                                      = $booking_resource->get_qty();
						$available_qty                            = $qty;
						$resources[ $booking_resource->get_id() ] = $qty;
					} else {
						$available_qty = $bookable_product->get_qty();
						$resources[0]  = $bookable_product->get_qty();
					}

					$qty_booked_in_block = 0;

					foreach ( $existing_bookings as $existing_booking ) {
						if ( $existing_booking->is_intersecting_block( $block_start_time, $block_end_time ) ) {
							$qty_to_add = $bookable_product->has_person_qty_multiplier() ? max( 1, array_sum( $existing_booking->get_persons() ) ) : 1;
							if ( $has_resources ) {
								if ( $existing_booking->get_resource_id() === absint( $resource_id ) ) {
									// Include the quantity to subtract if an existing booking matches the selected resource id
									$qty_booked_in_block      += $qty_to_add;
									$resources[ $resource_id ] = ( isset( $resources[ $resource_id ] ) ? $resources[ $resource_id ] : 0 ) - $qty_to_add;
								} elseif ( ( is_null( $booking_resource ) || ! $has_qty ) && $existing_booking->get_resource() ) {
									// Include the quantity to subtract if the resource is auto selected (null/resource id empty)
									// but the existing booking includes a resource
									$qty_booked_in_block                              += $qty_to_add;
									$resources[ $existing_booking->get_resource_id() ] = ( isset( $resources[ $existing_booking->get_resource_id() ] ) ? $resources[ $existing_booking->get_resource_id() ] : 0 ) - $qty_to_add;
								}
							} else {
								$qty_booked_in_block += $qty_to_add;
								$resources[0]         = ( isset( $resources[0] ) ? $resources[0] : 0 ) - $qty_to_add;
							}
						}
					}

					$available_slots[ $block ] = array(
						'booked'    => $qty_booked_in_block,
						'available' => $available_qty - $qty_booked_in_block,
						'resources' => $resources,
					);
				}

				set_transient( $transient_name, $available_slots, YEAR_IN_SECONDS );
			}

			return $available_slots;
		}


		/**
		 * Get an array of blocks within in a specified date range
		 *
		 * The WC_Product_Booking class does not account for 'nights' as a valid duration unit so it retrieves every minute of each day as a block,
		 * severly slowing down the load time of the page.
		 *
		 * @param       $start_date
		 * @param       $end_date
		 * @param array      $intervals
		 * @param int        $resource_id
		 * @param array      $booked
		 * @param bool       $get_past_times
		 *
		 * @return array
		 */
		public function get_blocks_in_range( $start_date, $end_date, $intervals = array(), $resource_id = 0, $booked = array(), $get_past_times = false ) {

			$blocks_in_range = $this->get_blocks_in_range_for_day( $start_date, $end_date, $resource_id, $booked );

			return array_unique( $blocks_in_range );
		}

		/**
		 * Get checkin and checkout times.
		 *
		 * @param string $type       The type, check_in or check_out.
		 * @param int    $product_id The product ID.
		 *
		 * @return string The time, either from options or default or from the filtered value.
		 */
		public static function get_check_times( $type, $product_id = 0 ) {
			$option     = get_option( 'woocommerce_accommodation_bookings_times_settings' );
			$check_time = '';

			switch ( $type ) {
				case 'in':
					$check_time = $option['check_in'] ?? '14:00';
					break;
				case 'out':
					$check_time = $option['check_out'] ?? '14:00';
					break;
			}

			/**
			 * Filter the check-in/out times for a specific product.
			 *
			 * @param string $check_time The check-in/out time stored in the database.
			 * @param string $type       The type, check_in or check_out.
			 * @param int    $product_id The product ID.
			 *
			 * @return string The filtered/original time.
			 */
			return apply_filters( 'woocommerce_accommodation_booking_get_check_times', $check_time, $type, (int) $product_id );
		}

		/**
		 * Get duration.
		 *
		 * Duration unit is always one night.
		 *
		 * @param  string $context
		 * @return integer
		 */
		public function get_duration( $context = 'view' ) {
			return 1;
		}
	}

endif;
