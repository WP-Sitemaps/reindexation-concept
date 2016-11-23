<?php

class WP_Sitemap_Indexer_Custom {
	/** @var WP_Sitemap_Indexer */
	protected $indexer;

	/**
	 * WP_Sitemap_Indexer_Custom constructor.
	 *
	 * @param WP_Sitemap_Indexer $indexer
	 */
	public function __construct( WP_Sitemap_Indexer $indexer ) {
		$this->indexer = $indexer;
	}

	/**
	 * @return bool
	 */
	public function run() {
		$finished = $this->index();

		if ( $finished ) {
			$this->finish();
		}

		return $finished;
	}

	/**
	 * Index custom URLs
	 *
	 * @return bool True if all custom URLs have been processed.
	 */
	protected function index() {
		$finished = false;

		// Should be fetched from options, to allow for continuing.
		$iteration = get_option( 'wp_sitemap_custom_url_run', 0 );

		// Get an indication of how many iterations are needed.
		$iterations = apply_filters( 'wp_sitemap_register_urls_iterations', 'unknown' );

		$this->indexer->set_index_indicator( 'custom', array(
			'iteration'  => $iteration,
			'iterations' => $iterations
		) );

		do {
			// Make sure we don't go on forever.
			if ( $this->indexer->reached_execution_limit() ) {
				break;
			}

			// Request custom URLs.
			$register_urls = apply_filters( 'wp_sitemap_register_urls', array(), $iteration );

			// If we received no custom URLs, we are done!
			if ( ! is_array( $register_urls ) || array() === $register_urls ) {
				$finished = true;
				break;
			}

			// Register the URLs provided.
			foreach ( $register_urls as $register ) {
				$this->register_custom_url( $register );
			}

			// Bump the $iteration.
			$iteration ++;

			// Save the index to the options.
			update_option( 'wp_sitemap_custom_url_iteration', $iteration, $autoload = false );

		} while ( true );

		return $finished;
	}

	/**
	 * Clean up custom indexation options
	 */
	protected function finish() {
		// Clean up re-indexation option data.
		delete_option( 'wp_sitemap_custom_url_iteration' );

		$this->indexer->clear_index_indicator();
	}

	/**
	 * Validate the custom URL format before entering it in the database
	 *
	 * @param array $data Data provided by the custom URL implementation.
	 *
	 * @return bool True if the data validates.
	 */
	protected function is_valid_custom_url_format( $data ) {

		$valid = true;

		$keys      = array( 'URL', 'identifier', 'last_modified' );
		$data_keys = array_keys( $data );

		// If any keys are missing, it is invalid.
		if ( $keys !== array_intersect( $keys, $data_keys ) ) {
			$this->trigger_url_format_error( $data, 'Missing keys in custom URL format.' );
			$valid = false;
		}

		// Verify URL format
		$parsed = parse_url( $data['URL'] );

		if ( false === $parsed ) {
			$this->trigger_url_format_error( $data, 'Malformed URL provided.' );
			$valid = false;
		} else {
			if ( ! isset( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ) ) ) {
				$this->trigger_url_format_error( $data, 'Invalid or missing URL-scheme provided.' );
				$valid = false;
			}

			if ( ! isset( $parsed['host'] ) ) {
				$this->trigger_url_format_error( $data, 'No hostname found in URL.' );
				$valid = false;
			}
		}

		// Verify last_modified format
		if ( ! is_int( $data['last_modified'] ) ) {
			$this->trigger_url_format_error( $data, 'Last modified is not a timestamp.' );
			$valid = false;
		}

		$last_modified = intval( $data['last_modified'] );
		// Before January 1st 2001 WordPress didn't exist.
		if ( $last_modified > 0 && $last_modified < 978307200 ) {
			$this->trigger_url_format_error( $data, 'Last modified is not a valid timestamp.' );
			$valid = false;
		}

		// If the last_modified timestamp is in the future.
		if ( $last_modified > $_SERVER['REQUEST_TIME'] ) {
			$this->trigger_url_format_error( $data, 'Last modified cannot be in the future.' );
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Register the custom URL
	 *
	 * @param array $data Custom URL data to register.
	 */
	public function register_custom_url( $data ) {

		if ( ! $this->is_valid_custom_url_format( $data ) ) {
			return;
		}

		$register = array(
			'source'        => 'custom',
			'URL'           => $data['URL'],
			'identifier'    => $data['identifier'],
			'last_modified' => $data['last_modified']
		);

		$this->indexer->register_url( $register );
	}

	/**
	 * Trigger an error in the custom URL format.
	 *
	 * @param array  $data    The provided data that triggered the error.
	 * @param string $message The message describing the error.
	 */
	protected function trigger_url_format_error( $data, $message ) {
		do_action( 'wp_sitemap_url_registration_error', $data, $message );
	}
}
