<?php
/**
 * Mail environment detection service.
 *
 * @package WPAnchorBay\CartBay\Admin\Settings
 */

namespace WPAnchorBay\CartBay\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Detects mail delivery and email logging plugins without sending email.
 *
 * @since 1.0.0
 */
class MailEnvironmentDetector {
	/**
	 * Detect the current mail environment.
	 *
	 * @since 1.0.0
	 *
	 * @return array{has_delivery: bool, has_logger: bool, delivery: array{source: string, detail: string, debug: string, confidence: string}, logger: array{source: string, detail: string, confidence: string}} Mail environment status.
	 */
	public function detect(): array {
		$status = array(
			'has_delivery' => false,
			'has_logger'   => false,
			'delivery'     => array(
				'source'     => '',
				'detail'     => '',
				'debug'      => '',
				'confidence' => '',
			),
			'logger'       => array(
				'source'     => '',
				'detail'     => '',
				'confidence' => '',
			),
		);

		$active_plugins = $this->get_active_plugins();
		$plugin_data    = $this->get_plugin_data();
		$delivery       = $this->detect_delivery( $active_plugins, $plugin_data );
		$logger         = $this->detect_logger( $active_plugins, $plugin_data );

		if ( ! empty( $delivery ) ) {
			$status['has_delivery'] = true;
			$status['delivery']     = array_merge( $status['delivery'], $delivery );
		}

		if ( ! empty( $logger ) ) {
			$status['has_logger'] = true;
			$status['logger']     = $logger;
		}

		/**
		 * Filter the detected CartBay mail environment status.
		 *
		 * @since 1.0.0
		 *
		 * @param array $status Mail environment status.
		 */
		return apply_filters( 'cartbay_mail_environment_status', $status );
	}

	/**
	 * Detect mail delivery signals.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>          $active_plugins Active plugin basenames.
	 * @param array<string, array<mixed>> $plugin_data    Plugin metadata keyed by basename.
	 *
	 * @return array{source: string, detail: string, debug: string, confidence: string}|array{} Delivery result.
	 */
	private function detect_delivery( array $active_plugins, array $plugin_data ): array {
		$known_plugins = $this->get_delivery_plugins();

		foreach ( $known_plugins as $plugin => $name ) {
			if ( in_array( $plugin, $active_plugins, true ) ) {
				return array(
					'source'     => 'known_plugin',
					'detail'     => $name,
					'debug'      => '',
					'confidence' => 'high',
				);
			}
		}

		$metadata_match = $this->detect_delivery_from_metadata( $active_plugins, $plugin_data );
		if ( ! empty( $metadata_match ) ) {
			return $metadata_match;
		}

		/*
		 * Hook-based detection is a fallback for delivery plugins we do not know
		 * by basename. It is deliberately conservative: WordPress and
		 * WooCommerce core both register their own callbacks on these hooks
		 * (WooCommerce attaches WC_Email::handle_multipart to phpmailer_init on
		 * every store), so the mere presence of a callback proves nothing. A
		 * callback only counts as delivery when it is not a core callback AND
		 * its name names a delivery service. Every registered callback is
		 * examined, not just the first, because core's usually is first.
		 */
		$hook_confidence = array(
			'phpmailer_init' => 'medium',
			'pre_wp_mail'    => 'medium',
			'wp_mail'        => 'low',
		);

		foreach ( $hook_confidence as $hook => $confidence ) {
			foreach ( $this->hook_callbacks( $hook ) as $callback ) {
				if ( $this->is_core_callback( $callback ) ) {
					continue;
				}

				if ( ! $this->contains_delivery_keyword( $callback ) ) {
					continue;
				}

				return array(
					'source'     => $hook . '_hook',
					'detail'     => __( 'A mail delivery plugin is handling delivery.', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
					'debug'      => $callback,
					'confidence' => $confidence,
				);
			}
		}

		return array();
	}

	/**
	 * Detect email logger signals.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>          $active_plugins Active plugin basenames.
	 * @param array<string, array<mixed>> $plugin_data    Plugin metadata keyed by basename.
	 *
	 * @return array{source: string, detail: string, confidence: string}|array{} Logger result.
	 */
	private function detect_logger( array $active_plugins, array $plugin_data ): array {
		$known_plugins = $this->get_logger_plugins();

		foreach ( $known_plugins as $plugin => $name ) {
			if ( in_array( $plugin, $active_plugins, true ) ) {
				return array(
					'source'     => 'known_plugin',
					'detail'     => $name,
					'confidence' => 'high',
				);
			}
		}

		foreach ( $active_plugins as $plugin ) {
			$metadata = $this->metadata_text( $plugin, $plugin_data );

			if ( $this->contains_logger_keyword( $metadata ) ) {
				return array(
					'source'     => 'plugin_metadata',
					'detail'     => $this->plugin_name( $plugin, $plugin_data ),
					'confidence' => 'medium',
				);
			}
		}

		return array();
	}

	/**
	 * Get known mail delivery plugins.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Plugin names keyed by basename.
	 */
	private function get_delivery_plugins(): array {
		$plugins = array(
			'wp-mail-smtp/wp_mail_smtp.php'     => 'WP Mail SMTP',
			'postman-smtp/postman-smtp.php'     => 'Postman SMTP',
			'post-smtp/postman-smtp.php'        => 'Post SMTP',
			'easy-wp-smtp/easy-wp-smtp.php'     => 'Easy WP SMTP',
			'fluent-smtp/fluent-smtp.php'       => 'FluentSMTP',
			'smtp-mailer/main.php'              => 'SMTP Mailer',
			'wp-smtp/wp-smtp.php'               => 'WP SMTP',
			'mailgun/mailgun.php'               => 'Mailgun',
			'sendgrid-email-delivery-simplified/wpsendgrid.php' => 'SendGrid',
			'postmark-approved-wordpress-plugin/postmark.php' => 'Postmark',
			'mailin/sendinblue.php'             => 'Brevo',
			'wp-offload-ses/wp-offload-ses.php' => 'WP Offload SES',
			'wp-ses/wp-ses.php'                 => 'WP SES',
			'sparkpost/sparkpost.php'           => 'SparkPost',
		);

		/**
		 * Filter known mail delivery plugin basenames.
		 *
		 * @since 1.0.0
		 *
		 * @param array $plugins Plugin names keyed by basename.
		 */
		return apply_filters( 'cartbay_mail_delivery_plugins', $plugins );
	}

	/**
	 * Get known email logger plugins.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Plugin names keyed by basename.
	 */
	private function get_logger_plugins(): array {
		$plugins = array(
			'wp-mail-logging/wp-mail-logging.php' => 'WP Mail Logging',
			'email-log/email-log.php'             => 'Email Log',
			'wp-mail-catcher/WpMailCatcher.php'   => 'WP Mail Catcher',
			'check-email/check-email.php'         => 'Check & Log Email',
		);

		/**
		 * Filter known email logger plugin basenames.
		 *
		 * @since 1.0.0
		 *
		 * @param array $plugins Plugin names keyed by basename.
		 */
		return apply_filters( 'cartbay_email_logger_plugins', $plugins );
	}

	/**
	 * Detect a mail delivery plugin from active plugin metadata.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>          $active_plugins Active plugin basenames.
	 * @param array<string, array<mixed>> $plugin_data    Plugin metadata keyed by basename.
	 *
	 * @return array{source: string, detail: string, debug: string, confidence: string}|array{} Delivery result.
	 */
	private function detect_delivery_from_metadata( array $active_plugins, array $plugin_data ): array {
		foreach ( $active_plugins as $plugin ) {
			$metadata = $this->metadata_text( $plugin, $plugin_data );

			if ( $this->contains_logger_keyword( $metadata ) ) {
				continue;
			}

			if ( $this->contains_delivery_keyword( $metadata ) ) {
				return array(
					'source'     => 'plugin_metadata',
					'detail'     => $this->plugin_name( $plugin, $plugin_data ),
					'debug'      => '',
					'confidence' => 'medium',
				);
			}
		}

		return array();
	}

	/**
	 * Get active plugin basenames, including network-active plugins.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Active plugin basenames.
	 */
	private function get_active_plugins(): array {
		$plugins = get_option( 'active_plugins', array() );

		if ( ! is_array( $plugins ) ) {
			$plugins = array();
		}

		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );

		if ( is_array( $network_plugins ) ) {
			$plugins = array_merge( $plugins, array_keys( $network_plugins ) );
		}

		return array_values( array_unique( array_map( 'sanitize_text_field', $plugins ) ) );
	}

	/**
	 * Get installed plugin metadata.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<mixed>> Plugin metadata keyed by basename.
	 */
	private function get_plugin_data(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			$plugin_file = ABSPATH . 'wp-admin/includes/plugin.php';

			if ( file_exists( $plugin_file ) ) {
				require_once $plugin_file;
			}
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			return array();
		}

		$plugins = get_plugins();

		return $plugins;
	}

	/**
	 * Get combined searchable metadata text for a plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string                      $plugin      Plugin basename.
	 * @param array<string, array<mixed>> $plugin_data Plugin metadata keyed by basename.
	 *
	 * @return string Searchable plugin metadata text.
	 */
	private function metadata_text( string $plugin, array $plugin_data ): string {
		$data = $plugin_data[ $plugin ] ?? array();

		return strtolower(
			$plugin . ' ' .
			(string) ( $data['Name'] ?? '' ) . ' ' .
			(string) ( $data['Title'] ?? '' ) . ' ' .
			(string) ( $data['Description'] ?? '' )
		);
	}

	/**
	 * Get a display name for a plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string                      $plugin      Plugin basename.
	 * @param array<string, array<mixed>> $plugin_data Plugin metadata keyed by basename.
	 *
	 * @return string Plugin display name.
	 */
	private function plugin_name( string $plugin, array $plugin_data ): string {
		$data = $plugin_data[ $plugin ] ?? array();
		$name = (string) ( $data['Name'] ?? $data['Title'] ?? '' );

		return '' !== $name ? $name : $plugin;
	}

	/**
	 * Check whether text contains a delivery keyword.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to inspect.
	 *
	 * @return bool Whether a delivery keyword is present.
	 */
	private function contains_delivery_keyword( string $text ): bool {
		$keywords = array(
			'smtp',
			'mailgun',
			'sendgrid',
			'postmark',
			'sparkpost',
			'brevo',
			'sendinblue',
			'amazon ses',
			'wp ses',
			'transactional email',
			'email delivery',
			'mail delivery',
			'email deliverability',
		);

		foreach ( $keywords as $keyword ) {
			if ( str_contains( strtolower( $text ), $keyword ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether text contains an email logging keyword.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to inspect.
	 *
	 * @return bool Whether an email logging keyword is present.
	 */
	private function contains_logger_keyword( string $text ): bool {
		$keywords = array(
			'wp mail logging',
			'mail logging',
			'email logging',
			'wp mail log',
			'mail log',
			'email log',
			'mail catcher',
		);

		foreach ( $keywords as $keyword ) {
			if ( str_contains( strtolower( $text ), $keyword ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return every callback description registered on a hook, without executing it.
	 *
	 * Reads $wp_filter directly so detection never fires the hook. Callers must
	 * filter the result — a registered callback is not by itself evidence of
	 * anything, since WordPress and WooCommerce core register their own.
	 *
	 * @since 1.1.1
	 *
	 * @param string $hook Hook name.
	 *
	 * @return array<int, string> Callback descriptions, in registration order.
	 */
	private function hook_callbacks( string $hook ): array {
		if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
			return array();
		}

		$callbacks = $GLOBALS['wp_filter'][ $hook ]->callbacks ?? array();

		if ( ! is_array( $callbacks ) ) {
			return array();
		}

		$descriptions = array();

		foreach ( $callbacks as $priority_callbacks ) {
			if ( ! is_array( $priority_callbacks ) ) {
				continue;
			}

			foreach ( $priority_callbacks as $callback ) {
				if ( ! is_array( $callback ) || ! isset( $callback['function'] ) ) {
					continue;
				}

				$description = $this->callback_to_string( $callback['function'] );

				if ( '' !== $description ) {
					$descriptions[] = $description;
				}
			}
		}

		return $descriptions;
	}

	/**
	 * Determine whether a callback description belongs to WordPress or WooCommerce core.
	 *
	 * Core callbacks on the mail hooks are routine and say nothing about how a
	 * site delivers email. WooCommerce in particular attaches
	 * WC_Email::handle_multipart to phpmailer_init on every store, which would
	 * otherwise read as "SMTP delivery detected" on a store that has no SMTP
	 * plugin at all.
	 *
	 * @since 1.1.1
	 *
	 * @param string $callback Callback description.
	 *
	 * @return bool True when the callback is a core callback.
	 */
	private function is_core_callback( string $callback ): bool {
		$normalized = strtolower( $callback );

		/*
		 * Deliberately narrow. A broad 'wp_' prefix would swallow genuine
		 * delivery plugins (wp_mail_smtp_*, wp-ses), so core is excluded by the
		 * prefixes that cannot belong to a delivery plugin, plus the specific
		 * core callbacks known to sit on these hooks.
		 */
		$prefixes = array(
			'wc_',
			'woocommerce',
			'automattic\\woocommerce',
		);

		foreach ( $prefixes as $prefix ) {
			if ( str_starts_with( $normalized, $prefix ) ) {
				return true;
			}
		}

		$core_callbacks = array(
			'wp_staticize_emoji_for_email',
			'wp_mail_succeeded',
			'wp_mail_failed',
		);

		return in_array( $normalized, $core_callbacks, true );
	}

	/**
	 * Convert a hook callback to a safe short description.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $callback Hook callback.
	 *
	 * @return string Callback description.
	 */
	private function callback_to_string( mixed $callback ): string {
		if ( is_string( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) && isset( $callback[0], $callback[1] ) ) {
			$class = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];

			return $class . '::' . (string) $callback[1];
		}

		if ( $callback instanceof \Closure ) {
			return 'Closure';
		}

		if ( is_object( $callback ) ) {
			return get_class( $callback );
		}

		return __( 'unknown callback', 'cartbay-abandoned-cart-recovery-for-woocommerce' );
	}
}
