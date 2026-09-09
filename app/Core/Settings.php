<?php
/**
 * Plugin settings helpers.
 *
 * @package WPAnchorBay\CartBay\Core
 */

namespace WPAnchorBay\CartBay\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and normalizes CartBay settings.
 *
 * @since 1.0.0
 */
class Settings {
	/**
	 * Lowest accepted abandonment timeout, in minutes.
	 *
	 * @since 1.1.1
	 *
	 * @var int
	 */
	public const ABANDONMENT_TIMEOUT_MIN = 5;

	/**
	 * Highest accepted abandonment timeout, in minutes.
	 *
	 * @since 1.1.1
	 *
	 * @var int
	 */
	public const ABANDONMENT_TIMEOUT_MAX = 1440;

	/**
	 * Abandonment timeout used when none is stored, in minutes.
	 *
	 * @since 1.1.1
	 *
	 * @var int
	 */
	public const ABANDONMENT_TIMEOUT_DEFAULT = 30;

	/**
	 * Clamp an abandonment timeout to the supported range.
	 *
	 * The range was previously enforced only by an HTML min/max attribute, so
	 * any path that did not go through the browser — the setup wizard, WP-CLI, a
	 * direct update_option() — could store a value the scheduler could not
	 * safely use. A stored 0 made every cart abandoned immediately, mailing
	 * shoppers who were still checking out. This is the single definition of the
	 * range; every reader and writer goes through it.
	 *
	 * @since 1.1.1
	 *
	 * @param mixed $value Raw timeout value.
	 *
	 * @return int Timeout in minutes, guaranteed within range.
	 */
	public static function clamp_abandonment_timeout( mixed $value ): int {
		if ( ! is_numeric( $value ) ) {
			return self::ABANDONMENT_TIMEOUT_DEFAULT;
		}

		return (int) min( self::ABANDONMENT_TIMEOUT_MAX, max( self::ABANDONMENT_TIMEOUT_MIN, absint( $value ) ) );
	}

	/**
	 * Get the configured abandonment timeout in minutes.
	 *
	 * @since 1.1.1
	 *
	 * @return int Timeout in minutes, guaranteed within range.
	 */
	public static function get_abandonment_timeout(): int {
		$settings = get_option( 'cartbay_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		return self::clamp_abandonment_timeout( $settings['abandonment_timeout'] ?? self::ABANDONMENT_TIMEOUT_DEFAULT );
	}

	/**
	 * Normalize the checkout consent checkbox default state.
	 *
	 * Returns 'checked' only for the exact stored literal. Anything else — an
	 * empty string, a stale value, a third-party write — normalizes to
	 * 'unchecked', because a pre-ticked consent box is not valid consent under
	 * the GDPR and the failure direction has to be toward asking.
	 *
	 * @since 1.1.1
	 *
	 * @param mixed $value Raw stored value.
	 *
	 * @return string Either 'checked' or 'unchecked'.
	 */
	public static function normalize_consent_default_state( mixed $value ): string {
		return 'checked' === $value ? 'checked' : 'unchecked';
	}

	/**
	 * Determine whether checkout capture is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether new checkout capture should run.
	 */
	public static function is_capture_enabled(): bool {
		$settings = get_option( 'cartbay_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		if ( ! array_key_exists( 'capture_enabled', $settings ) ) {
			return true;
		}

		return self::normalize_boolean( $settings['capture_enabled'] );
	}

	/**
	 * Determine whether the WooCommerce admin menu shortcut is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the WooCommerce menu shortcut should be shown.
	 */
	public static function is_wc_menu_enabled(): bool {
		$settings = get_option( 'cartbay_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		if ( ! array_key_exists( 'wc_menu_enabled', $settings ) ) {
			return true;
		}

		return self::normalize_boolean( $settings['wc_menu_enabled'] );
	}

	/**
	 * Determine whether test mode is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether test mode should run.
	 */
	public static function is_test_mode_enabled(): bool {
		$settings = get_option( 'cartbay_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		if ( ! array_key_exists( 'test_mode', $settings ) ) {
			return false;
		}

		return self::normalize_boolean( $settings['test_mode'] );
	}

	/**
	 * Normalize stored checkbox-like values to a boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Stored setting value.
	 *
	 * @return bool Normalized boolean value.
	 */
	private static function normalize_boolean( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return 1 === absint( $value );
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );

			return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
		}

		return false;
	}
}
