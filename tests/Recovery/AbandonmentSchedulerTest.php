<?php
/**
 * Tests for the abandonment scheduler.
 *
 * @package WPAnchorBay\CartBay\Tests\Recovery
 */

namespace WPAnchorBay\CartBay\Tests\Recovery;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WPAnchorBay\CartBay\Data\SessionRepository;
use WPAnchorBay\CartBay\Recovery\AbandonmentScheduler;
use WPAnchorBay\CartBay\Recovery\NotificationService;

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

/**
 * Abandonment scheduler tests.
 *
 * @since 1.1.3
 */
class AbandonmentSchedulerTest extends TestCase {

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
				if ( 'cartbay_campaign_settings' === $name ) {
					return array(
						'enabled' => true,
						'steps'   => array( array( 'delay_minutes' => 60 ) ),
					);
				}
				if ( 'cartbay_settings' === $name ) {
					return array( 'log_enabled' => false );
				}
				return $default;
			}
		);
		Functions\when( 'absint' )->alias( static fn( $v ) => abs( (int) $v ) );
		Functions\when( 'sanitize_email' )->returnArg();
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'do_action' )->justReturn();
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
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
	 * Build a captured session mock that went quiet an hour ago.
	 *
	 * @since 1.1.3
	 *
	 * @param array<string, mixed> $meta Meta values keyed by name.
	 *
	 * @return \Mockery\MockInterface
	 */
	private function captured_session( array $meta = array() ) {
		$created = Mockery::mock( 'WC_DateTime' );
		$created->shouldReceive( 'getTimestamp' )->andReturn( time() - 7200 );

		$session = Mockery::mock( 'WC_Order' );
		$session->shouldReceive( 'get_id' )->andReturn( 42 );
		$session->shouldReceive( 'get_status' )->andReturn( 'cartbay-captured' );
		$session->shouldReceive( 'get_billing_email' )->andReturn( 'buyer@example.com' );
		$session->shouldReceive( 'get_date_created' )->andReturn( $created );
		$session->shouldReceive( 'get_meta' )->andReturnUsing(
			static function ( $key ) use ( $meta ) {
				if ( '_cartbay_last_activity_at' === $key ) {
					return time() - 3600;
				}
				return $meta[ $key ] ?? '';
			}
		);

		return $session;
	}

	/**
	 * Build the scheduler around a session repository returning one session.
	 *
	 * @since 1.1.3
	 *
	 * @param \Mockery\MockInterface $session Session mock.
	 *
	 * @return AbandonmentScheduler
	 */
	private function scheduler_for( $session ): AbandonmentScheduler {
		$sessions = Mockery::mock( SessionRepository::class );
		$sessions->shouldReceive( 'get' )->with( 42 )->andReturn( $session );
		$sessions->shouldReceive( 'add_event' )->byDefault();

		return new AbandonmentScheduler( $sessions, Mockery::mock( NotificationService::class ) );
	}

	/**
	 * A shopper who paid before the timeout must not enter the email sequence.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_session_with_a_paid_order_is_not_marked_abandoned_or_scheduled(): void {
		$session = $this->captured_session();
		$session->shouldNotReceive( 'set_status' );
		$session->shouldReceive( 'update_meta_data' )->with( '_cartbay_ordered_order_id', 900 )->once();
		$session->shouldReceive( 'save' )->byDefault();

		Functions\expect( 'wc_get_orders' )->once()->andReturn( array( 900 ) );
		Functions\expect( 'as_schedule_single_action' )->never();

		$this->scheduler_for( $session )->run_for_session( 42 );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * A session already flagged as ordered is skipped without any order lookup.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_flagged_session_is_skipped_without_lookup(): void {
		$session = $this->captured_session( array( '_cartbay_ordered_order_id' => 900 ) );
		$session->shouldNotReceive( 'set_status' );

		Functions\expect( 'wc_get_orders' )->never();
		Functions\expect( 'as_schedule_single_action' )->never();

		$this->scheduler_for( $session )->run_for_session( 42 );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Without an order the session is still abandoned and the sequence scheduled.
	 *
	 * @since 1.1.3
	 *
	 * @return void
	 */
	public function test_session_without_order_is_still_abandoned(): void {
		$session = $this->captured_session();
		$session->shouldReceive( 'set_status' )->with( 'wc-cartbay-abandoned' )->once();
		$session->shouldReceive( 'update_meta_data' )->byDefault();
		$session->shouldReceive( 'save' )->byDefault();

		Functions\expect( 'wc_get_orders' )->once()->andReturn( array() );
		Functions\when( 'as_has_scheduled_action' )->justReturn( false );
		Functions\expect( 'as_schedule_single_action' )->once();

		$notifications = Mockery::mock( NotificationService::class );
		$notifications->shouldReceive( 'queue' )->once();
		$sessions = Mockery::mock( SessionRepository::class );
		$sessions->shouldReceive( 'get' )->with( 42 )->andReturn( $session );
		$sessions->shouldReceive( 'add_event' );

		( new AbandonmentScheduler( $sessions, $notifications ) )->run_for_session( 42 );

		$this->addToAssertionCount( 1 );
	}
}
