<?php
/**
 * Tests for mail environment detection.
 *
 * @package WPAnchorBay\CartBay\Tests\Admin\Settings
 */

namespace WPAnchorBay\CartBay\Tests\Admin\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPAnchorBay\CartBay\Admin\Settings\MailEnvironmentDetector;

/**
 * Mail environment detector tests.
 *
 * @since 1.1.1
 */
class MailEnvironmentDetectorTest extends TestCase {

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

		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'get_site_option' )->justReturn( array() );
		Functions\when( '__' )->alias(
			static fn ( string $text ): string => $text
		);
		Functions\when( 'sanitize_text_field' )->alias(
			static fn ( mixed $value ): string => is_scalar( $value ) ? trim( (string) $value ) : ''
		);

		$GLOBALS['wp_filter'] = array();
	}

	/**
	 * Clean up WordPress function doubles.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wp_filter'] );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Register a fake hook callback in the global filter registry.
	 *
	 * @since 1.1.1
	 *
	 * @param string $hook     Hook name.
	 * @param mixed  $callback Callback to register.
	 *
	 * @return void
	 */
	private function register_callback( string $hook, mixed $callback ): void {
		$registry = new \stdClass();

		$registry->callbacks = array(
			10 => array(
				'a-callback' => array(
					'function'      => $callback,
					'accepted_args' => 1,
				),
			),
		);

		$GLOBALS['wp_filter'][ $hook ] = $registry;
	}

	/**
	 * WooCommerce core attaches WC_Email::handle_multipart to phpmailer_init on
	 * every store. Treating that as evidence of SMTP told a store with no mail
	 * plugin at all that its email would deliver reliably, and suppressed the
	 * warning it actually needed.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_woocommerce_core_callback_is_not_smtp_delivery(): void {
		$this->register_callback( 'phpmailer_init', array( 'WC_Email_New_Order', 'handle_multipart' ) );

		$status = ( new MailEnvironmentDetector() )->detect();

		$this->assertFalse( $status['has_delivery'], 'WooCommerce core must not be detected as a delivery service.' );
	}

	/**
	 * A store with nothing registered has no delivery service.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_no_callbacks_means_no_delivery(): void {
		$status = ( new MailEnvironmentDetector() )->detect();

		$this->assertFalse( $status['has_delivery'] );
	}

	/**
	 * A genuine delivery plugin behind core's callback is still found — the
	 * detector examines every callback, not just the first one registered.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_delivery_plugin_registered_after_core_is_detected(): void {
		$registry            = new \stdClass();
		$registry->callbacks = array(
			10 => array(
				'core'  => array(
					'function'      => array( 'WC_Email_New_Order', 'handle_multipart' ),
					'accepted_args' => 1,
				),
				'vendor' => array(
					'function'      => array( 'WPMailSMTP\\Processor', 'phpmailer_init' ),
					'accepted_args' => 1,
				),
			),
		);
		$GLOBALS['wp_filter']['phpmailer_init'] = $registry;

		$status = ( new MailEnvironmentDetector() )->detect();

		$this->assertTrue( $status['has_delivery'], 'A real SMTP plugin must still be detected.' );
		$this->assertSame( 'WPMailSMTP\\Processor::phpmailer_init', $status['delivery']['debug'] );
	}

	/**
	 * The merchant-facing detail must never be a raw class::method reference.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_detail_is_not_a_raw_callback_reference(): void {
		$this->register_callback( 'phpmailer_init', array( 'WPMailSMTP\\Processor', 'phpmailer_init' ) );

		$status = ( new MailEnvironmentDetector() )->detect();

		$this->assertTrue( $status['has_delivery'] );
		$this->assertStringNotContainsString( '::', (string) $status['delivery']['detail'] );
	}

	/**
	 * An unrelated third-party callback is not delivery either.
	 *
	 * @since 1.1.1
	 *
	 * @return void
	 */
	public function test_unrelated_callback_is_not_delivery(): void {
		$this->register_callback( 'phpmailer_init', 'my_theme_add_reply_to_header' );

		$status = ( new MailEnvironmentDetector() )->detect();

		$this->assertFalse( $status['has_delivery'] );
	}
}
