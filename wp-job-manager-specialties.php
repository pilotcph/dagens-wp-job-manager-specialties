<?php
/**
 * Plugin Name: WP Job Manager - Predefined specialties
 * Plugin URI:  https://wordpress.org/plugins/wp-job-manager-specialties/
 * Description: Create predefined specialties/specialties that job submissions can associate themselves with.
 * Author:      Astoundify, forked: Pilot & BAM
 * Author URI:  http://astoundify.com
 * Version:     1.10.0
 * Text Domain: wp-job-manager-specialties
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Astoundify_Job_Manager_specialties {

    /**
     * @var $instance
     */
    private static $instance;

    /**
     * Make sure only one instance is only running.
     */
    public static function instance() {
        if ( ! isset ( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Start things up.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->file         = __FILE__;
        $this->basename     = plugin_basename( $this->file );
        $this->plugin_dir   = plugin_dir_path( $this->file );
        $this->plugin_url   = plugin_dir_url ( $this->file );
        $this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );
        $this->domain       = 'wp-job-manager-specialties';

        $files = array(
            'includes/class-taxonomy.php',
            'includes/class-template.php',
            'includes/class-widgets.php'
        );

        foreach ( $files as $file ) {
            include_once( $this->plugin_dir . '/' . $file );
        }

        $this->taxonomy = new Astoundify_Job_Manager_specialties_Taxonomy;
        $this->template = new Astoundify_Job_Manager_specialties_Template;

        $this->setup_actions();
    }

    /**
     * Setup the default hooks and actions
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function setup_actions() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        add_filter( 'job_manager_settings', array( $this, 'job_manager_settings' ) );

        add_filter( 'job_manager_output_jobs_defaults', array( $this, 'job_manager_output_jobs_defaults' ) );
        add_filter( 'job_manager_get_listings', array( $this, 'job_manager_get_listings' ), 10, 2 );
        add_filter( 'job_manager_get_listings_args', array( $this, 'job_manager_get_listings_args' ) );

        add_filter( 'job_feed_args', array( $this, 'job_feed_args' ) );
    }

    /**
     * Add settings fields to select the appropriate form for each listing type.
     *
     * @since WP Job Manager - Predefiend specialties 1.4.1
     *
     * @return void
     */
    public function job_manager_settings($settings) {
        $settings[ 'job_listings' ][1][] = array(
            'name'     => 'job_manager_specialties_filter',
            'std'      => '0',
            'label'    => __( 'Job Specialties', 'wp-job-manager-specialties' ),
            'cb_label' => __( 'Filter by specialty', 'wp-job-manager-specialties' ),
            'desc'     => __( 'Use a dropdown instead of a text input.', 'wp-job-manager-specialties' ),
            'type'     => 'checkbox'
        );

        return $settings;
    }

    /**
     * Modify the default shortcode attributes for displaying listings.
     *
     * If we are on a listing specialty term archive set the selected_specialty so
     * we can preselect the dropdown value. This is needed when filtering by specialty.
     */
    public function job_manager_output_jobs_defaults( $defaults ) {
        $defaults[ 'selected_specialty' ] = '';

        if ( is_tax( 'job_listing_specialty' ) ) {
            $type = get_queried_object();

            if ( ! $type ) {
                return $defaults;
            }

            $defaults[ 'show_categories' ] = true;
            $defaults[ 'selected_specialty' ] = $type->term_id;
        }

        return $defaults;
    }

    public function job_manager_get_listings( $query_args, $args ) {
        $params = array();

        if ( isset( $_REQUEST[ 'form_data' ] ) ) {

            parse_str( $_REQUEST[ 'form_data' ], $params );

            if ( isset( $params[ 'search_specialty' ] ) && 0 != $params[ 'search_specialty' ] ) {
                $specialty = $params[ 'search_specialty' ];

                if ( is_int( $specialty ) ) {
                    $specialty = array( $specialty );
                }

                $query_args[ 'tax_query' ][] = array(
                    'taxonomy' => 'job_listing_specialty',
                    'field'    => 'id',
                    'terms'    => $specialty,
                    'operator' => 'IN'
                );

                add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );
                add_filter( 'job_manager_get_listings_custom_filter_text', array( $this, 'custom_filter_text' ) );
                add_filter( 'job_manager_get_listings_custom_filter_rss_args', array( $this, 'custom_filter_rss' ) );
            }

        } elseif ( isset( $_GET[ 'selected_specialty' ] ) ) {

            $specialty = $_GET[ 'selected_specialty' ];

            if ( is_int( $specialty ) ) {
                $specialty = array( $specialty );
            }

            $query_args[ 'tax_query' ][] = array(
                'taxonomy' => 'job_listing_specialty',
                'field'    => 'id',
                'terms'    => $specialty,
                'operator' => 'IN'
            );

        } elseif( isset( $args['search_specialty'] ) ) { // WPJM Alerts support
            $specialty = $args[ 'search_specialty' ];

			if ( is_array( $specialty ) && empty( $specialty ) ) {
				return $query_args;
			}

            $query_args[ 'tax_query' ][] = array(
                'taxonomy' => 'job_listing_specialty',
                'field'    => 'id',
                'terms'    => $specialty,
                'operator' => 'IN'
            );

        }

        return $query_args;
    }

    /**
     * Filter the AJAX request to set the search location to null if a specialty
     * is being passed as well.
     */
    public function job_manager_get_listings_args( $args ) {
        $params = array();

        if ( isset( $_REQUEST[ 'form_data' ] ) ) {

            parse_str( $_REQUEST[ 'form_data' ], $params );

            if ( isset( $params[ 'search_specialty' ] ) && 0 != $params[ 'search_specialty' ] ) {
                $args[ 'search_specialty' ] = null;
            }

        }

        return $args;
    }

    /**
     * Filter the AJAX to update the "showing" text.
     */
    public function custom_filter_text( $text ) {
        $params = array();

        parse_str( $_REQUEST[ 'form_data' ], $params );

        $term = get_term( $params[ 'search_specialty' ], 'job_listing_specialty' );

        $text .= sprintf( ' ' .  __( 'in %s', 'wp-job-manager-specialties' ) . ' ', $term->name );

        return $text;
    }

    /**
     * Filter the AJAX request to update the RSS feed URL.
     */
    public function custom_filter_rss( $args ) {
        $params = array();

        parse_str( $_REQUEST[ 'form_data' ], $params );

        $args[ 'search_specialty' ] = $params[ 'search_specialty' ];

        return $args;
    }

    public function job_feed_args( $query_args ) {
        $specialty = isset( $_GET[ 'search_specialty' ] ) ? $_GET[ 'search_specialty' ] : false;

        if ( ! $specialty ) {
            return $query_args;
        }

        $specialty = esc_attr( $specialty );

        if ( is_int( $specialty ) ) {
            $specialty = array( absint( $specialty ) );
        }

        $query_args[ 'tax_query' ][] = array(
            'taxonomy' => 'job_listing_specialty',
            'field'    => 'id',
            'terms'    => $specialty,
            'operator' => 'IN'
        );

        return $query_args;
    }

    /**
     * Loads the plugin language files
     */
    public function load_textdomain() {
        $locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-specialties' );
        load_textdomain( 'wp-job-manager-specialties', WP_LANG_DIR . "/wp-job-manager-specialties/wp-job-manager-specialties-$locale.mo" );
        load_plugin_textdomain( 'wp-job-manager-specialties', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
}

/**
 * Start things up.
 *
 * Use this function instead of a global.
 *
 * $ajmr = ajmr();
 *
 * @since 1.0.0
 */
function wp_job_manager_specialties() {
    return Astoundify_Job_Manager_specialties::instance();
}

wp_job_manager_specialties();
