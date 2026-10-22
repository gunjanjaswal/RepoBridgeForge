<?php
/**
 * Plugin orchestrator: wiring, cron, lifecycle.
 *
 * @package WP_GitHub_Sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RepoBridgeForge_Plugin {

	const CRON_HOOK = 'repobridgeforge_scheduled_sync';

	/** @var RepoBridgeForge_Plugin|null */
	private static $instance = null;

	/** @var RepoBridgeForge_Settings */
	public $settings;

	/** @var RepoBridgeForge_Logger */
	public $logger;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->logger   = new RepoBridgeForge_Logger();
		$this->settings = new RepoBridgeForge_Settings();
	}

	public function init() {
		// Translations for WordPress.org-hosted plugins are loaded automatically since WP 4.6.
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
		add_action( self::CRON_HOOK, array( $this, 'run_scheduled_sync' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		if ( is_admin() ) {
			$admin = new RepoBridgeForge_Admin_Page( $this->settings, $this->logger );
			$admin->init();
		}
	}

	public function run_scheduled_sync() {
		$engine = new RepoBridgeForge_Sync_Engine( $this->settings, $this->logger );
		$engine->sync();
	}

	/**
	 * Register the GitHub push webhook endpoint.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'repobridgeforge/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle a GitHub webhook: verify the signature, then sync on a push.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_webhook( $request ) {
		$secret = (string) $this->settings->get( 'webhook_secret', '' );
		if ( '' === $secret ) {
			return new WP_REST_Response( array( 'message' => 'Webhook secret not configured.' ), 403 );
		}

		$signature = (string) $request->get_header( 'x_hub_signature_256' );
		$body      = (string) $request->get_body();
		if ( '' === $signature ) {
			return new WP_REST_Response( array( 'message' => 'Missing signature.' ), 401 );
		}

		$expected = 'sha256=' . hash_hmac( 'sha256', $body, $secret );
		if ( ! hash_equals( $expected, $signature ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid signature.' ), 401 );
		}

		$event = (string) $request->get_header( 'x_github_event' );
		if ( 'ping' === $event ) {
			return new WP_REST_Response( array( 'message' => 'pong' ), 200 );
		}
		if ( '' !== $event && 'push' !== $event ) {
			return new WP_REST_Response( array( 'message' => 'Ignored event: ' . $event ), 200 );
		}

		$engine = new RepoBridgeForge_Sync_Engine( $this->settings, $this->logger );
		$result = $engine->sync();
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'message' => $result->get_error_message() ), 500 );
		}

		return new WP_REST_Response( array( 'message' => 'Synced.' ), 200 );
	}

	/**
	 * Add a 15-minute schedule for users who want tighter polling.
	 *
	 * @param array $schedules
	 * @return array
	 */
	public function add_cron_interval( $schedules ) {
		$schedules['repobridgeforge_15min'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes (Repo Bridge Forge)', 'repobridgeforge' ),
		);
		return $schedules;
	}

	/**
	 * Re-schedule the cron event to match the configured frequency.
	 * Called on activation and whenever settings are saved.
	 */
	public function reschedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}

		$frequency = $this->settings->get( 'frequency', 'manual' );
		$valid     = array( 'repobridgeforge_15min', 'hourly', 'twicedaily', 'daily' );

		if ( in_array( $frequency, $valid, true ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $frequency, self::CRON_HOOK );
		}
	}

	/* -------------------------------------------------------------------------
	 * Lifecycle
	 * ---------------------------------------------------------------------- */

	public static function activate() {
		self::instance()->reschedule();
	}

	public static function deactivate() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
