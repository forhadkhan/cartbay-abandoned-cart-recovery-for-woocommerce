<?php
/**
 * Tests for settings normalization helpers.
 *
 * @package WPAnchorBay\CartBay\Tests\Core
 */

namespace WPAnchorBay\CartBay\Tests\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPAnchorBay\CartBay\Core\Settings;

/**
 * Settings normalization tests.
 *
 * @since 1.1.1
 */
class SettingsTest extends TestCase {

	/**
	 * Prepare WordPress function doubles.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'absint' )->alias(
			static fn ( mixed $value ): int => abs( (int) $value )
		);
	}

	/**
	 * Clean up WordPress function doubles.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * A zero timeout must never reach the scheduler: it would mark every cart
	 * abandoned immediately and mail shoppers who are still checking out.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_zero_timeout_is_raised_to_the_minimum(): void {
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_MIN, Settings::clamp_abandonment_timeout( 0 ) );
	}

	/**
	 * Values below and above the range are pulled back into it.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_out_of_range_timeouts_are_clamped(): void {
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_MIN, Settings::clamp_abandonment_timeout( 4 ) );
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_MAX, Settings::clamp_abandonment_timeout( 99999 ) );
	}

	/**
	 * In-range values, including numeric strings from $_POST, are preserved.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_in_range_timeouts_are_preserved(): void {
		$this->assertSame( 30, Settings::clamp_abandonment_timeout( 30 ) );
		$this->assertSame( 45, Settings::clamp_abandonment_timeout( '45' ) );
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_MIN, Settings::clamp_abandonment_timeout( Settings::ABANDONMENT_TIMEOUT_MIN ) );
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_MAX, Settings::clamp_abandonment_timeout( Settings::ABANDONMENT_TIMEOUT_MAX ) );
	}

	/**
	 * Non-numeric input falls back to the default rather than to zero.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_non_numeric_timeout_falls_back_to_the_default(): void {
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_DEFAULT, Settings::clamp_abandonment_timeout( '' ) );
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_DEFAULT, Settings::clamp_abandonment_timeout( 'soon' ) );
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_DEFAULT, Settings::clamp_abandonment_timeout( null ) );
		$this->assertSame( Settings::ABANDONMENT_TIMEOUT_DEFAULT, Settings::clamp_abandonment_timeout( array() ) );
	}

	/**
	 * Only the exact literal 'checked' may pre-tick a consent box.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_only_the_exact_checked_literal_opts_in(): void {
		$this->assertSame( 'checked', Settings::normalize_consent_default_state( 'checked' ) );
	}

	/**
	 * Every other value fails safe to unchecked. A pre-ticked box is not valid
	 * consent under the GDPR, so an unexpected value must not produce one.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_unexpected_values_fail_safe_to_unchecked(): void {
		foreach ( array( '', 'unchecked', 'yes', '1', 'Checked', 'CHECKED', null, true, 1, array() ) as $value ) {
			$this->assertSame(
				'unchecked',
				Settings::normalize_consent_default_state( $value ),
				'Unexpected consent value must not pre-tick the consent box.'
			);
		}
	}
}
