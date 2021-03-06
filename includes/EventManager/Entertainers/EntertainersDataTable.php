<?php

namespace EventManager\Entertainers;

use EventManager\DataTables\AbstractWPQueryDataTable;
use EventManager\DataTables\Column;

/**
 * Class for the education page data table.
 */
class EntertainersDataTable extends AbstractWPQueryDataTable {

	/**
	 * Get the table rows.
	 *
	 * @return array
	 */
	public function get_rows() {
		$search_term = get_query_var( 's' );

		if ( ! empty( $search_term ) ) {
			$this->query_args['s'] = $search_term;
		}
		return parent::get_rows();
	}

	/**
	 * Render a entertainer type row item.
	 *
	 * @param  \WP_Post $row The row data.
	 * @param  Column $column The column the row item belongs to.
	 */
	public function render_type_item( \WP_Post $row, Column $column ) {
		$this->render_taxonomy_item( $row, $column, event_manager_core()->entertainer->entertainer_type_taxonomy->get_name());
	}

	/**
	 * Get default query args.
	 *
	 * @return array The default query args.
	 */
	public function get_default_query_args() {
		$args = parent::get_default_query_args();
		$args['post_type'] = event_manager_core()->entertainer->get_cpts();
		return $args;
	}



}
