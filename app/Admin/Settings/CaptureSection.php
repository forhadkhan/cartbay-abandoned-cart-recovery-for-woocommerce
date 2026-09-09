<?php
/**
 * Capture settings section.
 *
 * @package WPAnchorBay\CartBay\Admin\Settings
 */

namespace WPAnchorBay\CartBay\Admin\Settings;

use WPAnchorBay\CartBay\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Capture settings section.
 *
 * @since 1.0.0
 */
class CaptureSection extends AbstractSettingsSection {
	/**
	 * Get the section identifier used in the URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Section identifier.
	 */
	public function id(): string {
		return 'capture';
	}

	/**
	 * Get the navigation label for the section.
	 *
	 * @since 1.0.0
	 *
	 * @return string Section label.
	 */
	public function label(): string {
		return __( 'Capture', 'cartbay-abandoned-cart-recovery-for-woocommerce' );
	}

	/**
	 * Get WooCommerce settings API fields for the section.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>> Section fields.
	 */
	public function fields(): array {
		return array(
			array(
				'title' => __( 'Capture Settings', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure how CartBay captures email and cart data at checkout.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'id'    => 'cartbay_capture_settings',
			),
			array(
				'title'    => __( 'Enable Capture', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'desc'     => __( 'Capture email and cart data at checkout', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'desc_tip' => __( 'Turns guest cart capture on for both classic checkout and WooCommerce Blocks checkout.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'tooltip'  => __( 'When enabled, CartBay loads the checkout capture scripts and accepts consented capture requests. Turn this off to pause new cart and email capture without deleting existing sessions.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'id'       => 'cartbay_settings[capture_enabled]',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Consent Text', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'desc'     => __( 'Text shown next to the consent checkbox on checkout.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'desc_tip' => __( 'Keep this short and explicit so shoppers understand you may email them about an unfinished checkout.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'id'       => 'cartbay_settings[consent_text]',
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:400px;height:60px;',
			),
			array(
				'title'    => __( 'Consent Checkbox Default State', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'desc'     => __( 'Choose whether the checkout consent checkbox starts checked or unchecked.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'desc_tip' => __( 'Defaults to unchecked so consent is an explicit opt-in (recommended for GDPR and similar laws). Shoppers can change it at checkout; CartBay only captures while it is checked.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'id'       => 'cartbay_settings[consent_default_state]',
				'default'  => 'unchecked',
				'type'     => 'select',
				'options'  => array(
					'unchecked' => __( 'Unchecked (recommended)', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
					'checked'   => __( 'Checked', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				),
			),
			array(
				'title'             => __( 'Abandonment Timeout (minutes)', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'desc'              => sprintf(
					/* translators: 1: minimum minutes, 2: maximum minutes. */
					__( 'Minutes of inactivity before a cart is marked as abandoned. Accepts %1$d to %2$d minutes.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
					Settings::ABANDONMENT_TIMEOUT_MIN,
					Settings::ABANDONMENT_TIMEOUT_MAX
				),
				'desc_tip'          => __( 'CartBay waits this long after the shopper stops interacting before the recovery sequence becomes eligible.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'id'                => 'cartbay_settings[abandonment_timeout]',
				'default'           => Settings::ABANDONMENT_TIMEOUT_DEFAULT,
				'type'              => 'number',
				'css'               => 'width:80px;',
				'custom_attributes' => array(
					'min' => Settings::ABANDONMENT_TIMEOUT_MIN,
					'max' => Settings::ABANDONMENT_TIMEOUT_MAX,
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'cartbay_capture_settings',
			),
		);
	}

	/**
	 * Normalize capture settings after WooCommerce has written them.
	 *
	 * WooCommerce's woocommerce_update_options() stores whatever was posted. The HTML min/max
	 * on the timeout field is a browser convenience, not a control, so the range
	 * is enforced here as well; the consent default is normalized for the same
	 * reason, since only the exact literal 'checked' may pre-tick a consent box.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function save(): void {
		$settings = get_option( 'cartbay_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		$stored_timeout  = $settings['abandonment_timeout'] ?? Settings::ABANDONMENT_TIMEOUT_DEFAULT;
		$clamped_timeout = Settings::clamp_abandonment_timeout( $stored_timeout );

		if ( (string) $stored_timeout !== (string) $clamped_timeout ) {
			$settings['abandonment_timeout'] = $clamped_timeout;

			if ( class_exists( 'WC_Admin_Settings' ) ) {
				\WC_Admin_Settings::add_error(
					sprintf(
						/* translators: 1: minimum minutes, 2: maximum minutes, 3: value that was saved instead. */
						__( 'Abandonment Timeout must be between %1$d and %2$d minutes. It has been saved as %3$d.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
						Settings::ABANDONMENT_TIMEOUT_MIN,
						Settings::ABANDONMENT_TIMEOUT_MAX,
						$clamped_timeout
					)
				);
			}
		} else {
			$settings['abandonment_timeout'] = $clamped_timeout;
		}

		$settings['consent_default_state'] = Settings::normalize_consent_default_state( $settings['consent_default_state'] ?? 'unchecked' );

		update_option( 'cartbay_settings', $settings );
	}
}
