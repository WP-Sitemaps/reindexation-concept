<?php

class WP_Sitemap_Indexer_Post_Type {
	/** @var WP_Sitemap_Indexer */
	protected $indexer;

	/**
	 * WP_Sitemap_Indexer_Post_Type constructor.
	 *
	 * @param WP_Sitemap_Indexer $indexer
	 */
	public function __construct( WP_Sitemap_Indexer $indexer ) {
		$this->indexer = $indexer;
	}

	/**
	 * Index public post type posts
	 *
	 * @return bool Finished or still need to index objects.
	 */
	public function run() {
		$finished = true;

		foreach ( $this->get_indexable_post_types() as $post_type ) {
			$finished = $this->index( $post_type );

			if ( ! $finished ) {
				break;
			}

			$this->finish( $post_type );
		}

		return $finished;
	}

	/**
	 * Index a specific post type
	 *
	 * @param string $post_type Post type to index.
	 *
	 * @return bool True if all the posts in the post type have been parsed.
	 */
	protected function index( $post_type ) {

		$finished = false;

		// Bulk fetch any "public" non-password-protected items.
		$arguments = array(
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'has_password'   => false,
			'fields'         => 'ids',
		);

		$arguments = apply_filters( 'wp_sitemap_index_post_type_args', $arguments );

		// Get the last used offset.
		$offset = get_option( 'wp_sitemap_index-' . $post_type, 0 );

		// Force offset in arguments.
		$arguments['offset']    = $offset;
		$arguments['post_type'] = $post_type;

		// Get an indication of the number of posts to process.
		$number_of_posts                   = $arguments;
		$number_of_posts['posts_per_page'] = 1;
		$number_of_posts_query             = new WP_Query( $number_of_posts );
		$total                             = $number_of_posts_query->found_posts;

		$this->indexer->set_index_indicator( 'post_type', array(
			'post_type' => $post_type,
			'offset'    => $offset,
			'total'     => $total
		) );

		do {
			if ( $this->indexer->reached_execution_limit() ) {
				break;
			}

			// Fetch the posts.
			$posts = get_posts( $arguments );

			// If we have no posts to process, we are done!
			if ( empty( $posts ) ) {
				$finished = true;
				break;
			}

			// Process each post by ID.
			foreach ( $posts as $post_id ) {
				// Allow excluding of posts by ID.
				if ( true !== apply_filters( 'wp_sitemap_index-' . $post_type . '-' . $post_id, true ) ) {
					continue;
				}

				// Post is not excluded, fetch data.
				$post = get_post( $post_id );

				$register = array(
					'source'        => 'post_type',
					'identifier'    => $post_id,
					'URL'           => get_permalink( $post_id ),
					'last_modified' => $post->post_modified_gmt
				);

				$this->indexer->register_url( $register );
			}

			// If all posts were fetched at once, we are done already.
			$posts_per_page = intval( $arguments['posts_per_page'] );
			if ( $posts_per_page < 0 ) {
				$finished = true;
				break;
			}

			// Bump offset and run again.
			$offset += $posts_per_page;
			update_option( 'wp_sitemap_index-' . $post_type, $offset, $autoload = false );

			$arguments['offset'] = $offset;

		} while ( true );

		return $finished;
	}

	/**
	 * Clean up post type indexation options.
	 *
	 * @param string $post_type Post type that just finished indexing.
	 */
	protected function finish( $post_type ) {
		delete_option( 'wp_sitemap_index-' . $post_type );

		$this->indexer->clear_index_indicator();
	}

	/**
	 * Get a list of indexable post types
	 *
	 * @uses $this->remove_excluded_post_type()
	 *
	 * @return array
	 */
	public function get_indexable_post_types() {
		// Should/could this be cached?
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = array_filter( $post_types, array( $this, 'remove_excluded_post_type' ) );

		return $post_types;
	}

	/**
	 * Filter out non-indexable post types.
	 *
	 * This function is used in an array_filter call.
	 *
	 * @used-by $this->get_indexable_post_types()
	 *
	 * @param string $post_type Post type to check for exclusion.
	 *
	 * @return bool True when the post type should not be indexed.
	 */
	protected function remove_excluded_post_type( $post_type ) {
		return ( true === apply_filters( 'wp_sitemap_index-' . $post_type, true ) );
	}
}
