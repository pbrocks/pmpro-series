<?php
/**
 * Branch: dev-pb
 */
class PMProSeries {

	/**
	 * [__construct] Class constructor.
	 *
	 * @param integer $id
	 */
	function __construct( $id = null ) {
		if ( ! empty( $id ) ) {
			return $this->getSeriesByID( $id );
		} else {
			return true;
		}
	}

	/**
	 * [getSeriesByID] Populate series data by post id passed.
	 *
	 * @param  integer $id
	 * @return integer
	 */
	function getSeriesByID( $id ) {
		$this->post = get_post( $id );
		if ( ! empty( $this->post->ID ) ) {
			$this->id = $id;
		} else {
			$this->id = false;
		}

		return $this->id;
	}

	/**
	 * [addPost] Add a post to this series.
	 *
	 * @param integer $post_id
	 * @param integer $delay
	 */
	function addPost( $post_id, $delay ) {
		if ( empty( $post_id ) || ! isset( $delay ) ) {
			$this->error = 'Please enter a value for post and delay.';
			return false;
		}

		$post = get_post( $post_id );

		if ( empty( $post->ID ) ) {
			$this->error = 'A post with that id does not exist.';
			return false;
		}

		$this->getPosts();

		if ( empty( $this->posts ) ) {
			$this->posts = array();
		}

		// remove any old post with this id
		if ( $this->hasPost( $post_id ) ) {
			$this->removePost( $post_id );
		}

		// add post
		$temp          = new stdClass();
		$temp->id      = $post_id;
		$temp->delay   = $delay;
		$this->posts[] = $temp;

		// sort
		usort( $this->posts, array( 'PMProSeries', 'sortByDelay' ) );

		// save
		update_post_meta( $this->id, '_series_posts', $this->posts );

		// add series to post
		$post_series = get_post_meta( $post_id, '_post_series', true );
		if ( ! is_array( $post_series ) ) {
			$post_series = array( $this->id );
		} else {
			$post_series[] = $this->id;
		}

		// save
		update_post_meta( $post_id, '_post_series', $post_series );
	}

	/**
	 * [removePost] Remove a post from this series.
	 *
	 * @param integer $post_id
	 * @param integer $delay
	 */
	function removePost( $post_id ) {
		if ( empty( $post_id ) ) {
			return false;
		}

		$this->getPosts();

		if ( empty( $this->posts ) ) {
			return true;
		}

		// remove this post from the series
		foreach ( $this->posts as $i => $post ) {
			if ( $post->id == $post_id ) {
				unset( $this->posts[ $i ] );
				$this->posts = array_values( $this->posts );
				update_post_meta( $this->id, '_series_posts', $this->posts );
				break;  // assume there is only one
			}
		}

		// remove this series from the post
		$post_series = get_post_meta( $post_id, '_post_series', true );
		if ( is_array( $post_series ) && ( $key = array_search( $this->id, $post_series ) ) !== false ) {
			unset( $post_series[ $key ] );
			update_post_meta( $post_id, '_post_series', $post_series );
		}

		return true;
	}

	/**
	 * [getPosts] Get array of all posts in this series.
	 * force = ignore cache and get data from DB.
	 *
	 * @param boolean
	 */
	function getPosts( $force = false ) {
		if ( ! isset( $this->posts ) || $force ) {
			$this->posts = get_post_meta( $this->id, '_series_posts', true );
		}

		return $this->posts;
	}

	/**
	 * [hasPost] Does this series include post with id = post_id.
	 *
	 * @param  [type] $post_id
	 * @return boolean
	 */
	function hasPost( $post_id ) {
		$this->getPosts();

		if ( empty( $this->posts ) ) {
			return false;
		}

		foreach ( $this->posts as $key => $post ) {
			if ( $post->id == $post_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * [getPostKey] Get key of post with id = $post_id.
	 *
	 * @param  [type] $post_id
	 * @return [type]
	 */
	function getPostKey( $post_id ) {
		$this->getPosts();

		if ( empty( $this->posts ) ) {
			return false;
		}

		foreach ( $this->posts as $key => $post ) {
			if ( $post->id == $post_id ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * [getDelayForPost]
	 *
	 * @param  [type] $post_id
	 * @return [type]
	 */
	function getDelayForPost( $post_id ) {
		$key = $this->getPostKey( $post_id );

		if ( $key === false ) {
			return false;
		} else {
			return $this->posts[ $key ]->delay;
		}
	}

	/**
	 * [sortByDelay] Sort posts by delay.
	 *
	 * @param  [type] $a
	 * @param  [type] $b
	 * @return [type]
	 */
	function sortByDelay( $a, $b ) {
		if ( $a->delay == $b->delay ) {
			return 0;
		}
		return ( $a->delay < $b->delay ) ? -1 : 1;
	}

		/**
		 * [sendEmail] Send an email RE new access to post_id to email of user_id.
		 *
		 * @param  [type] $post_ids
		 * @param  [type] $user_id
		 * @return [type]
		 */
	function sendEmail( $post_ids, $user_id ) {
		if ( ! class_exists( 'PMProEmail' ) ) {
			return;
		}

		$email = new PMProEmail();

		$user = get_user_by( 'id', $user_id );

		// build list of posts
		$post_list = "<ul>\n";
		foreach ( $post_ids as $post_id ) {
			$post_list .= '<li><a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a></li>' . "\n";
		}
		$post_list .= "</ul>\n";

		$email->email    = $user->user_email;
		$subject         = sprintf( __( 'New content is available at %s', 'pmpro' ), get_option( 'blogname' ) );
		$email->subject  = apply_filters( 'pmpros_new_content_subject', $subject, $user, $post_ids );
		$email->template = 'new_content';

		// check for custom email template
		if ( file_exists( get_stylesheet_directory() . '/paid-memberships-pro/series/new_content.html' ) ) {
			$template_path = get_stylesheet_directory() . '/paid-memberships-pro/series/new_content.html';
		} elseif ( file_exists( get_template_directory() . '/paid-memberships-pro/series/new_content.html' ) ) {
			$template_path = get_template_directory() . '/paid-memberships-pro/series/new_content.html';
		} else {
			$template_path = plugins_url( 'email/new_content.html', dirname( __FILE__ ) );
		}

		$email->email    = $user->user_email;
		$email->subject  = sprintf( __( 'New content is available at %s', 'pmpro' ), get_option( 'blogname' ) );
		$email->template = 'new_content';

		$email->body .= file_get_contents( dirname( __FILE__ ) . '/email/new_content.html' );

		$email->data = array(
			'name'       => $user->display_name,
			'sitename'   => get_option( 'blogname' ),
			'post_list'  => $post_list,
			'login_link' => wp_login_url(),
		);

		if ( ! empty( $post->post_excerpt ) ) {
			$email->data['excerpt'] = '<p>An excerpt of the post is below.</p><p>' . $post->post_excerpt . '</p>';
		} else {
			$email->data['excerpt'] = '';
		}

		$email->sendEmail();
	}

	/**
	 * [createCPT] Create the Custom Post Type for Series.
	 *
	 * @return [type]
	 */
	static function createCPT() {
		// don't want to do this when deactivating
		global $pmpros_deactivating;
		if ( ! empty( $pmpros_deactivating ) ) {
			return false;
		}

		$labels = apply_filters(
			'pmpros_series_labels',
			array(
				'name'               => __( 'Series' ),
				'singular_name'      => __( 'Series' ),
				'slug'               => 'series',
				'add_new'            => __( 'New Series' ),
				'add_new_item'       => __( 'New Series' ),
				'edit'               => __( 'Edit Series' ),
				'edit_item'          => __( 'Edit Series' ),
				'new_item'           => __( 'Add New' ),
				'view'               => __( 'View This Series' ),
				'view_item'          => __( 'View This Series' ),
				'search_items'       => __( 'Search Series' ),
				'not_found'          => __( 'No Series Found' ),
				'not_found_in_trash' => __( 'No Series Found In Trash' ),
			)
		);

		register_post_type(
			'pmpro_series',
			apply_filters(
				'pmpros_series_registration',
				array(
					'labels'             => $labels,
					'public'             => true,
					'menu_icon'          => 'dashicons-clock',
					'show_ui'            => true,
					'show_in_menu'       => true,
					'show_in_rest'       => true,
					'publicly_queryable' => true,
					'hierarchical'       => true,
					'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'author' ),
					'can_export'         => true,
					'show_in_nav_menus'  => true,
					'rewrite'            => array(
						'slug'       => 'series',
						'with_front' => false,
					),
					'has_archive'        => 'series',
				)
			)
		);
	}

	/**
	 * [checkForMetaBoxes] Meta boxes
	 *
	 * @return [type]
	 */
	static function checkForMetaBoxes() {
		// add meta boxes
		// if ( is_admin() ) {
		// wp_enqueue_style( 'pmpros-select2', plugins_url( 'css/select2.css', dirname( __FILE__ ) ), '', '3.1', 'screen' );
		// wp_enqueue_script( 'pmpros-select2', plugins_url( 'js/select2.js', dirname( __FILE__ ) ), array( 'jquery' ), '3.1' );
		add_action( 'admin_menu', array( 'PMProSeries', 'defineMetaBoxes' ) );
		add_action( 'admin_enqueue_scripts', array( 'PMProSeries', 'pmprors_admin_scripts' ) );
		add_action( 'wp_ajax_post_select_request', array( 'PMProSeries', 'run_pmpro_series_ajax_function' ) );
		// }
	}

	/**
	 * [defineMetaBoxes] Meta boxes
	 *
	 * @return [type]
	 */
	static function defineMetaBoxes() {
		 // PMPro box
		add_meta_box( 'pmpro_page_meta', 'Require Membership', 'pmpro_page_meta', 'pmpro_series', 'side' );

		// series meta box
		add_meta_box( 'pmpro_series_meta', 'Posts in this Series', array( 'PMProSeries', 'seriesMetaBox' ), 'pmpro_series', 'normal' );
	}

	/**
	 * [seriesMetaBox] This is the actual series meta box.
	 *
	 * @return [type]
	 */
	static function seriesMetaBox() {
		global $post;
		$series = new PMProSeries( $post->ID );
		?>
		<div id="pmpros_series_posts">
		<?php $series->getPostListForMetaBox(); ?>
		</div>				
		<?php
	}

	/**
	 * [seriesMetaBox] This function returns a UL with the current posts.
	 *
	 * @return [type]
	 */
	function getPostList( $echo = false ) {
		global $current_user;
		$this->getPosts();
		if ( ! empty( $this->posts ) ) {
			ob_start();
			?>
				
			<ul id="pmpro_series-<?php echo $this->id; ?>" class="pmpro_series_list">
			<?php
				$member_days = pmpro_getMemberDays( $current_user->ID );

			foreach ( $this->posts as $sp ) {
				$days_left = ceil( $sp->delay - $member_days );
				$date      = date( get_option( 'date_format' ), strtotime( "+ $days_left Days", current_time( 'timestamp' ) ) );
				?>
				<li class="pmpro_series_item-li-
				<?php
				if ( max( 0, $member_days ) >= $sp->delay ) {
					?>
					available
					<?php
				} else {
					?>
					unavailable<?php } ?>">
				<?php if ( max( 0, $member_days ) >= $sp->delay ) { ?>
						<span class="pmpro_series_item-title"><a href="<?php echo get_permalink( $sp->id ); ?>"><?php echo get_the_title( $sp->id ); ?></a></span>
						<span class="pmpro_series_item-available"><a class="pmpro_btn pmpro_btn-primary" href="<?php echo get_permalink( $sp->id ); ?>">Available Now</a></span>
					<?php } else { ?>
						<span class="pmpro_series_item-title"><?php echo get_the_title( $sp->id ); ?></span>
						<span class="pmpro_series_item-unavailable">available on <?php echo $date; ?></span>
					<?php } ?>
				</li>
				<?php
			}
			?>
			</ul>
			
			<?php
			$temp_content = ob_get_contents();
			ob_end_clean();

			// filter
			$temp_content = apply_filters( 'pmpro_series_get_post_list', $temp_content, $this );

			if ( $echo ) {
				echo $temp_content;
			}

			return $temp_content;
		}

		return false;
	}

	/**
	 * [getPostListForMetaBox] This code updates the posts and draws the list/form.
	 *
	 * @return [type]
	 */
	function getPostListForMetaBox() {
		global $wpdb;

		// boot out people without permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		if ( isset( $_REQUEST['pmpros_post'] ) ) {
			$pmpros_post = intval( $_REQUEST['pmpros_post'] );
		}
		if ( isset( $_REQUEST['pmpros_delay'] ) ) {
			$delay = intval( $_REQUEST['pmpros_delay'] );
		}
		if ( isset( $_REQUEST['pmpros_remove'] ) ) {
			$remove = intval( $_REQUEST['pmpros_remove'] );
		}

		// adding a post
		if ( ! empty( $pmpros_post ) ) {
			$this->addPost( $pmpros_post, $delay );
		}

		// removing a post
		if ( ! empty( $remove ) ) {
			$this->removePost( $remove );
		}

		// show posts
		$this->getPosts();

		?>
				
			
		<?php if ( ! empty( $this->error ) ) { ?>
			<div class="message error"><p><?php echo $this->error; ?></p></div>
		<?php } ?>
		
		<table id="pmpros_table" class="wp-list-table widefat fixed">
		<thead>
			<th>Order</th>
			<th width="50%">Title</th>
			<th>Delay (# of days)</th>
			<th></th>
			<th></th>
		</thead>
		<tbody>
		<?php
		$count = 1;

		if ( empty( $this->posts ) ) {
			?>
			<?php
		} else {
			foreach ( $this->posts as $post ) {
				?>
				<tr>
					<td><?php echo $count; ?>.</td>
					<td><?php echo get_the_title( $post->id ); ?></td>
					<td><?php echo $post->delay; ?></td>
					<td>
						<a href="javascript:pmpros_editPost('<?php echo $post->id; ?>', '<?php echo $post->delay; ?>'); void(0);">Edit</a>
					</td>
					<td>
						<a href="javascript:pmpros_removePost('<?php echo $post->id; ?>'); void(0);">Remove</a>
					</td>
				</tr>
				<?php
				$count++;
			}
		}
		?>
		</tbody>
		</table>
		
		<div id="postcustomstuff">
			<div id="ajax-return">ajax-return</div>
			<p><strong>Add/Edit Posts:</strong></p>
			<table id="newmeta">
				<thead>
					<tr>
						<th>Post/Page</th>
						<th>Delay (# of days)</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><form id="series-posts">
						<select name="pmpros_post[]" multiple id="pmpros_post">
						<?php
							$pmpros_post_types = apply_filters( 'pmpros_post_types', array( 'post', 'page' ) );
							$allposts          = $wpdb->get_results( "SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_status IN('publish', 'draft') AND post_type IN ('" . implode( "','", $pmpros_post_types ) . "') AND post_title <> '' ORDER BY post_title" );
						foreach ( $allposts as $p ) {
							?>
							<option value="<?php echo $p->ID; ?>"><?php echo esc_textarea( $p->post_title ); ?> (#
							<?php
							echo $p->ID;

							if ( $p->post_status == 'draft' ) {
								echo '-DRAFT';
							}
							?>
								)</option>
							<?php
						}
						?>
						</select>
						</td>
						<td><input id="pmpros_delay" name="pmpros_delay" type="text" value="" size="7" /></td>
						<td><a class="button" id="pmpros_save">Add to Series</a><input type="button" id="pmpro-series-check" value="check-posts" /></form></td>
					</tr>
				</tbody>
			</table>
		</div>		
		<?php
	}

	/**
	 * [pmprors_admin_scripts] Load admin JS files.
	 *
	 * @param  [type] $hook
	 * @return void
	 */
	function pmprors_admin_scripts( $hook ) {
		if ( 'post.php' == $hook && 'pmpro_series' == get_post_type() ) {
			wp_enqueue_style( 'pmprors-admin', plugins_url( 'css/pmpro-series-admin.css', __FILE__ ) );
			// wp_register_script( 'pmprors_pmpro', plugins_url( 'js/pmpro-series.js', __FILE__ ), array( 'jquery' ), time(), true );
			wp_register_script( 'pmpro-series', plugins_url( 'js/pmpro-series-select.js', __FILE__ ), array( 'jquery' ), time(), true );

			$localize = array(
				'series_id'         => $_GET['post'],
				'select_page'       => $_REQUEST['post'],
				'post_select_url'   => admin_url( 'admin.php?page=' ),
				'post_select_nonce' => wp_create_nonce( 'select-nonce' ),
				'save'              => __( 'Save', 'pmproseries' ),
				'saving'            => __( 'Saving...', 'pmproseries' ),
				'saving_error_1'    => __( 'Error saving series post [1]', 'pmproseries' ),
				'saving_error_2'    => __( 'Error saving series post [2]', 'pmproseries' ),
				'remove_error_1'    => __( 'Error removing series post [1]', 'pmproseries' ),
				'remove_error_2'    => __( 'Error removing series post [2]', 'pmproseries' ),
			);

			wp_localize_script( 'pmpro-series', 'pmpro_series_object', $localize );
			wp_enqueue_script( 'pmpro-series' );
		}
	}

	function run_pmpro_series_ajax_function() {
		$stuff       = $_POST;
		$array       = $_POST['posts_to_add'];
		$delay       = $_POST['delay'];
		$post_series = get_post_meta( $_POST['series_id'], '_series_posts', true );

		foreach ( $array as $key => $value ) {
			$object           = new stdClass();
			$object->id       = $value;
			$object->delay    = $delay;
			$this_object[]    = $object;
			$stuff['array'][] = 'Adding Post ' . $value . ' and delay for ' . $_POST['delay'] . ' days';
		}
		// echo '<pre>';
		// print_r( $stuff );
		// echo '<h4>Adding</h4>';
		// print_r( $this_object );
		if ( ! is_array( $this_object ) ) {
			array_push( $post_series, $this_object );
		} else {
			foreach ( $this_object as $key => $one_object ) {
				array_push( $post_series, $one_object );
			}
		}
		update_post_meta( $_POST['series_id'], '_series_posts', $post_series );
		// $series = new PMProSeries( $_POST['series_id'] );
		// $series->getPostListForMetaBox();
		// echo json_encode( $stuff );
		// echo '<h4>post_series</h4>';
		// print_r( $post_series );
		// echo '</pre>';
		exit();
	}


}
