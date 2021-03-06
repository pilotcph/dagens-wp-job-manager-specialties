<?php

/**
 * Simple list
 */
class Astoundify_Job_Manager_specialties_Widget_List extends Jobify_Widget {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->widget_cssclass    = 'ajmr_widget_specialties';
		$this->widget_description = __( 'Display a list of job Specialties.', 'wp-job-manager-specialties' );
		$this->widget_id          = 'ajmr_widget_specialties';
		$this->widget_name        = __( 'Job Specialties', 'wp-job-manager-specialties' );
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => 'Job Specialties',
				'label' => __( 'Title:', 'wp-job-manager-specialties' )
			)
		);
		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) )
			return;

		ob_start();

		extract( $args );

		echo $before_widget;

		if ( $instance[ 'title' ] ) echo $before_title . $instance[ 'title' ] . $after_title;

		wp_list_categories( array(
			'title_li'   => '',
			'taxonomy'   => 'job_listing_specialty',
			'hide_empty' => 0
		) );

		echo $after_widget;

		$content = ob_get_clean();

		echo $content;

		$this->cache_widget( $args, $content );
	}
}