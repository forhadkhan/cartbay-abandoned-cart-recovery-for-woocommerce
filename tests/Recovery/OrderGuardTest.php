<?php
/**
 * Tests for the order guard, the send-time guard and the retention cleanup.
 *
 * @package WPAnchorBay\CartBay\Tests\Recovery
 */

namespace WPAnchorBay\CartBay\Tests\Recovery;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WPAnchorBay\CartBay\Data\SessionRepository;
use WPAnchorBay\CartBay\Recovery\EmailSequenceService;
use WPAnchorBay\CartBay\Recovery\NotificationService;
use WPAnchorBay\CartBay\Recovery\OrderGuard;

/**
 * Order guard tests.
 *
 * @since 1.1.3
 */
class OrderGuardTest extends TestCase {

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
	 * Build a session mock created an hour ago.
	 *
	 * @since 1.1.3
	 *
	 * @param string $status Unprefixed session status.
	 *
	 * @return \Mockery\MockInterface
	 */
	private function session( string $status = 'cartbay-abandoned' ) {
		$created = Mockery::mock( 'WC_DateTime' );
		$created->shouldReceive( 'getTimestamp' )->andReturn( 1700000000 );

		$session = Mockery::mock( 'WC_Order' );
		$session->shouldReceive( 'get_id' )->andReturn( 42 );
		$session->shouldReceive( 'get_status' )->andReturn( $status );
		$session->shouldReceive( 'get_billing_email' )->andReturn( 'buyer@example.com' );
		$session->shouldReceive( 'get_date_created' )->andReturn( $created );

		return $session;
	}

	/**
	 * On-hold counts as ordered by default, and the list is filterable.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_ordered_statuses_default_and_filter(): void {
		$this->assertSame( array( 'processing', 'completed', 'on-hold' ), OrderGuard::ordered_statuses() );

		Filters\expectApplied( 'cartbay_ordered_order_statuses' )->once()->andReturn( array( 'completed' ) );
		$this->assertSame( array( 'completed' ), OrderGuard::ordered_statuses() );
	}

	/**
	 * The lookup is one HPOS-safe query, limited to one id, for the session email.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_find_order_id_runs_one_limited_query(): void {
		Functions\expect( 'wc_get_orders' )
			->once()
			->with(
				Mockery::on(
					static function ( $args ) {
						return 'buyer@example.com' === $args['billing_email']
							&& array( 'processing', 'completed', 'on-hold' ) === $args['status']
							&& 1 === $args['limit']
							&& 'ids' === $args['return']
							&& '>=1700000000' === $args['date_created'];
					}
				)
			)
			->andReturn( array( 900 ) );

		$this->assertSame( 900, OrderGuard::find_order_id( $this->session() ) );
	}

	/**
	 * An already-abandoned session is not emailed when its shopper has ordered.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_send_step_skips_with_already_ordered(): void {
		Functions\when( 'wc_get_order' )->justReturn( $this->session() );
		Functions\expect( 'wc_get_orders' )->once()->andReturn( array( 900 ) );
		Functions\expect( 'WC' )->never();

		$notifications = Mockery::mock( NotificationService::class );
		$notifications->shouldReceive( 'cancel_pending_for_session' )->once()->with( 42, 'already_ordered' );
		$sessions = Mockery::mock( SessionRepository::class );
		$sessions->shouldReceive( 'add_event' )
			->once()
			->with(
				42,
				'email_skipped',
				Mockery::on(
					static fn( $data ) => 'already_ordered' === $data['reason'] && 0 === $data['step']
				)
			);

		( new EmailSequenceService( $sessions, $notifications ) )->send_step( 42, 0 );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Retention cleanup unschedules each step with its full arguments.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_prune_expired_unschedules_with_step_arguments(): void {
		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_id' )->andReturn( 42 );
		$order->shouldReceive( 'delete' )->once()->with( true );

		Functions\when( 'wc_get_orders' )->justReturn( array( $order ) );
		Functions\when( 'as_unschedule_all_actions' )->alias(
			static function ( $hook, $args, $group ) use ( &$calls ) {
				$calls[] = array( $hook, $args, $group );
			}
		);
		$calls = array();

		$this->define_time_constants();
		( new SessionRepository() )->prune_expired();

		$this->assertContains( array( 'cartbay_send_recovery_email', array( 42, 0 ), 'cartbay' ), $calls );
		$this->assertContains( array( 'cartbay_send_recovery_email', array( 42, 1 ), 'cartbay' ), $calls );
		$this->assertContains( array( 'cartbay_send_recovery_email', array( 42, 2 ), 'cartbay' ), $calls );
		$this->assertNotContains( array( 'cartbay_send_recovery_email', array( 42 ), 'cartbay' ), $calls );
	}

	/**
	 * Define WordPress time constants the repository relies on.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	private function define_time_constants(): void {
		defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
	}
}
