<?php
/**
 * Tests for the deal banner rules and the footer sale line phases.
 *
 * @package WPAnchorBay\CartBay\Tests\Admin
 */

namespace WPAnchorBay\CartBay\Tests\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPAnchorBay\CartBay\Admin\DealPromo;

defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
defined( 'WPAB_BANNER_STARTS' ) || define( 'WPAB_BANNER_STARTS', 1794470400 );
defined( 'WPAB_BANNER_ENDS' ) || define( 'WPAB_BANNER_ENDS', 1797811199 );
defined( 'WPAB_BANNER_META' ) || define( 'WPAB_BANNER_META', 'wpab_banner_dismissed' );
defined( 'WPAB_BANNER_CAMPAIGN' ) || define( 'WPAB_BANNER_CAMPAIGN', 'blackfriday26' );

/**
 * Deal promo tests.
 *
 * @since 1.1.4
 */
class DealPromoTest extends TestCase {

	/**
	 * Prepare WordPress function doubles.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'sanitize_key' )->alias( static fn ( $key ) => strtolower( (string) $key ) );
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
	}

	/**
	 * Clean up WordPress function doubles and the request.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_GET = array();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Phase table from the shared footer guide (UTC times).
	 *
	 * @since 1.1.4
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function phase_provider(): array {
		return array(
			'before start'          => array( '2026-11-12 07:59:59', '' ),
			'start'                 => array( '2026-11-12 08:00:00', 'blackfriday' ),
			'last black friday sec' => array( '2026-11-27 23:59:59', 'blackfriday' ),
			'after black friday'    => array( '2026-11-28 00:00:00', 'sale' ),
			'last sale second'      => array( '2026-12-19 23:59:58', 'sale' ),
			'last day starts'       => array( '2026-12-19 23:59:59', 'last_day' ),
			'last day ends'         => array( '2026-12-20 23:59:58', 'last_day' ),
			'end'                   => array( '2026-12-20 23:59:59', '' ),
		);
	}

	/**
	 * The phase follows the campaign dates.
	 *
	 * @since 1.1.4
	 *
	 * @dataProvider phase_provider
	 *
	 * @param string $utc      UTC time.
	 * @param string $expected Expected phase.
	 *
	 * @return void
	 */
	public function test_promo_phase( string $utc, string $expected ): void {
		$now = (int) ( new \DateTimeImmutable( $utc, new \DateTimeZone( 'UTC' ) ) )->format( 'U' );

		$this->assertSame( $expected, DealPromo::promo_phase( $now ) );
	}

	/**
	 * A banner another rule hid stays hidden.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function test_hidden_banner_stays_hidden(): void {
		$this->assertFalse( ( new DealPromo() )->banner_show( false, array( 'cartbay', 'spacebay' ) ) );
	}

	/**
	 * The banner never shows inside the setup wizard.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function test_no_banner_in_wizard(): void {
		$_GET['page'] = 'cartbay-wizard';

		$this->assertFalse( ( new DealPromo() )->banner_show( true, array( 'cartbay', 'spacebay' ) ) );
	}

	/**
	 * With other WPAnchorBay plugins active, their rules apply.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function test_other_plugins_keep_their_rules(): void {
		$this->assertTrue( ( new DealPromo() )->banner_show( true, array( 'cartbay', 'spacebay' ) ) );
	}

	/**
	 * CartBay alone: no banner outside the CartBay tab, for example on the Plugins screen.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function test_cartbay_alone_stays_off_other_screens(): void {
		$this->assertFalse( ( new DealPromo() )->banner_show( true, array( 'cartbay' ) ) );
	}

	/**
	 * CartBay alone: shows on the CartBay tab until the user closes it, then stays closed.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function test_cartbay_alone_on_its_tab_respects_close(): void {
		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'cartbay';

		Functions\when( 'get_user_meta' )->justReturn( '' );
		$this->assertTrue( ( new DealPromo() )->banner_show( true, array( 'cartbay' ) ) );

		Functions\when( 'get_user_meta' )->justReturn( array( 'blackfriday26' => 1795000000 ) );
		$this->assertFalse( ( new DealPromo() )->banner_show( true, array( 'cartbay' ) ) );
	}
}
