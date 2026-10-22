<?php
/**
 * Tests for the recovery-email delivery funnel.
 *
 * @package WPAnchorBay\CartBay\Tests\Analytics
 */

namespace WPAnchorBay\CartBay\Tests\Analytics;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WPAnchorBay\CartBay\Analytics\AnalyticsService;

/**
 * Recovery-email funnel tests.
 *
 * @since 1.1.2
 */
class AnalyticsServiceTest extends TestCase {

	/**
	 * Prepare WordPress function doubles.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'sanitize_key' )->alias( 'strtolower' );
		Functions\when( 'absint' )->alias( 'abs' );
	}

	/**
	 * Clean up WordPress function doubles.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Run the private funnel builder over one session holding the notifications.
	 *
	 * @since 1.1.2
	 *
	 * @param array<int, array<string, mixed>> $notifications Notification entries.
	 *
	 * @return array<string, int> Funnel counts.
	 */
	private function funnel( array $notifications ): array {
		$session = Mockery::mock( 'WC_Order' );
		$session->shouldReceive( 'get_meta' )->with( '_cartbay_notifications', true )->andReturn( $notifications );

		$method = new ReflectionMethod( AnalyticsService::class, 'build_email_funnel' );
		$method->setAccessible( true );

		return $method->invoke( new AnalyticsService(), array( $session ) );
	}

	/**
	 * A failed send that is waiting to retry is a failure, not a pending email.
	 * Counting it as pending showed Emails Failed 0 after a mail failure.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	public function test_retry_queued_counts_as_failed_not_pending(): void {
		$funnel = $this->funnel(
			array(
				array(
					'status'   => 'retry_queued',
					'attempts' => 1,
				),
			)
		);

		$this->assertSame( 0, $funnel['queued'] );
		$this->assertSame( 1, $funnel['failed'] );
		$this->assertSame( 1, $funnel['attempted'] );
		$this->assertSame( 0, $funnel['sent'] );
	}

	/**
	 * Each status lands in exactly one of pending, sent or failed.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	public function test_each_status_is_counted_once(): void {
		$funnel = $this->funnel(
			array(
				array( 'status' => 'queued' ),
				array(
					'status'   => 'sent',
					'attempts' => 1,
				),
				array(
					'status'   => 'failed',
					'attempts' => 3,
				),
				array(
					'status'   => 'retry_queued',
					'attempts' => 2,
				),
			)
		);

		$this->assertSame( 1, $funnel['queued'] );
		$this->assertSame( 1, $funnel['sent'] );
		$this->assertSame( 2, $funnel['failed'] );
		$this->assertSame( 3, $funnel['attempted'] );
	}
}
