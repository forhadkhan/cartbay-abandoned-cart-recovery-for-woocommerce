<?php
/**
 * Tests for order matching, session reuse after an order and the inactive query.
 *
 * @package WPAnchorBay\CartBay\Tests\Recovery
 */

namespace WPAnchorBay\CartBay\Tests\Recovery;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPAnchorBay\CartBay\Data\SessionRepository;
use WPAnchorBay\CartBay\Recovery\CaptureService;
use WPAnchorBay\CartBay\Recovery\NotificationService;
use WPAnchorBay\CartBay\Recovery\OrderGuard;
use WPAnchorBay\CartBay\Recovery\RecoveryMatcher;

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

/**
 * Recovery matcher tests.
 *
 * @since 1.1.3
 */
class RecoveryMatcherTest extends TestCase {

	/**
	 * Prepare WordPress function doubles.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'get_option' )->alias(
			static function ( $name, $default = false ) {
				if ( 'cartbay_settings' === $name ) {
					return array( 'log_enabled' => false );
				}
				return $default;
			}
		);
		Functions\when( 'absint' )->alias( static fn( $v ) => abs( (int) $v ) );
		Functions\when( 'sanitize_email' )->returnArg();
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'do_action' )->justReturn();
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'as_unschedule_all_actions' )->justReturn();
	}

	/**
	 * Clean up WordPress function doubles.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Build an order mock with the given status.
	 *
	 * @since 1.1.3
	 *
	 * @param string $status Order status.
	 *
	 * @return \Mockery\MockInterface
	 */
	private function order( string $status ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_id' )->andReturn( 900 );
		$order->shouldReceive( 'get_status' )->andReturn( $status );
		$order->shouldReceive( 'get_billing_email' )->andReturn( 'buyer@example.com' );
		$order->shouldReceive( 'get_total' )->andReturn( '25.00' );
		$order->shouldReceive( 'get_meta' )->andReturnUsing(
			static fn( $key ) => '_cartbay_session_id' === $key ? 42 : ''
		);

		return $order;
	}

	/**
	 * Build a session mock.
	 *
	 * @since 1.1.3
	 *
	 * @param string $status       Unprefixed session status.
	 * @param int    $abandoned_at Abandonment timestamp.
	 *
	 * @return \Mockery\MockInterface
	 */
	private function session( string $status, int $abandoned_at ) {
		$session = Mockery::mock( 'WC_Order' );
		$session->shouldReceive( 'get_id' )->andReturn( 42 );
		$session->shouldReceive( 'get_status' )->andReturn( $status );
		$session->shouldReceive( 'get_meta' )->andReturnUsing(
			static fn( $key ) => '_cartbay_abandoned_at' === $key ? $abandoned_at : ''
		);

		return $session;
	}

	/**
	 * Build a matcher whose repository finds the given session.
	 *
	 * @since 1.1.3
	 *
	 * @param \Mockery\MockInterface $session Session mock.
	 *
	 * @return RecoveryMatcher
	 */
	private function matcher_for( $session ): RecoveryMatcher {
		$sessions = Mockery::mock( SessionRepository::class );
		$sessions->shouldReceive( 'find_by_session_id' )->with( 42 )->andReturn( $session );
		$sessions->shouldReceive( 'add_event' );

		$notifications = Mockery::mock( NotificationService::class );
		$notifications->shouldReceive( 'latest_sent_notification_id' )->andReturn( '' );
		$notifications->shouldReceive( 'cancel_pending_for_session' );

		return new RecoveryMatcher( $sessions, $notifications );
	}

	/**
	 * An order placed before abandonment flags the session and changes nothing else.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_order_before_abandonment_flags_session(): void {
		$session = $this->session( 'cartbay-captured', 0 );
		$session->shouldReceive( 'update_meta_data' )->once()->with( OrderGuard::ORDERED_META_KEY, 900 );
		$session->shouldReceive( 'save' )->once();
		$session->shouldNotReceive( 'set_status' );

		$this->matcher_for( $session )->handle_status_change( 900, 'pending', 'processing', $this->order( 'processing' ) );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * An on-hold order stops the sequence but is not attributed as recovered.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_on_hold_order_is_not_attributed_as_recovered(): void {
		$session = $this->session( 'cartbay-abandoned', 1700000000 );
		$session->shouldNotReceive( 'set_status' );
		$session->shouldNotReceive( 'update_meta_data' );

		$this->matcher_for( $session )->handle_status_change( 900, 'pending', 'on-hold', $this->order( 'on-hold' ) );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * A processing order still recovers an abandoned session.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_processing_order_still_recovers_abandoned_session(): void {
		$session = $this->session( 'cartbay-abandoned', 1700000000 );
		$session->shouldReceive( 'set_status' )->once()->with( 'wc-cartbay-recovered' );
		$session->shouldReceive( 'update_meta_data' );
		$session->shouldReceive( 'save' );
		Functions\when( 'WC' )->justReturn( (object) array( 'session' => null ) );

		$this->matcher_for( $session )->handle_status_change( 900, 'pending', 'processing', $this->order( 'processing' ) );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * A session whose shopper ordered is not reused for the next cart.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_ordered_session_is_not_reused_for_capture(): void {
		$hash = hash( 'sha256', 'token' );

		$build = static function ( int $ordered ) use ( $hash ) {
			$session = Mockery::mock( 'WC_Order' );
			$session->shouldReceive( 'get_status' )->andReturn( 'cartbay-captured' );
			$session->shouldReceive( 'get_meta' )->andReturnUsing(
				static function ( $key ) use ( $hash, $ordered ) {
					if ( '_cartbay_capture_token_hash' === $key ) {
						return $hash;
					}
					return OrderGuard::ORDERED_META_KEY === $key ? $ordered : '';
				}
			);
			return $session;
		};

		$reflection = new ReflectionClass( CaptureService::class );
		$service    = $reflection->newInstanceWithoutConstructor();
		$method     = $reflection->getMethod( 'can_update_session' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $service, $build( 0 ), 'token' ) );
		$this->assertFalse( $method->invoke( $service, $build( 900 ), 'token' ) );
	}

	/**
	 * The inactive-session query excludes sessions flagged as ordered.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_inactive_query_excludes_ordered_sessions(): void {
		Functions\expect( 'wc_get_orders' )
			->once()
			->with(
				Mockery::on(
					static fn( $args ) => array(
						array(
							'key'     => '_cartbay_ordered_order_id',
							'compare' => 'NOT EXISTS',
						),
					) === $args['meta_query']
				)
			)
			->andReturn( array() );

		$this->assertSame( array(), ( new SessionRepository() )->get_inactive_captured( 30 ) );
	}
}
