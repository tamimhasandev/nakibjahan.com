<?php
/**
 * WooCommerce Price Based Country Selector Widget.
 *
 * @version 1.8.10
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * WCPBC_Widget_Country_Selector Class
 */
class WCPBC_Widget_Country_Selector extends WC_Widget {

	/**
	 * Other countries text.
	 *
	 * @var string
	 */
	private static $_other_countries_text = ''; // phpcs:ignore

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->widget_description = __( 'A country switcher for your store.', 'woocommerce-product-price-based-on-countries' );
		$this->widget_id          = 'wcpbc_country_selector';
		$this->widget_name        = __( 'WooCommerce Country Switcher', 'woocommerce-product-price-based-on-countries' );
		$this->settings           = array(
			'title'                => array(
				'type'  => 'text',
				'std'   => __( 'Country', 'woocommerce-product-price-based-on-countries' ),
				'label' => __( 'Title', 'woocommerce-product-price-based-on-countries' ),
			),

			'flag'                 => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Display flags in supported devices', 'woocommerce-product-price-based-on-countries' ),
			),

			'other_countries_text' => array(
				'type'  => 'text',
				'std'   => __( 'Other countries', 'woocommerce-product-price-based-on-countries' ),
				'label' => __( 'Other countries text', 'woocommerce-product-price-based-on-countries' ),
			),
		);

		if ( wcpbc_is_pro() ) {
			// Allow users to remove other countries.
			$this->settings['remove_other_countries'] = array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Remove "Other countries" from switcher.', 'woocommerce-product-price-based-on-countries' ),
			);
		} else {
			// "Upgrade to Pro"
			$this->settings['remove_other_countries_pro'] = array(
				'type'  => 'wcpbc_remove_other_countries_pro',
				'std'   => '',
				'label' => __( 'Remove "Other countries" from switcher.', 'woocommerce-product-price-based-on-countries' ),
			);
		}

		if ( ! has_action( 'woocommerce_widget_field_wcpbc_remove_other_countries_pro', array( __CLASS__, 'remove_other_countries_field' ) ) ) {
			add_action( 'woocommerce_widget_field_wcpbc_remove_other_countries_pro', array( __CLASS__, 'remove_other_countries_field' ), 10, 4 );
		}

		parent::__construct();
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param string $key Field key.
	 * @param string $value Field value.
	 * @param array  $setting An array of settings.
	 * @param array  $instance Widget instance.
	 */
	public static function remove_other_countries_field( $key, $value, $setting, $instance ) {
		?>
		<p>
			<input class="checkbox" disabled="disabled" name="remove_other_countries_pro" type="checkbox" />
			<label style="opacity: 0.7;" for="remove_other_countries_pro"><?php echo esc_html( $setting['label'] ); ?></label>
			<span style="display: block; font-size: 12px; font-style: italic; margin-left: 22px;">
				<?php
					// Translators: HTML tags.
					printf( esc_html__( '%1$sUpgrade to Pro to remove Other countries%2$s', 'woocommerce-product-price-based-on-countries' ), '<a target="_blank" rel="noopener noreferrer" class="cta-button" href="' . esc_url( wcpbc_home_url( 'widget' ) ) . '">', '</a>' );
				?>
			</span>
		</p>
		<?php
	}

	/**
	 * Widget function.
	 *
	 * @see WP_Widget
	 * @version 1.9 Check the countries of the widget are in the allowed countries.
	 * @param array $args Array of arguments.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		require_once dirname( __FILE__ ) . '/class-wcpbc-country-flags.php';

		$allowed_countries = apply_filters( 'wc_price_based_country_allow_all_countries', false ) ? WC()->countries->get_countries() : WC()->countries->get_allowed_countries();
		$all_countries     = WC()->countries->get_countries();
		$base_country      = wc_get_base_location();
		$base_country      = isset( $base_country['country'] ) ? $base_country['country'] : '';
		$countries         = array();

		if ( array_key_exists( $base_country, $allowed_countries ) && array_key_exists( $base_country, $all_countries ) ) {
			$countries[ $base_country ] = $all_countries[ $base_country ];
		}

		foreach ( WCPBC_Pricing_Zones::get_zones() as $zone ) {
			if ( ! $zone->get_enabled() ) {
				continue;
			}
			foreach ( $zone->get_countries() as $country ) {
				if ( ! array_key_exists( $country, $countries ) && isset( $all_countries[ $country ] ) ) {
					$countries[ $country ] = $all_countries[ $country ];
				}
			}
		}

		wcpbc_maybe_asort_locale( $countries );

		// Add other countries.
		$other_country               = key( array_diff_key( $all_countries, $countries ) );
		$countries[ $other_country ] = empty( $instance['other_countries_text'] ) ? apply_filters( 'wcpbc_other_countries_text', __( 'Other countries', 'woocommerce-product-price-based-on-countries' ) ) : $instance['other_countries_text'];
		$remove_other_countries      = wcpbc_is_pro() && ! empty( $instance['remove_other_countries'] ) && 'false' !== $instance['remove_other_countries'];

		if ( $remove_other_countries ) {
			unset( $countries[ $other_country ] );
			$other_country = $base_country;
		}

		/**
		 * Allow developers filter the list of countries
		 */
		do_action_ref_array( 'wc_price_based_country_widget_before_selected', array( &$other_country, &$countries, $base_country, $instance ) );

		// Set selected country and display.
		$selected_country = wcpbc_get_woocommerce_country();

		if ( is_string( $selected_country ) && ! array_key_exists( $selected_country, $countries ) ) {
			$selected_country = $other_country;
		}

		$widget_data = wp_json_encode(
			array(
				'instance' => $instance,
				'id'       => $this->widget_id,
			)
		);

		$this->widget_start( $args, $instance );

		echo '<div class="wc-price-based-country wc-price-based-country-refresh-area" data-area="widget" data-id="' . esc_attr( md5( $widget_data ) ) . '" data-options="' . esc_attr( $widget_data ) . '">';
		wc_get_template(
			'country-selector.php',
			array(
				'show_flags'             => empty( $instance['flag'] ) ? 0 : 1,
				'other_country_id'       => $other_country,
				'remove_other_countries' => $remove_other_countries,
				'countries'              => $countries,
				'selected_country'       => $selected_country,
				'label'                  => empty( $instance['title'] ) ? __( 'Country', 'woocommerce-product-price-based-on-countries' ) : $instance['title'],
			),
			'woocommerce-product-price-based-on-countries/',
			wcpbc()->plugin_path() . '/templates/'
		);
		echo '</div>';

		$this->widget_end( $args );
	}

	/**
	 * Output a form to handle the country/currency switcher.
	 */
	public static function country_switcher_form() {
		static $form_added = false;
		if ( $form_added ) {
			// Do only once.
			return;
		}
		$form_added = true;
		?>
		<form method="post" id="wcpbc-widget-country-switcher-form" class="wcpbc-widget-country-switcher" style="display:none;">
			<input type="hidden" id="wcpbc-widget-country-switcher-input" name="wcpbc-manual-country" />
		</form>
		<?php
	}
}
?>
