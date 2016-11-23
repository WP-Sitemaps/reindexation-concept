<?php

/**
 * Class WP_Sitemap_Indexer
 *
 * Handle full sitemap indexation
 */
class WP_Sitemap_Indexer {

	const OPTION_INDEX_STATUS = 'wp_sitemap_index_status';

	/** @var int Time the indexation started */
	protected $start_time;

	/** @var int Maximum duration of the execution (in seconds) */
	protected $max_duration = 25; // 25 seconds max.

	public function run() {
		// Determine if we need to run; no sitemaps are indexed -or- we have an index_status
		// If no sitemaps exist to index the indexation will be very quick and cheap

		$sitemaps_registered = $this->have_registered_urls();
		$has_index_status    = ( false !== get_option( self::OPTION_INDEX_STATUS, false ) );

		if ( ! $sitemaps_registered || $has_index_status ) {
			$this->index();

			// Reset execution time limit after indexing.
			set_time_limit( ini_get( 'max_execution_time' ) );
		}
	}

	/**
	 * Start/continue a re-indexation of the sitemaps.
	 */
	protected function index() {

		$this->start_time = ! empty( $_SERVER['REQUEST_TIME'] ) ? $_SERVER['REQUEST_TIME'] : time();

		$post_type_indexer = new WP_Sitemap_Indexer_Post_Type( $this );
		$post_type_indexer->run();

		$customer_indexer = new WP_Sitemap_Indexer_Custom( $this );
		$customer_indexer->run();
	}

	/**
	 * Keep track of the current index status
	 *
	 * @param string $type Current indexing type, post_type or custom.
	 * @param mixed  $meta Optional. Added meta information about the current type.
	 */
	public function set_index_indicator( $type, $meta = null ) {
		$status = array( 'type' => $type );
		if ( ! is_null( $meta ) ) {
			$status['meta'] = $meta;
		}

		// Autoload for quick access.
		update_option( self::OPTION_INDEX_STATUS, $status, true );
	}

	/**
	 * Clean up the index status after indexation of the type has finished.
	 */
	public function clear_index_indicator() {
		delete_option( self::OPTION_INDEX_STATUS );
	}

	/**
	 * Get the time the indexation started
	 *
	 * @return int Timestamp.
	 */
	public function get_start_time() {
		return $this->start_time;
	}

	/**
	 * Determines if we have reached our execution time limit
	 *
	 * @return bool True if we have reached the limit.
	 */
	public function reached_execution_limit() {
		$usage = memory_get_usage();
		$limit = ini_get( 'memory_limit' );
		$limit = $this->return_bytes( $limit ) - 4096000;

		if ( $limit > 0 && $usage < $limit ) {
			return false;
		}

		$duration = time() - $this->get_start_time();

		return ( $duration >= $this->max_duration );
	}

	/**
	 * Convert memory_limit value to workable bytes
	 *
	 * @param string $val
	 *
	 * @return int
	 */
	private function return_bytes( $val ) {
		$val  = trim( $val );
		$last = strtolower( $val[ strlen( $val ) - 1 ] );
		$val  = intval( $val );
		switch ( $last ) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	/**
	 * Register an URL to the sitemap
	 *
	 * @param array $register Data to register.
	 */
	public function register_url( $register ) {
		// @todo implement.
		var_export( $register );
	}

	/**
	 * Determine if there are any URLs registered
	 *
	 * @return bool
	 */
	protected function have_registered_urls() {
		// @todo implement.
		return true;
	}
}
