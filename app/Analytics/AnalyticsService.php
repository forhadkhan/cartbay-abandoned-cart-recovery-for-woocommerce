<?php
/**
 * Analytics service.
 *
 * @package WPAnchorBay\CartBay\Analytics
 */

namespace WPAnchorBay\CartBay\Analytics;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and caches CartBay's core recovery analytics aggregates.
 *
 * This service intentionally computes only the headline recovery metrics that
 * the free plugin renders (tracked, abandoned, recovered, abandoned value,
 * recovered revenue, recovery rate, and the basic email-delivery counts).
 * Deeper reporting (per-step performance, restore-click funnels, shopper
 * behaviour, etc.) is provided by extensions through the documented
 * `cartbay_overview_metric_cards` and `cartbay_notifications_after_summary`
 * hooks, which receive the raw session data and compute their own figures.
 *
 * @since 1.0.0
 */
class AnalyticsService {
	/**
	 * Number of session orders hydrated per analytics query batch.
	 *
	 * @since 1.1.1
	 *
	 * @var int
	 */
	private const QUERY_BATCH_SIZE = 200;


	/**
	 * Transient cache key.
	 *
	 * @since 1.0.0
	 */
	private const CACHE_KEY = 'cartbay_analytics_cache';

	/**
	 * Cache TTL in seconds.
	 *
	 * @since 1.0.0
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Get analytics data for the given period.
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Period length: 7, 30, or 90.
	 *
	 * @return array Analytics data.
	 */
	public function get( int $days = 30 ): array {
		$cache     = get_transient( self::CACHE_KEY );
		$cache_key = "period_{$days}";

		if ( is_array( $cache ) && isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$data = $this->build( $days );

		$all_cache               = is_array( $cache ) ? $cache : array();
		$all_cache[ $cache_key ] = $data;
		set_transient( self::CACHE_KEY, $all_cache, self::CACHE_TTL );

		return $data;
	}

	/**
	 * Force refresh all cached periods.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function refresh(): void {
		self::invalidate_cache();
		foreach ( array( 7, 30, 90 ) as $days ) {
			$this->get( $days );
		}
	}

	/**
	 * Invalidate cached analytics aggregates.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function invalidate_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Build raw analytics for a period.
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Period length in days.
	 *
	 * @return array
	 */
	private function build( int $days ): array {
		$since_timestamp = time() - ( $days * DAY_IN_SECONDS );

		// Count tracked carts on the same CartBay meta-timestamp basis as the
		// abandoned/recovered funnel (capture time), rather than order
		// date_created, so the funnel metrics stay consistent. Counted in the
		// database rather than by hydrating and counting order objects.
		$captured = $this->count_sessions_in_period(
			array( 'wc-cartbay-captured', 'wc-cartbay-abandoned', 'wc-cartbay-recovered' ),
			'_cartbay_captured_at',
			$since_timestamp
		);

		$abandoned       = 0;
		$abandoned_value = 0.0;
		$email_funnel    = array(
			'queued'    => 0,
			'attempted' => 0,
			'sent'      => 0,
			'failed'    => 0,
		);

		$this->each_session_batch_in_period(
			array( 'wc-cartbay-abandoned', 'wc-cartbay-recovered' ),
			'_cartbay_abandoned_at',
			$since_timestamp,
			function ( array $batch ) use ( &$abandoned, &$abandoned_value, &$email_funnel ): void {
				$abandoned       += count( $batch );
				$abandoned_value += $this->sum_abandoned_value( $batch );

				foreach ( $this->build_email_funnel( $batch ) as $key => $value ) {
					$email_funnel[ $key ] += $value;
				}
			}
		);

		$recovered = 0;
		$revenue   = 0.0;

		$this->each_session_batch_in_period(
			array( 'wc-cartbay-recovered' ),
			'_cartbay_recovered_at',
			$since_timestamp,
			function ( array $batch ) use ( &$recovered, &$revenue ): void {
				$recovered += count( $batch );
				$revenue   += $this->sum_recovered_revenue( $batch );
			}
		);

		$rate      = $abandoned > 0 ? round( ( $recovered / $abandoned ) * 100, 1 ) : 0.0;
		$send_rate = $email_funnel['attempted'] > 0 ? round( ( $email_funnel['sent'] / $email_funnel['attempted'] ) * 100, 1 ) : 0.0;

		return array(
			'tracked'         => $captured,
			'abandoned'       => $abandoned,
			'recovered'       => $recovered,
			'abandoned_value' => $abandoned_value,
			'revenue'         => $revenue,
			'recovery_rate'   => $rate,
			'emails_queued'   => $email_funnel['queued'],
			'emails_sent'     => $email_funnel['sent'],
			'emails_failed'   => $email_funnel['failed'],
			'email_send_rate' => $send_rate,
			'period_days'     => $days,
		);
	}

	/**
	 * Build the basic recovery-email delivery counts for a period.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, WC_Order> $abandoned_sessions Abandoned sessions.
	 *
	 * @return array<string, int> Funnel counts: queued, attempted, sent, failed.
	 */
	private function build_email_funnel( array $abandoned_sessions ): array {
		$queued    = 0;
		$attempted = 0;
		$sent      = 0;
		$failed    = 0;

		foreach ( $abandoned_sessions as $session ) {
			foreach ( $this->get_notifications( $session ) as $notification ) {
				$status = sanitize_key( (string) ( $notification['status'] ?? '' ) );
				if ( in_array( $status, array( 'queued', 'retry_queued' ), true ) ) {
					++$queued;
				}

				if ( absint( $notification['attempts'] ?? 0 ) > 0 || in_array( $status, array( 'attempted', 'sent', 'failed', 'delivered' ), true ) ) {
					++$attempted;
				}

				if ( in_array( $status, array( 'sent', 'delivered' ), true ) ) {
					++$sent;
				}

				if ( 'failed' === $status ) {
					++$failed;
				}
			}
		}

		return array(
			'queued'    => $queued,
			'attempted' => $attempted,
			'sent'      => $sent,
			'failed'    => $failed,
		);
	}

	/**
	 * Build the shared query arguments for sessions inside a reporting period.
	 *
	 * The period bound is applied in the database via meta_query. Before 1.1.1
	 * this method fetched every session ever created with limit => -1 and
	 * filtered in PHP, which grew without limit and exhausted memory on a busy
	 * store.
	 *
	 * @since 1.1.1
	 *
	 * @param array<int, string> $statuses        WC order statuses.
	 * @param string             $meta_key        Timestamp meta key.
	 * @param int                $since_timestamp Period start timestamp.
	 *
	 * @return array<string, mixed> Query arguments.
	 */
	private function period_query_args( array $statuses, string $meta_key, int $since_timestamp ): array {
		return array(
			'status'     => $statuses,
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounding the result set in SQL is the point; the alternative is scanning every session.
				array(
					'key'     => $meta_key,
					'value'   => $since_timestamp,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		);
	}

	/**
	 * Count sessions inside the reporting period without hydrating them.
	 *
	 * @since 1.1.1
	 *
	 * @param array<int, string> $statuses        WC order statuses.
	 * @param string             $meta_key        Timestamp meta key.
	 * @param int                $since_timestamp Period start timestamp.
	 *
	 * @return int Matching session count.
	 */
	private function count_sessions_in_period( array $statuses, string $meta_key, int $since_timestamp ): int {
		$results = wc_get_orders(
			array_merge(
				$this->period_query_args( $statuses, $meta_key, $since_timestamp ),
				array(
					'limit'    => 1,
					'return'   => 'ids',
					'paginate' => true,
				)
			)
		);

		return is_object( $results ) ? absint( $results->total ?? 0 ) : 0;
	}

	/**
	 * Walk sessions inside the reporting period in bounded batches.
	 *
	 * Each batch is handed to the callback and then released, so peak memory is
	 * a function of the batch size rather than of how many sessions the store
	 * has accumulated.
	 *
	 * @since 1.1.1
	 *
	 * @param array<int, string> $statuses        WC order statuses.
	 * @param string             $meta_key        Timestamp meta key.
	 * @param int                $since_timestamp Period start timestamp.
	 * @param callable           $handler         Receives array<int, WC_Order>.
	 *
	 * @return void
	 */
	private function each_session_batch_in_period( array $statuses, string $meta_key, int $since_timestamp, callable $handler ): void {
		$args = $this->period_query_args( $statuses, $meta_key, $since_timestamp );
		$page = 1;

		do {
			$batch = wc_get_orders(
				array_merge(
					$args,
					array(
						'limit'  => self::QUERY_BATCH_SIZE,
						'page'   => $page,
						'return' => 'objects',
					)
				)
			);

			if ( ! is_array( $batch ) || array() === $batch ) {
				return;
			}

			$handler( $batch );

			$fetched = count( $batch );
			unset( $batch );
			++$page;
		} while ( self::QUERY_BATCH_SIZE === $fetched );
	}

	/**
	 * Sum recovered revenue from recovered session orders.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, WC_Order> $sessions Recovered session orders.
	 *
	 * @return float Recovered revenue total.
	 */
	private function sum_recovered_revenue( array $sessions ): float {
		$total = 0.0;
		foreach ( $sessions as $session ) {
			$recovered_order_id = absint( $session->get_meta( '_cartbay_recovered_order_id', true ) );
			$recovered_order    = $recovered_order_id > 0 ? wc_get_order( $recovered_order_id ) : null;

			if ( $recovered_order instanceof WC_Order ) {
				$total += (float) $recovered_order->get_total();
				continue;
			}

			$total += floatval( $session->get_meta( '_cartbay_recovered_revenue', true ) );
		}

		return $total;
	}

	/**
	 * Sum value of carts that became abandoned in the period.
	 *
	 * Includes both currently abandoned and recovered sessions, because recovered
	 * sessions were abandoned before recovery.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, WC_Order> $sessions Abandoned session orders.
	 *
	 * @return float Abandoned cart value total.
	 */
	private function sum_abandoned_value( array $sessions ): float {
		$total = 0.0;
		foreach ( $sessions as $session ) {
			$total += floatval( $session->get_meta( '_cartbay_cart_total', true ) );
		}

		return $total;
	}

	/**
	 * Get notifications from a session.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $session Session order.
	 *
	 * @return array<int, array<string, mixed>> Notifications.
	 */
	private function get_notifications( WC_Order $session ): array {
		$notifications = $session->get_meta( '_cartbay_notifications', true );

		return is_array( $notifications ) ? array_values( array_filter( $notifications, 'is_array' ) ) : array();
	}
}
