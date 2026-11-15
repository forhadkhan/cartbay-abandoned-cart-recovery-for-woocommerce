<?php
/**
 * Rules for the shared WPAnchorBay deal banner and the CartBay footer sale line.
 *
 * @package CartBay
 * @since   1.1.4
 */

namespace WPAnchorBay\CartBay\Admin;

use WPAnchorBay\CartBay\Admin\Settings\SettingsUrl;

defined( 'ABSPATH' ) || exit;

/**
 * Decides where the shared deal banner may show and prints the footer sale line.
 *
 * The banner itself lives in includes/wpab-banner/ and is shared by every WPAnchorBay plugin. This class only adds
 * CartBay's own rules through the banner's filters, and the short sale line in the footer of the CartBay settings tab.
 * The dates come from the banner constants, so nothing shows outside the campaign.
 *
 * @since 1.1.4
 */
class DealPromo {

	/**
	 * First second after Black Friday 2026 (27 Nov): from here the footer line stops saying "Black Friday".
	 *
	 * @since 1.1.4
	 */
	const BLACK_FRIDAY_OVER = 1795824000;

	/**
	 * Register the banner filter and the footer line.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'wpab_banner_show', array( $this, 'banner_show' ), 10, 2 );
		add_action( 'in_admin_footer', array( $this, 'footer_promo' ) );
	}

	/**
	 * Whether CartBay Pro is active.
	 *
	 * @since 1.1.4
	 *
	 * @return bool
	 */
	public static function has_pro(): bool {
		return defined( 'CARTBAY_PRO_VERSION' );
	}

	/**
	 * Decide whether the shared WPAnchorBay deal banner may show.
	 *
	 * It never shows on a site that has CartBay Pro or inside the setup wizard. When CartBay is the only WPAnchorBay
	 * plugin on the site, the banner is kept to the CartBay settings tab (not the Plugins screen) and stays closed once
	 * a user has closed it. With other WPAnchorBay plugins active, their own rules apply.
	 *
	 * @since 1.1.4
	 *
	 * @param bool          $show  Whether the banner shows.
	 * @param array<string> $slugs Brand keys of the active WPAnchorBay plugins.
	 *
	 * @return bool
	 */
	public function banner_show( $show, $slugs = array() ): bool {
		if ( ! $show || self::has_pro() || $this->on_wizard_screen() ) {
			return false;
		}

		if ( array( 'cartbay' ) !== array_values( (array) $slugs ) ) {
			return true;
		}

		if ( ! $this->on_cartbay_screen() ) {
			return false;
		}

		$closed = defined( 'WPAB_BANNER_META' ) ? get_user_meta( get_current_user_id(), WPAB_BANNER_META, true ) : array();

		return ! ( is_array( $closed ) && defined( 'WPAB_BANNER_CAMPAIGN' ) && isset( $closed[ WPAB_BANNER_CAMPAIGN ] ) );
	}

	/**
	 * Which sale line the footer shows at a given time.
	 *
	 * 'blackfriday' until Black Friday is over, 'sale' after that, 'last_day' for the final 24 hours (with a countdown),
	 * and '' before the start, after the end, or when the banner constants are not loaded.
	 *
	 * @since 1.1.4
	 *
	 * @param int $now Unix time.
	 *
	 * @return string One of 'blackfriday', 'sale', 'last_day' or ''.
	 */
	public static function promo_phase( int $now ): string {
		if ( ! defined( 'WPAB_BANNER_STARTS' ) || ! defined( 'WPAB_BANNER_ENDS' ) || $now < WPAB_BANNER_STARTS || $now >= WPAB_BANNER_ENDS ) {
			return '';
		}

		if ( $now >= WPAB_BANNER_ENDS - DAY_IN_SECONDS ) {
			return 'last_day';
		}

		return $now < self::BLACK_FRIDAY_OVER ? 'blackfriday' : 'sale';
	}

	/**
	 * Print a short sale line in the middle of the footer of the CartBay settings tab, for sites without Pro.
	 *
	 * It never claims a sale that has ended: the wording follows promo_phase(). In the last 24 hours it adds a countdown
	 * computed from the seconds left on the server, so a wrong computer clock does not move it.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function footer_promo(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! $this->on_cartbay_screen() || self::has_pro() ) {
			return;
		}

		$now   = time();
		$phase = self::promo_phase( $now );
		if ( '' === $phase ) {
			return;
		}

		$url = add_query_arg(
			array(
				'utm_source'   => 'cartbay-plugin-admin',
				'utm_medium'   => 'blackfriday-footer',
				'utm_campaign' => 'blackfriday26',
				'utm_content'  => 'pro',
			),
			'https://wpanchorbay.com/deals/'
		);

		if ( 'blackfriday' === $phase ) {
			$text = esc_html__( 'Get CartBay Pro with 70% OFF this Black Friday', 'cartbay-abandoned-cart-recovery-for-woocommerce' );
		} elseif ( 'sale' === $phase ) {
			$text = esc_html__( 'Get CartBay Pro with 70% OFF', 'cartbay-abandoned-cart-recovery-for-woocommerce' );
		} else {
			$left  = max( 0, (int) WPAB_BANNER_ENDS - $now );
			$clock = sprintf( '%02d:%02d:%02d', intdiv( $left, HOUR_IN_SECONDS ), intdiv( $left % HOUR_IN_SECONDS, MINUTE_IN_SECONDS ), $left % MINUTE_IN_SECONDS );
			$text  = sprintf(
				/* translators: %s: Countdown to the end of the sale, for example 05:32:10. */
				esc_html__( 'Last day: Get CartBay Pro with 70%% OFF. Ends in %s', 'cartbay-abandoned-cart-recovery-for-woocommerce' ),
				'<span class="cartbay-footer-promo__clock" data-left="' . esc_attr( (string) $left ) . '">' . esc_html( $clock ) . '</span>'
			);
		}

		printf(
			'<p class="cartbay-footer-promo"><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
			esc_url( $url ),
			wp_kses(
				$text,
				array(
					'span' => array(
						'class'     => true,
						'data-left' => true,
					),
				)
			)
		);

		if ( 'last_day' !== $phase ) {
			return;
		}
		?>
		<script>
		( function () {
			var clock = document.querySelector( '.cartbay-footer-promo__clock' );
			if ( ! clock ) { return; }
			var end = Date.now() + parseInt( clock.getAttribute( 'data-left' ), 10 ) * 1000;
			var line = clock.closest( '.cartbay-footer-promo' );
			function pad( n ) { return ( n < 10 ? '0' : '' ) + n; }
			function tick() {
				var left = Math.max( 0, Math.round( ( end - Date.now() ) / 1000 ) );
				if ( left <= 0 ) { line.hidden = true; return; }
				clock.textContent = pad( Math.floor( left / 3600 ) ) + ':' + pad( Math.floor( left % 3600 / 60 ) ) + ':' + pad( left % 60 );
				setTimeout( tick, 1000 );
			}
			tick();
		}() );
		</script>
		<?php
	}

	/**
	 * Whether the current request is the CartBay tab under WooCommerce > Settings.
	 *
	 * @since 1.1.4
	 *
	 * @return bool
	 */
	private function on_cartbay_screen(): bool {
		return ( new SettingsUrl() )->is_wc_settings_cartbay_page();
	}

	/**
	 * Whether the current request is the CartBay setup wizard.
	 *
	 * @since 1.1.4
	 *
	 * @return bool
	 */
	private function on_wizard_screen(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'cartbay-wizard' === $page;
	}
}
