<?php
namespace EventManager\Venues;

use EventManager\Refinement\AbstractRefinement;
use EventManager\DataTables\Column;

/**
 * Class to manage refinement for the education CPT.
 */
class VenuesRefinement extends AbstractRefinement {

	protected $data_source = 'venues';

	public $facets;

	/**
	 * Set the facets.
	 */
	protected function set_facets() {
		$this->facets = null;
	}

	/**
	 * Setup data table.
	 */
	protected function set_data_table() {
		$this->data_table = new VenuesDataTable();

		$this->data_table->add_column( new Column( 'Title', 'title', true ) );
		$this->data_table->add_column( new Column( 'Category', mayo_core()->education->education_type_taxonomy->get_name() ) );
		$this->data_table->add_column( new Column( 'Location', 'location' ) );
		$this->data_table->add_column( new Column( 'Bookmark', 'bookmark' ) );

		$this->data_table->set_facets( $this->facets );
	}

	/**
	 * Helper function to render all facets.
	 */
	public function render_facets() {
		$this->facets->output_refinement_facets();
	}

	/**
	 * Helper function to render facets by slug.
	 *
	 * @param string 	$slug 		The slug of the item to render.
	 * @param bool 		$show_label True/False to show the label of the facets.
	 */
	public function render_facets_by_slug( $slug, $class = '', $show_label = true ) {
		$this->facets->output_refinement_facets( $slug, $class, $show_label );
	}
}
