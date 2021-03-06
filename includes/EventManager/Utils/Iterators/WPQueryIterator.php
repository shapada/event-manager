<?php
namespace EventManager\Utils\Iterators;

class WPQueryIterator implements \Iterator {

	/**
	 * The current index.
	 *
	 * @var integer
	 */
	protected $current_index = 0;

	/**
	 * The query object.
	 *
	 * @var null|\WP_Query
	 */
	protected $query = null;

	/**
	 * The arguments passed to the WP_Query.
	 *
	 * @var array
	 */
	protected $query_args = array();

	/**
	 * The number of iterations a loop has gone through.
	 *
	 * @var integer
	 */
	public $loop_count = 0;

	public function __construct( $args = array() ) {
		if ( ! empty( $args ) ) {
			$default_args = $this->get_default_query_args();
			$args         = array_merge( $default_args, $args );

			$this->query_args = $args;
			$this->run();
		}
	}

	/**
	 * Get the default query args.
	 *
	 * @return array
	 */
	public function get_default_query_args() {
		return array(
			'post_type'      => 'post',
			'posts_per_page' => get_option( 'posts_per_page' ),
			'no_found_rows'  => true,
		);
	}

	/**
	 * Run the query for this iterator.
	 */
	public function run() {
		$this->query = new \WP_Query( $this->query_args );
		$this->rewind();
	}

	/**
	 * Rewind the Iterator to the first item.
	 */
	public function rewind() {
		$this->current_index = 0;
		$this->current();
	}

	/**
	 * Get the current item.
	 *
	 * @return \WP_Post
	 */
	public function current() {
		return $this->get_current_item();
	}

	/**
	 * Get the current index.
	 *
	 * @return int
	 */
	public function key() {
		return $this->current_index;
	}

	/**
	 * Advance to the next item in the query.
	 */
	public function next() {
		++$this->current_index;

		// If we've reached the last post on the current page of results, advance to the next page.
		if (
			! $this->is_valid_post( $this->get_current_item() ) &&
			$this->should_paginate()
		) {
			$this->next_page();
		}
	}

	/**
	 * Advance to th enext page of results.
	 */
	public function next_page() {
		$current_page = isset( $this->query_args['paged'] ) ? $this->query_args['paged'] : 1;
		$this->query_args['paged'] = intval( $current_page ) + 1;
		$this->run();
	}

	/**
	 * Determine if an item is valid for this iterator.
	 *
	 * @return boolean
	 */
	public function valid() {
		$is_valid = true;
		$post     = $this->get_current_item();

		if ( ! $this->is_valid_post( $post ) ) {
			$is_valid = false;
		}

		if ( false === $is_valid ) {
			// Reset the global post data in case it's been altered.
			wp_reset_postdata();
		}

		return $is_valid;
	}

	/**
	 * Get the current item.
	 *
	 * @return \WP_Post
	 */
	public function get_current_item() {
		return $this->get_item_at_index( $this->key() );
	}

	/**
	 * Get an item at a specific index.
	 *
	 * @param  string|int $index The index
	 * @return \WP_Post The item at the index, if it exists
	 */
	public function get_item_at_index( $index ) {
		if ( ! is_a( $this->query, 'WP_Query' ) ) {
			return;
		}

		if ( ! isset( $this->query->posts[ $index ] ) ) {
			return;
		}

		return $this->query->posts[ $index ];
	}

	/**
	 * Get all the items returned for a query.
	 *
	 * @return array
	 */
	public function get_items() {
		if ( ! is_a( $this->query, 'WP_Query' ) ) {
			return array();
		}

		return $this->query->posts;
	}

	/**
	 * Determine if an item is valid post object.
	 *
	 * @param  mixed $item The item
	 * @return boolean
	 */
	public function is_valid_post( $item ) {
		if ( ! is_a( $item, 'WP_Post' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Proxy method calls to the query object.
	 *
	 * @param  string $method The method name
	 * @param  array $arguments The method argumentes
	 * @return mixed The result of the proxied method call.
	 */
	public function __call( $method, $arguments = array() ) {
		$signature = array( $this->query, $method );

		if ( ! method_exists( $this->query, $method ) ||
			 ! is_callable( $signature )
		) {
			return;
		}

		return call_user_func_array(
			array( $this->query, $method ),
			$arguments
		);
	}

	/**
	 * Proxy property get requests to the query.
	 *
	 * @param  string $property The property name
	 * @return mixed The result of the proxied property request.
	 */
	public function __get( $property ) {
		if ( ! isset( $this->query->$property ) ) {
			return;
		}

		return $this->query->$property;
	}

	/**
	 * Determine if the query should loop through all pages of results.
	 *
	 * @return boolean
	 */
	public function should_paginate() {
		if ( empty( $this->query_args['loop'] ) ) {
			return false;
		}

		return filter_var( $this->query_args['loop'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Execute a callback over each item as well as set up the global post data.
	 *
	 * @param  string|array $callback The callback method
	 * @return \WPQueryIterator
	 */
	public function loop( $callback ) {
		global $post;

		$query = $this->get_query();
		if ( ! is_a( $query, 'WP_Query' ) ) {
			return $this;
		}

		if ( ! is_callable( $callback ) ) {
			return $this;
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			call_user_func_array( $callback, array( $post ) );
		}

		wp_reset_postdata();

		return $this;
	}

	/**
	 * Execute a callback over each item.
	 *
	 * @param  string|array $callback The callback method
	 * @return \WPQueryIterator
	 */
	public function each( $callback ) {
		if ( ! is_callable( $callback ) ) {
			return $this;
		}

		$this->reset_loop_count();
		foreach ( $this as $key => $item ) {
			$this->increment_loop_count();
			call_user_func_array( $callback, array( $item, $key ) );
		}

		return $this;
	}

	/**
	 * Execute a callback over each item and return an array of the results.
	 *
	 * @param  string|array $callback The callback method
	 * @return array
	 */
	public function map( $callback ) {
		if ( ! is_callable( $callback ) ) {
			return array();
		}

		$result = array();

		$this->reset_loop_count();
		foreach ( $this as $key => $item ) {
			$this->increment_loop_count();
			$result[] = call_user_func_array( $callback, array( $item, $key ) );
		}

		return $result;
	}

	/**
	 * Reset the loop count.
	 */
	public function reset_loop_count() {
		$this->loop_count = 0;
	}

	/**
	 * Increment the loop count by one.
	 */
	public function increment_loop_count() {
		++$this->loop_count;
	}

	public function get_query() {
		return $this->query;
	}
}
