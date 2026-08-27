<?php
/**
 * Tests for WooCommerce core recovery email suppression.
 *
 * @package WPAnchorBay\CartBay\Tests\Compat
 */

namespace WPAnchorBay\CartBay\Tests\Compat;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPAnchorBay\CartBay\Compat\CoreRecoveryEmail;

/**
 * Core recovery email suppression tests.
 *
 * @since 1.1.1
 */
class CoreRecoveryEmailTest extends TestCase {

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
	 * With capture on, CartBay owns recovery and WooCommerce must stand down,
	 * so one shopper cannot receive recovery mail from two systems.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_suppresses_core_email_while_capture_is_enabled(): void {
		Functions\when( 'get_option' )->justReturn( array( 'capture_enabled' => 'yes' ) );

		$this->assertTrue( ( new CoreRecoveryEmail() )->suppress_core_recovery_email( false ) );
	}

	/**
	 * With capture off CartBay is not recovering anything, so WooCommerce's own
	 * email must be left exactly as the merchant configured it.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_leaves_core_email_alone_while_capture_is_disabled(): void {
		Functions\when( 'get_option' )->justReturn( array( 'capture_enabled' => 'no' ) );

		$this->assertFalse( ( new CoreRecoveryEmail() )->suppress_core_recovery_email( false ) );
	}

	/**
	 * Another plugin's suppression is never undone.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_preserves_an_existing_suppression_when_capture_is_disabled(): void {
		Functions\when( 'get_option' )->justReturn( array( 'capture_enabled' => 'no' ) );

		$this->assertTrue( ( new CoreRecoveryEmail() )->suppress_core_recovery_email( true ) );
	}
}
