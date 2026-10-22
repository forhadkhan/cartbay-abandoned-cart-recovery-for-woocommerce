<?php
/**
 * Tests for the recovery email sequence service.
 *
 * @package WPAnchorBay\CartBay\Tests\Recovery
 */

namespace WPAnchorBay\CartBay\Tests\Recovery;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPAnchorBay\CartBay\Recovery\EmailSequenceService;

/**
 * Recovery email sequence tests.
 *
 * @since 1.1.2
 */
class EmailSequenceServiceTest extends TestCase {

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
	}

	/**
	 * Clean up WordPress function doubles.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * An earlier email's unsubscribe link must keep working after a later step.
	 *
	 * Each step adds a hash next to the earlier ones instead of replacing them,
	 * because the unsubscribe lookup matches any stored hash.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	public function test_each_unsubscribe_token_is_added_without_replacing_earlier_ones(): void {
		$tokens = array( 'first-token', 'second-token' );
		Functions\when( 'wp_generate_password' )->alias(
			static function () use ( &$tokens ) {
				return array_shift( $tokens );
			}
		);

		$stored  = array();
		$session = Mockery::mock( 'WC_Order' );
		$session->shouldReceive( 'add_meta_data' )
			->twice()
			->andReturnUsing(
				static function ( $key, $value, $unique ) use ( &$stored ) {
					$stored[] = array( $key, $value, $unique );
				}
			);
		$session->shouldNotReceive( 'update_meta_data' );

		$reflection = new ReflectionClass( EmailSequenceService::class );
		$service    = $reflection->newInstanceWithoutConstructor();
		$method     = $reflection->getMethod( 'issue_unsubscribe_token' );
		$method->setAccessible( true );

		$first  = $method->invoke( $service, $session );
		$second = $method->invoke( $service, $session );

		$this->assertSame( 'first-token', $first );
		$this->assertSame( 'second-token', $second );
		$this->assertSame(
			array(
				array( '_cartbay_unsub_token_hash', hash( 'sha256', 'first-token' ), false ),
				array( '_cartbay_unsub_token_hash', hash( 'sha256', 'second-token' ), false ),
			),
			$stored
		);
	}
}
