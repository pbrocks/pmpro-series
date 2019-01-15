<?php
/**
 * Add a page to the dashboard menu.
 *
 * @since 1.0.0
 *
 * @return array
 */
add_action( 'admin_menu', 'pmpseries_dashboard' );
function pmpseries_dashboard() {
	$slug  = preg_replace( '/_+/', '-', __FUNCTION__ );
	$label = ucwords( preg_replace( '/_+/', ' ', __FUNCTION__ ) );
	add_dashboard_page( __( $label, 'pmpseries-dashboard-menu' ), __( $label, 'pmpseries-dashboard-menu' ), 'manage_options', $slug . '.php', 'pmpseries_dashboard_page' );
}


/**
 * Debug Information
 *
 * @since 1.0.0
 *
 * @param bool $html Optional. Return as HTML or not
 *
 * @return string
 */
function pmpseries_dashboard_page() {
	echo '<div class="wrap">';
	echo '<h2>' . ucwords( preg_replace( '/_+/', ' ', __FUNCTION__ ) ) . '</h2>';
	$screen = get_current_screen();
	echo '<h4 style="color:rgba(250,128,114,.7);">Current Screen is <span style="color:rgba(250,128,114,1);">' . $screen->id . '</span></h4>';
	echo 'Your WordPress version is ' . get_bloginfo( 'version' );

	echo '<div class="add-to-series-dash" style="background:aliceblue;padding:1rem 2rem;">';
	do_action( 'add_to_series_dash' );
	echo '</div>' . plugins_url( 'css/pmpro-series-admin.css', __FILE__ );

	$my_theme = wp_get_theme();
	echo '<h4>Theme is ' . sprintf(
		__( '%1$s and is version %2$s', 'text-domain' ),
		$my_theme->get( 'Name' ),
		$my_theme->get( 'Version' )
	) . '</h4>';
	echo '<h4>Templates found in ' . get_template_directory() . '</h4>';
	echo '<h4>Stylesheet found in ' . get_stylesheet_directory() . '</h4>';
	echo '</div>';
}

add_action( 'add_to_series_dash', 'function_adding_to_series_dashboard_page' );

/**
 * Debug Information
 *
 * @since 1.0.0
 *
 * @param bool $html Optional. Return as HTML or not
 *
 * @return string
 */
function function_adding_to_series_dashboard_page() {
	echo '<h2>' . ucwords( preg_replace( '/_+/', ' ', __FUNCTION__ ) ) . '</h2>';
	echo '<h3>Add more info here</h3>';
	$post_id       = 5250;
	$delay         = 245;
	$object        = new stdClass();
	$object->id    = $post_id;
	$object->delay = $delay;
	$array         = [];
	echo '<pre>';
	print_r( $object );
	echo '</pre>';
	$post_series = get_post_meta( 17199, '_series_posts', true );
	echo '<pre>17199 _series_posts ';
	print_r( $post_series );
	echo '</pre>';

	$array_pushed = array_push( $post_series, $object );
	echo '<pre>array_pushed ';
	print_r( $post_series );
	echo '</pre>';
	echo '
<pre>
	// add post
	$temp          = new stdClass();
	$temp->id      = $post_id;
	$temp->delay   = $delay;
	$this->posts[] = $temp;

	// sort
	usort( $this->posts, array( "PMProSeries", "sortByDelay" ) );

	// save
	update_post_meta( $this->id, "_series_posts", $this->posts );

	// add series to post
	$post_series = get_post_meta( $post_id, "_post_series", true );
	if ( ! is_array( $post_series ) ) {
		$post_series = array( $this->id );
	} else {
		$post_series[] = $this->id;
	}

	// save
	update_post_meta( $post_id, "_post_series", $post_series );
</pre>
	';
}
