<?php

/**
 * Basic class that contains common functions,
 * such as:
 * - installation / deinstallation
 * - meta & options management,
 * - adding pages to menu
 * etc
 */
class Ddb_Plugin extends Ddb_Core {
	
	const CHECK_RESULT_OK = 'ok';
  
  protected $developers = array();
	
	public $plugin_root = '';
	public $cron_generation = false;
    
  public function __construct( $plugin_root ) {

		$this->plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array( $this, 'initialize'), 10 );
	  
    if ( is_admin() ) {
      add_action('admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts') );
      add_action( 'add_meta_boxes', array( $this, 'register_draft_licenses_metabox' ) );
      add_action( 'save_post_product', array( $this, 'handle_draft_licenses_approval' ), 20, 3 );
      add_action( 'wp_ajax_ddb_approve_pending_product', array( $this, 'ajax_approve_pending_product' ) );
      add_action( 'wp_ajax_ddb_approve_and_publish_product', array( $this, 'ajax_approve_and_publish_product' ) );
      add_action( 'wp_ajax_ddb_move_product_to_draft', array( $this, 'ajax_move_product_to_draft' ) );
    }
    
    add_action( 'wp_enqueue_scripts', array($this, 'register_frontend_scripts_when_shortcode_present') );
		add_action( 'admin_menu', array( $this, 'add_page_to_menu' ) );
    
    add_role( self::DEV_ROLE_NAME, 'Product Developer', array(
      'read'              => true,
      'create_posts'      => false,
      'edit_posts'        => false,
      'edit_others_posts' => false,
      'publish_posts'     => false,
      'manage_categories' => false,
    ));
   
		// modify_email_notification_for_new_developers() was removed -- check commit history for this code which sends the email about reports.
    // add_filter( 'wp_new_user_notification_email', array( 'Ddb_Plugin', 'modify_email_notification_for_new_developers' ), 10, 3 );
		
    add_filter( 'wp_new_user_notification_email', array( 'Ddb_Plugin', 'attach_developer_taxonomy_to_new_user' ), 10, 3 );
    
    add_filter( 'the_title', array( 'Ddb_Plugin', 'custom_title_for_developer_dashboard' ), 10, 2 );

		add_filter( 'map_meta_cap', array( 'Ddb_Plugin', 'map_meta_cap_draft_product_preview_for_developers' ), 10, 4 );
		
		// set up cron handling and events scheduling
		$this->cron_generation = new Ddb_Cron_Generator();
	}

	public function initialize() {
		self::load_options();
    
    $this->register_shortcodes();
	}

	/* Add options on plugin activate */
	public static function install() {
		self::install_plugin_options();
	}
  
	public static function install_plugin_options() {
		add_option( 'ddb_options', self::$default_option_values );
	}
  
  
	public function register_shortcodes() {		
    add_shortcode( 'developer_dashboard', array( 'Ddb_Plugin', 'render_developer_dashboard' ) );
    add_shortcode( 'display_content_for_developers_only', array( 'Ddb_Frontend', 'display_developer_content' ) );
    add_shortcode( 'display_content_for_affiliates_only', array( 'Ddb_Frontend', 'display_affiliate_content' ) );
	}

	/**
	 * Handler for "developer_dashboard" shortcode: ?section=orders|products|product-wizard|product-licenses.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @param string       $content Enclosed content (unused).
	 * @param string       $tag Shortcode name.
	 * @return string HTML.
	 */
	public static function render_developer_dashboard( $atts = array(), $content = '', $tag = '' ) {
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}

		$section_raw = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $allowed_sections = array( 'orders', 'products', 'create-product', 'product-wizard', 'edit-product', 'product-licenses' );

		$section     = in_array( $section_raw, $allowed_sections, true ) ? $section_raw : 'orders';

		$url_orders   = esc_url( remove_query_arg( 'product_id', add_query_arg( 'section', 'orders' ) ) );
		$url_products = esc_url( remove_query_arg( 'product_id', add_query_arg( 'section', 'products' ) ) );

		if ( in_array( $section, array( 'create-product', 'product-wizard', 'edit-product', 'product-licenses' ), true ) ) {
			Ddb_Frontend::do_frontend_action();
		}

		if ( 'edit-product' === $section && Ddb_Frontend::product_was_deleted_this_request() ) {
			$section = 'products';
		}

		if ( 'products' === $section ) {
			$panel_html = Ddb_Frontend::render_developer_products_dashboard( $atts );
		}
    elseif ( 'create-product' === $section ) {
			if ( Ddb_Frontend::draft_product_created_this_request() ) {
				$panel_html = '<div id="developer-dashboard" class="ddb-developer-products ddb-developer-create-product ddb-product-wizard">' . Ddb_Frontend::get_product_form_notice_html() . '</div>';
			} else {
				$panel_html = Ddb_Product_Wizard::render_developer_product_wizard();
			}
    }
    elseif ( 'product-wizard' === $section ) {
			if ( Ddb_Frontend::draft_product_created_this_request() ) {
				$panel_html = '<div id="developer-dashboard" class="ddb-developer-products ddb-developer-create-product ddb-product-wizard">' . Ddb_Frontend::get_product_form_notice_html() . '</div>';
			} else {
				$product_id = absint( filter_input( INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
				$panel_html = Ddb_Product_Wizard::render_developer_product_wizard( $product_id );
			}
		}
    elseif ( 'edit-product' === $section ) {
      $product_id = absint( filter_input( INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
			$panel_html = Ddb_Product_Wizard::render_developer_product_wizard( $product_id );
		}
    elseif ( 'product-licenses' === $section ) {
      $product_id = absint( filter_input( INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
			$panel_html = Ddb_Product_Form::render_developer_product_licenses( $product_id );
		}
    else {
			$panel_html = Ddb_Frontend::render_developer_orders_dashboard( $atts );
		}

		$products_tab_active = in_array( $section, array( 'products', 'create-product', 'product-wizard', 'edit-product', 'product-licenses' ), true );

		ob_start();
		?>
		<div class="ddb-developer-dashboard">
			<nav class="ddb-tab-nav" aria-label="<?php esc_attr_e( 'Developer dashboard sections', DDB_TEXT_DOMAIN ); ?>">
				<a class="ddb-tab-link<?php echo 'orders' === $section ? ' is-active' : ''; ?>" href="<?php echo $url_orders; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"<?php echo 'orders' === $section ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Your Orders', DDB_TEXT_DOMAIN ); ?></a>
				<a class="ddb-tab-link<?php echo $products_tab_active ? ' is-active' : ''; ?>" href="<?php echo $url_products; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"<?php echo $products_tab_active ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Your Products', DDB_TEXT_DOMAIN ); ?></a>
			</nav>

			<div>
        <?php echo $panel_html; ?>
      </div>
			
		</div>
		<?php
		return (string) ob_get_clean();
	}
  
  public function register_admin_styles_and_scripts() {
    $file_src = plugins_url( 'css/ddb-admin.css', $this->plugin_root );
    $style_path = plugin_dir_path( $this->plugin_root ) . 'css/ddb-admin.css';
    $style_ver  = file_exists( $style_path ) ? (string) filemtime( $style_path ) : DDB_VERSION;
    wp_enqueue_style( 'ddb-admin', $file_src, array(), $style_ver );
    
    $script_path = plugin_dir_path( $this->plugin_root ) . 'js/ddb-admin.js';
    $script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : DDB_VERSION;
    wp_enqueue_script( 'ddb-admin-js', plugins_url('/js/ddb-admin.js', $this->plugin_root), array( 'jquery' ), $script_ver, true );
    wp_localize_script( 'ddb-admin-js', 'scs_settings', array(
      'ajax_url'               => admin_url( 'admin-ajax.php' ),
      'approve_product_nonce' => wp_create_nonce( 'ddb_approve_pending_product' ),
      'approve_publish_nonce' => wp_create_nonce( 'ddb_approve_and_publish_product' ),
      'move_to_draft_nonce'   => wp_create_nonce( 'ddb_move_product_to_draft' ),
    ) );
  }
  
  public function register_frontend_scripts_when_shortcode_present() {
    global $post;
    
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'developer_dashboard') ) {
      wp_enqueue_editor();

      $file_src = plugins_url( 'css/ddb-front-2026-04-25.css', $this->plugin_root );
      wp_enqueue_style( 'ddb-front', $file_src, array(), DDB_VERSION );
    }
  }

	/**
	 * Register a metabox on WooCommerce product edit screen.
	 *
	 * Shows draft-only license rows from wc_product_licences table.
	 */
	public function register_draft_licenses_metabox() {
		add_meta_box(
			'ddb-draft-licenses',
			__( 'Draft License Code(s)', DDB_TEXT_DOMAIN ),
			array( $this, 'render_draft_licenses_metabox' ),
			'product',
			'advanced'
		);
	}

	/**
	 * Render product metabox with draft licenses only.
	 *
	 * @param \WP_Post $post Product post object.
	 * @return void
	 */
	public function render_draft_licenses_metabox( $post ) {
		$product_id = isset( $post->ID ) ? (int) $post->ID : 0;
		$licenses   = Ddb_License_Manager::get_licenses( $product_id, 'draft' );
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( '#Number', DDB_TEXT_DOMAIN ); ?></th>
					<th><?php esc_html_e( 'License Code/License Number', DDB_TEXT_DOMAIN ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! empty( $licenses ) ) : ?>
				<?php foreach ( $licenses as $index => $license ) : ?>
				<tr>
					<td><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
					<td><?php echo esc_html( (string) $license->licence_code ); ?></td>
				</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="2"><?php esc_html_e( 'No draft licenses available for this product.', DDB_TEXT_DOMAIN ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<p style="margin-top:12px;">
			<?php wp_nonce_field( 'ddb_approve_draft_licenses_' . $product_id, 'ddb_approve_draft_licenses_nonce' ); ?>
			<button type="submit" class="button button-primary" name="ddb_approve_draft_licenses" value="1">
				<?php esc_html_e( 'Approve these license keys', DDB_TEXT_DOMAIN ); ?>
			</button>
		</p>
		<?php
	}

	/**
	 * Approve draft licenses for a product when requested from metabox.
	 *
	 * @param int      $post_id Product post ID.
	 * @param \WP_Post $post    Product post object.
	 * @param bool     $update  Whether this is an existing post update.
	 * @return void
	 */
	public function handle_draft_licenses_approval( $post_id, $post, $update ) {
		if ( empty( $_POST['ddb_approve_draft_licenses'] ) ) {
			return;
		}

		if ( ! isset( $_POST['ddb_approve_draft_licenses_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['ddb_approve_draft_licenses_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'ddb_approve_draft_licenses_' . $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		global $wpdb;
		$wpdb->update(
			Ddb_License_Manager::table_name(),
			array( 'licence_status' => 'available' ),
			array(
				'product_id'     => (int) $post_id,
				'licence_status' => 'draft',
			),
			array( '%s' ),
			array( '%d', '%s' )
		);
	}

  
  public static function custom_title_for_developer_dashboard( $post_title, $post_id ) {
    
    if ( $post_title == 'Developer Dashboard' ) {
      
      $user = wp_get_current_user();

      // Check whether that user exists and is actually a Product Developer
      if ( $user && is_array( $user->roles ) && in_array( self::DEV_ROLE_NAME, $user->roles ) ) {
        $developer_term = self::find_developer_term_by_user_id( $user->ID );
        
        if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
          $post_title = $developer_term->name . ' Dashboard' ;
        }
      }
    }
    
    return $post_title;
  }
  
  /**
   * Callback function for 'wp_new_user_notification_email' filter.
   * 
   * This filter is applied in wp_new_user_notification() when a new user is created and email notification is sent to the user
   * 
   * The purpose of this callback is to update taxonomy meta and link taxonomy item to the user 
   * for the case when email is sent to a developer
   * 
   */ 
  public static function attach_developer_taxonomy_to_new_user( array $email, object $user, $blogname ) {
   
    $user_roles = implode( '', $user->roles );
    
    if ( $user_roles === self::DEV_ROLE_NAME ) { // this user is a developer
      
      $company_name = $user->first_name; // We assume that the developer account is created with the username set equal to the company name
      $company_slug = $user->user_login; // also it must ne created with user login equal to the developer slug
      
      $taxonomy_term = get_term_by( 'name', $company_name, 'developer' );
      $taxonomy_term2 = get_term_by( 'slug', $company_slug, 'developer' );
      
      if (     is_object( $taxonomy_term ) 
            && is_object( $taxonomy_term2 ) 
            && $taxonomy_term2->term_id === $taxonomy_term->term_id ) { // found taxonomy term that matches freshly created user by both name & slug 
        
        update_term_meta( $taxonomy_term->term_id, 'user_account', $user->ID ); // attach user account to the developer taxonomy term
      }
    }
    
    return $email; // since this is a callback for a email filter, return unchanged email
  }

	/**
	 * Allow Product Developer users to satisfy `read_post` and `edit_post` for draft WooCommerce products
	 * they own (developer taxonomy), so front-end preview works without `edit_product`.
	 *
	 * Core and WooCommerce both require `edit_post` for draft single products: `WP_Query` clears the post
	 * when that check fails (404), and `WC_Product::is_visible_core()` hides non-draft products the same way.
	 *
	 * @param string[] $caps    Primitive caps required.
	 * @param string   $cap     Meta capability name.
	 * @param int      $user_id User ID.
	 * @param array    $args    Extra args; index 0 is post ID for read_post / edit_post.
	 * @return string[]
	 */
	public static function map_meta_cap_draft_product_preview_for_developers( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'read_post', 'edit_post' ), true ) || empty( $args[0] ) ) {
			return $caps;
		}

		$post_id = (int) $args[0];
		if ( $post_id <= 0 ) {
			return $caps;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'product' !== $post->post_type || 'draft' !== $post->post_status ) {
			return $caps;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! in_array( self::DEV_ROLE_NAME, (array) $user->roles, true ) ) {
			return $caps;
		}

		if ( ! Ddb_Frontend::is_product_from_developer( $post_id, $user_id ) ) {
			return $caps;
		}

		return array( 'read' );
	}
    
	public function add_page_to_menu() {
		$review_products_count      = $this->get_pending_approval_products_count();
		$review_products_menu_title = __( 'Review Developer Products', 'ddb' );

		if ( $review_products_count > 0 ) {
			$review_products_menu_title .= sprintf(
				' <span class="update-plugins count-%1$d"><span class="plugin-count">%2$s</span></span>',
				$review_products_count,
				number_format_i18n( $review_products_count )
			);
		}
    
		add_management_page(
			__( 'Developer Payout Dashboard' ),          // page title.
			__( 'Developer Payout Dashboard' ),          // menu title.
			'manage_options',
			'ddb-settings',			                // menu slug.
			array( $this, 'render_settings_page' )   // callback.
		);

		add_management_page(
			__( 'Review Developer Products', 'ddb' ),     // page title.
			$review_products_menu_title,                 // menu title.
			'manage_options',
			'ddb-review-developer-products',             // menu slug.
			array( $this, 'render_approval_products_page' )
		);
  }

	public function get_pending_approval_products_count() {
		$pending_products_query = new WP_Query( array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'pending_apd_review',
					'value'   => array( self::STATUS_DRAFT ),
					'compare' => 'IN',
				),
			),
		) );

		return (int) $pending_products_query->found_posts;
	}
  
  
  public function do_action() {
    
    $result = '';
    
    if ( isset( $_POST['ddb-button'] ) ) {
      
      $start_date              = filter_input( INPUT_POST, self::FIELD_DATE_START );
      $end_date                = filter_input( INPUT_POST, self::FIELD_DATE_END );
      $free_orders             = (bool) filter_input( INPUT_POST, 'include_free_orders' );
      $product_id              = (int) filter_input( INPUT_POST, 'product_id' );
      $deal_product_id         = (int) filter_input( INPUT_POST, 'deal_product_id' );
      $dev_id                  = filter_input( INPUT_POST, 'developer_id' );
      $include_billing_address = (bool) filter_input( INPUT_POST, 'include_billing_address' );
      
      switch ( $_POST['ddb-button'] ) {
        case self::ACTION_SAVE_OPTIONS:
         
          $stored_options = get_option( 'ddb_options', array() );
          $stored_options['include_notes_into_report'] = $_POST['include_notes_into_report'] ?? false;
          $stored_options['global_default_profit_ratio'] = $_POST['global_default_profit_ratio'];
          
          update_option( 'ddb_options', $stored_options );
          
          foreach ( $_POST['dev_info'] as $dev_id => $dev_info ) {
            self::update_developer_settings( $dev_id, $dev_info );
          }
					break;
       
        case self::ACTION_GENERATE_REPORT_TABLE:
          
          $skip_deal_shop_check = (bool) filter_input( INPUT_POST, 'skip_deal_shop_check' );
          $report_settings = [
            'developer_id'           => $dev_id,
            'product_id'             => $product_id,
            'deal_product_id'        => $deal_product_id,
            'include_free_orders'    => $free_orders,
            'skip_deal_shop_check'   => $skip_deal_shop_check,
            'include_billing_address'=> $include_billing_address,
          ];
          $result = self::generate_table_sales_report_for_admin( $start_date, $end_date, $report_settings );
          break;
       
        case self::ACTION_GENERATE_REPORT_XLSX:
        
          $result = Ddb_Plugin::generate_xlsx_sales_report_for_admin();
          break;
				
				case self::ACTION_START_CRON_REPORTS_GENERATION:
					
					$errors_found = Ddb_Frontend::validate_input( $start_date, $end_date );
					
					if ( ! $errors_found ) {
						
						$use_bf_products = (bool) filter_input( INPUT_POST, 'use_bf_products' );
						
						$result = "<h2 style='color:purple;'>Started cron generation of all developer reports (using sales from $start_date to $end_date)</h2>";

						$parameters = array(
							'start_date' => $start_date,
							'end_date' => $end_date,
							'use_bf_products' => $use_bf_products
						);
						Ddb_Cron_Generator::start_processing( $parameters );
					}
					else {
						$result = $errors_found;
					}
					
					
					break;
				
				case self::ACTION_STOP_CRON_REPORTS_GENERATION:
					
					$result = "<h2 style='color:red;'>Stopped cron generation for developer reports</h2>";
					
					Ddb_Cron_Generator::stop_processing();
					
					break;
				
				case self::ACTION_RESTART_CRON_REPORTS_GENERATION:
					
					$result = "<h2 style='color:green;'>Re-started cron generation for stuck developer reports</h2>";
					Ddb_Cron_Generator::restart_processing();
					
					break;
      }
    }
    
    return $result;
  }
  
	
  /**
   * Get list of developers: [ id => [ name, slug and settings ] ]
   * 
   * @return array
   */
  public static function get_developer_list_and_settings() {
    $developers     = get_terms( array( 'taxonomy' => 'developer', 'hide_empty' => false ) );
		
		$arr_developers = array();
    
		if ( is_array($developers) ) {
			foreach ( $developers as $developer ) {

				$developer_data = array(
					'slug' => $developer->slug,
					'name' => $developer->name
				);

				foreach ( self::$dev_profile_settings as $setting_name => $default_value ) {
					$value =  get_term_meta( $developer->term_id, $setting_name, /* single? */ true );
					$developer_data[ $setting_name ] = $value ?: $default_value; // Elvis operator! 
				}

				$arr_developers[ $developer->term_id ] = $developer_data;

			}
		}

		return $arr_developers;
  }
  
  /**
   * Renders table row with fields to set developer profile settings
   * 
   * @param integer $dev_id
   * @param array $dev_data info for that particular developer
   */
  public static function get_developer_fields( int $dev_id, array $dev_data ) {
    
    $field_set = array(
			array(
				'name'    => "dev_info[$dev_id][profit_ratio]",
				'type'    => 'dropdown',
				'label'   => 'Profit ratio',
				'default' => self::USE_GLOBAL_PROFIT_RATIO,
        'options' => self::$profit_ratio_options,
        'value'   => $dev_data['profit_ratio'] ?? ''
			),
      array(
				'name'    => "dev_info[$dev_id][payment_method]",
				'type'    => 'dropdown',
				'label'   => 'Payment method',
				'default' => self::PM__NONE,
        'options' => self::$payment_methods_names,
        'value'   => $dev_data['payment_method'] ?? ''
			),
      array(
				'name'    => "dev_info[$dev_id][paypal_address]",
				'type'    => 'text',
				'label'   => 'Paypal address',
				'default' => '',
        'value'   => $dev_data['paypal_address'] ?? ''
			),
      array(
				'name'    => "dev_info[$dev_id][user_account]",
				'type'    => 'text',
				'label'   => 'User Account',
				'default' => '',
        'value'   => $dev_data['user_account'] ?? ''
			),
      array(
				'name'    => "dev_info[$dev_id][dropbox_folder_url]",
				'type'    => 'text',
				'label'   => 'Dropbox URL',
				'default' => '',
        'value'   => $dev_data['dropbox_folder_url'] ?? ''
			),
      array(
				'name'    => "dev_info[$dev_id][additional_notes]",
				'type'    => 'text',
				'label'   => 'Additional notes',
				'default' => '',
        'value'   => $dev_data['additional_notes'] ?? ''
			),
		);
    
    if ( $dev_id == 0 ) { // special case - we need only the field labels
      return $field_set;
    }
    
    $row_html = self::render_fields_row( $field_set );
    return $row_html;
  }
  
  
  public static function update_developer_settings( $dev_id, $dev_data ) {
    
    foreach ( self::$dev_profile_settings as $field_name => $default_value ) {
      
      if ( isset( $dev_data[ $field_name ] ) ) {
        $new_value = $dev_data[ $field_name ];
        update_term_meta($dev_id, $field_name, sanitize_text_field( $new_value ) );
      }
    }
    
  }
  
  
  
  /**
   * Generates HTML table with report containing all sales for the specified developer in the specified date range
   * 
   * @param $start_date string date in format Y-m-d
   * @param $end_date string date in format Y-m-d 
   * @param $report_settings array [ developer_id, product_id, deal_product_id, include_free_orders ] 
   
   */
  public static function generate_table_sales_report_for_admin( string $start_date, string $end_date, array $report_settings ) {
    
    $result = "<h2 style='color:red;'>Error: could not generate the report ( $start_date, $end_date )</h2>";
    
    
    $developer_id = $report_settings['developer_id'] ?? 0;
    $product_id = $report_settings['product_id'] ?? 0;
    $deal_product_id = $report_settings['deal_product_id'] ?? 0;
      
    if ( $developer_id != 0 ) {
      $developer_term = self::find_developer_term_by_id( $developer_id );
      $result = Ddb_Report_Generator::generate_table_report( $developer_term, $start_date, $end_date, $report_settings );
    }
    elseif ( $product_id || $deal_product_id ) {
      $result = Ddb_Report_Generator::generate_table_report( null, $start_date, $end_date, $report_settings );
    }
    
    return $result;
  }
  
  /**
   * Generates XLSX file when requested from admin area
   */
  public static function generate_xlsx_sales_report_for_admin() {

		
		
    $start_date       = filter_input( INPUT_POST, self::FIELD_DATE_START );
    $end_date         = filter_input( INPUT_POST, self::FIELD_DATE_END );
    $free_orders      = (bool) filter_input( INPUT_POST, 'include_free_orders' );
    $product_id       = sanitize_text_field( filter_input( INPUT_POST, 'product_id') ?? 0 );
    $deal_product_id  = sanitize_text_field( filter_input( INPUT_POST, 'deal_product_id') ?? 0 );
    $dev_id           = filter_input( INPUT_POST, 'developer_id' );
		$save_to_file     = filter_input( INPUT_POST, 'save_to_file' );
		$skip_deal_shop_check = (bool) filter_input( INPUT_POST, 'skip_deal_shop_check' );
    $include_billing_address = (bool) filter_input( INPUT_POST, 'include_billing_address' );
		
    if ( $dev_id || $product_id || $deal_product_id ) {
      
      $developer_term = $dev_id ? self::find_developer_term_by_id( $dev_id ) : null;
      
      $report_settings = [
				'start_date'          => $start_date,
				'end_date'            => $end_date,
				'product_id'          => $product_id, 
				'deal_product_id'     => $deal_product_id, 
				'include_free_orders' => $free_orders,
				'save_to_file'        => $save_to_file,
				'skip_deal_shop_check' => $skip_deal_shop_check,
				'include_billing_address' => $include_billing_address
			];
      
			$report_name          = is_object( $developer_term ) ? $developer_term->name : 'products';
			$filename             = $report_name . '_report_from_' . $start_date . '_to_' . $end_date . '.xlsx';
			
			if ( $save_to_file ) { 
				$directory_path       = self::create_folder_for_reports( $report_settings );
				$report_download_url  = self::create_url_for_reports( $report_settings ) . '/' . $filename;
				$report_settings['save_path'] = $directory_path . '/' . $filename;
				$report_settings['save_url'] = $report_download_url;
			}
			
      $report_generated = Ddb_Report_Generator::generate_xlsx_report( $developer_term, $start_date, $end_date, $report_settings );

			$result = "<h2 style='color:red;'>Found no orders for the report ( from $start_date to $end_date, developer ID $dev_id, product ID  $product_id )</h2>";
			
      if ( $report_generated ) {
				if ( $save_to_file ) {
					$result = "<h2 style='color:teal;'>Found $report_generated orders for the report ( from $start_date to $end_date, developer ID $dev_id, product ID  $product_id )</h2>";
					$result .= "<h2 style='color:teal;'><a href='$report_download_url'>Download_report</a></h2>";
				}
				else { // Ddb_Report_Generator::generate_xlsx_report has already sent file contents to the browser. Must not add extra output.
					exit();
				}
      }
    }
		else {		
			$result = "<h2 style='color:red;'>Incorrect parameters for the report ( developer ID $dev_id, product ID $product_id )</h2>";
		}
		
		return $result;
  }
  
  /**
   * 
   * option_name to search for in "wp_options" is in the format of 'aff_cron_results_XXXX', where XXX if $payroll_timestamp
   * 
   * $payout_category either 'paypal' or 'others'
   */
  public static function generate_payout_report_for_admin() {
    
    $payout_category = $_GET['payout_category'];
    
    if ( in_array( $payout_category, array( 'paypal', 'others', 'summary' ) ) ) {
      
      $payroll_timestamp = $_GET['source_timestamp'];

      $filename = 'payout_report_' . $payout_category . '_' . date('Y-m-d', $payroll_timestamp );
      $format = 'csv';

      self::load_options();
      
      $global_profit_ratio = self::$option_values['global_default_profit_ratio'];
			
			self::wc_log( '$global_profit_ratio: ' . $global_profit_ratio );
        
      $report_data = Ddb_Cron_Generator::get_saved_report_data( $payroll_timestamp );
      
      $developer_settings = self::get_developer_settings_by_payout_category( $payout_category );

      if ( $report_data && $developer_settings ) {
        if ( $payout_category == self::PM__PAYPAL ) {
          Ddb_Report_Generator::generate_paypal_payroll_report( $filename, $report_data, $developer_settings, $global_profit_ratio, $format );
        }
        else if ( $payout_category == 'others' ) {
          Ddb_Report_Generator::generate_general_payroll_report( $filename, $report_data, $developer_settings, $global_profit_ratio, $format );
        }
        else {
          Ddb_Report_Generator::generate_summary_report( $filename, $report_data, $developer_settings, $global_profit_ratio, $format );
        }
      }
    }
  }
  
  /**
   * Finds developer payout settings for all developers that have specific payment type.
   * 
   * If $payout_category == 'summary', then find all developers.
   * If $payout_category == 'paypal', then find all developers who is paid via Paypal. 
   * Otherwise, find all developers that are paid via other methods (not Paypal).
   * 
   * @global object $wpdb
   * @param string $payout_category
   * @return array
   */
  public static function get_developer_settings_by_payout_category( string $payout_category ) {
        
    global $wpdb;
    $wp = $wpdb->prefix;
    
    $developers = array();

    if ( $payout_category == self::PM__PAYPAL ) { // find only those developers that are paid via paypal
      $query_sql = $wpdb->prepare( "SELECT term_id FROM `{$wp}termmeta` WHERE `meta_key` = 'payment_method' AND `meta_value` = '%s' ", $payout_category );
    }
    elseif ( $payout_category == 'summary' )  { // find all developers
      $query_sql = "SELECT term_id FROM `{$wp}termmeta` WHERE `meta_key` = 'payment_method' AND `meta_value` != '' ";
    }
    else { // find those developers that are paid via anything but paypal
      $query_sql = $wpdb->prepare( "SELECT term_id FROM `{$wp}termmeta` WHERE `meta_key` = 'payment_method' AND `meta_value` != '%s' ", self::PM__PAYPAL );
    }

    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );

    foreach ( $sql_results as $row ) {
      $developer_id = $row['term_id'];

      $developer_data = array();

      foreach ( self::$dev_profile_settings as $setting_name => $default_value ) {
        $developer_term = get_term( $developer_id, 'developer' );
        
        if ( $developer_term ) { // extra check to be sure that is it really a developer taxonomy item
          $value =  get_term_meta( $developer_id, $setting_name, /* single? */ true );
          $developer_data[ $setting_name ] = $value ?: $default_value; 
        }
        
        //$developer_data['name'] = $developer_term->name;
      }

      $developers[$developer_id] = $developer_data;

    }
    
		//self::wc_log( " get_developer_settings_by_payout_category - $payout_category", $developers);
		
    return $developers;
  }
  
  
	public function render_settings_page() {
    
    $action_results = '';
    
    if ( isset( $_POST['ddb-button'] ) ) {
			$action_results = $this->do_action();
		}
    
    echo $action_results;
		
    $this->developers = self::get_developer_list_and_settings();
    
    self::load_options();
   
    $this->render_report_generator_form();
    $this->render_payout_report_form();
		$this->render_generation_schedule_form();
    $this->render_settings_form();
    
  }

	public function render_approval_products_page() {
		?>
		<div class="wrap">
			<?php $this->render_approval_products_list(); ?>
		</div>
		<?php
	}
  
  /**
   * Shows the form used to generate developer reports for the admin
   */
  public function render_report_generator_form() {
    
    $start_date         = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_START ) ?? date( 'Y-m-d', strtotime("-30 days") ) );
    $end_date           = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_END ) ?? self::get_today_date() );
    $developer_id       = sanitize_text_field( filter_input( INPUT_POST, 'developer_id') ?? 0 );
    $product_id         = sanitize_text_field( filter_input( INPUT_POST, 'product_id') ?? 0 );
    $deal_product_id    = sanitize_text_field( filter_input( INPUT_POST, 'deal_product_id') ?? 0 );
    
    $developers     = self::get_developer_list_and_settings();
    $products       = self::get_all_developer_products();
    $deal_products  = self::get_all_deal_products();
    
    $developer_list       = array( 0 => '[Not Selected]' );
    $products_list        = array( 0 => '[Not Selected]' ) + $products;
    $deal_products_list   = array( 0 => '[Not Selected]' ) + $deal_products;
    
    foreach( $developers as $term_id => $dev_data ) {
      $developer_list[$term_id] = $dev_data['name'];
    }
    
    $report_field_set = array(
      array(
				'name'        => "report_date_start",
				'type'        => 'date',
				'label'       => 'Start date',
        'min'         => '2020-01-01',
        'value'       => $start_date,
        'description' => '' 
			),
      array(
				'name'        => "report_date_end",
				'type'        => 'date',
				'label'       => 'End date',
        'min'         => '2020-01-01',
        'value'       => $end_date,
        'description' => ''
			),
      array(
				'name'        => "developer_id",
				'type'        => 'dropdown',
				'label'       => 'Developer',
        'autocomplete'=> true,
        'options'     => $developer_list,
        'value'       => $developer_id,
        'description' => ''
			),
      array(
				'name'        => "product_id",
				'type'        => 'dropdown',
				'label'       => 'Shop Product',
        'autocomplete'=> true,
        'options'     => $products_list,
        'value'       => $product_id,
        'description' => ''
			),
      array(
				'name'        => "deal_product_id",
				'type'        => 'dropdown',
				'label'       => 'Deal Product',
        'autocomplete'=> true,
        'options'     => $deal_products_list,
        'value'       => $deal_product_id,
        'description' => ''
			),
      array(
				'name'        => "include_free_orders",
				'type'        => 'checkbox',
				'label'       => 'Include free orders ( with $0 total sum )',
        'value'       => 0,
        'description' => ''
			),
      array(
				'name'        => "skip_deal_shop_check",
				'type'        => 'checkbox',
				'label'       => 'Skip deal/shop check ( added for Black Friday products )',
        'value'       => 0,
        'description' => ''
			),
      array(
				'name'        => "include_billing_address",
				'type'        => 'checkbox',
				'label'       => 'Include billing address into report',
        'value'       => 0,
        'description' => ''
			),
			array(
				'name'        => "save_to_file",
				'type'        => 'checkbox',
				'label'       => 'Save report to file',
        'value'       => 0,
        'description' => ''
			)
		);
    
		$last_report_url = get_option( 'ddb_last_generated_report', false );
		
    ?> 

		<?php if ( $last_report_url) : ?>
			<hr>
			<a target="_blank" href="<?php echo $last_report_url; ?>">Download last generated report</a>
			<hr>
		<?php endif; ?>
				
    <form method="POST" >
    
      <h2><?php esc_html_e('Generate Developer Report', 'ddb'); ?></h2>
      
      
      <table class="ddb-global-table">
        <tbody>
          <?php self::display_field_set( $report_field_set ); ?>
        </tbody>
      </table>
    
      
      <p class="submit">
       <input type="submit" id="ddb-button-generate-xlsx" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_GENERATE_REPORT_XLSX; ?>" />
       <input type="submit" id="ddb-button-generate-table" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_GENERATE_REPORT_TABLE; ?>" />
      </p>
    
    </form>
    <?php 

  }
	
	 
  /**
   * Shows the form used to generate developer reports for the admin
   */
  public function render_generation_schedule_form() {
    
    $start_date         = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_START ) ?? date( 'Y-m-d', strtotime("-30 days") ) );
    $end_date           = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_END ) ?? self::get_today_date() );
    
    $mass_generation_field_set = array(
      array(
				'name'        => self::FIELD_DATE_START,
				'type'        => 'date',
				'label'       => 'Start date',
        'min'         => '2020-01-01',
        'value'       => $start_date,
        'description' => '' 
			),
      array(
				'name'        => self::FIELD_DATE_END,
				'type'        => 'date',
				'label'       => 'End date',
        'min'         => '2020-01-01',
        'value'       => $end_date,
        'description' => ''
			)
		);
    
		
		$cron_is_running = wp_next_scheduled( Ddb_Cron_Generator::HOOK_NAME );
		
    ?> 

    <form method="POST" >
    
      <h2><?php esc_html_e('Mass Report Generation', 'ddb'); ?></h2>
      
      
      <table class="ddb-global-table">
        <tbody>
          <?php self::display_field_set( $mass_generation_field_set ); ?>
        </tbody>
      </table>
    
      <label for="ddb_use_bf_products">Include only Black Friday products into reports</label>
      <input type="checkbox" id="ddb_use_bf_products" name="use_bf_products" value="1" class="ddb-checkbox-field">
      
      <p class="submit">
				<?php if ( ! $cron_is_running ): ?>
					<input type="submit" id="ddb-button-start-cron" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_START_CRON_REPORTS_GENERATION; ?>" />
					<input type="submit" id="ddb-button-re-generate-cron" name="ddb-button" class="button" value="<?php echo self::ACTION_RESTART_CRON_REPORTS_GENERATION; ?>" />
					<br><br>
          <input type="submit" id="ddb-button-stop-cron" name="ddb-button" class="button" value="<?php echo self::ACTION_STOP_CRON_REPORTS_GENERATION; ?>" />
				<?php endif; ?>
					
				<?php if ( $cron_is_running ): ?>
					<br><br>
					<input type="submit" id="ddb-button-re-generate-cron" name="ddb-button" class="button" value="<?php echo self::ACTION_RESTART_CRON_REPORTS_GENERATION; ?>" />
					<br><br>
					<input type="submit" id="ddb-button-stop-cron" name="ddb-button" class="button" value="<?php echo self::ACTION_STOP_CRON_REPORTS_GENERATION; ?>" />
				<?php endif; ?>
      </p>
      
    </form>

    <?php 
			echo Ddb_Cron_Generator::render_status();
  }

  
  public function render_settings_form() {
    
    $global_settings_field_set = array(
      array(
				'name'        => "global_default_profit_ratio",
				'type'        => 'text',
				'label'       => 'Global profit ratio',
				'default'     => '',
        'value'       => self::$option_values['global_default_profit_ratio'],
        'description' => 'Enter 0.05 for 5% profit ratio'
			),
      array(
				'name'        => "include_notes_into_report",
				'type'        => 'checkbox',
				'label'       => 'Include notes into report',
				'default'     => '',
        'value'       => self::$option_values['include_notes_into_report'],
			)
		);
    
    ?> 

    <form method="POST" >
    
      <h2><?php esc_html_e('Developer Payout Dashboard', 'ddb'); ?></h2>
      
      
      <table class="ddb-global-table">
        <tbody>
          <?php self::display_field_set( $global_settings_field_set ); ?>
        </tbody>
      </table>
      
      <h2><?php esc_html_e('APD Developers profiles & settings', 'ddb'); ?></h2>
      
      <table class="ddb-table">
        <thead>
          <th>Developer name</th>
          <th>Developer account</th>
          <?php 
          
            $header_info = self::get_developer_fields(0, array());
            
            foreach ( $header_info as $column_header ) {
              echo( '<th>' . $column_header['label'] . '</th>');
            }
          ?>
        </thead>
        <tbody>
          <?php foreach ( $this->developers as $dev_id => $dev_data ): ?>
            <?php
            
              $developer_setting_fields = self::get_developer_fields( $dev_id, $dev_data );
            
              if ( $dev_data['user_account'] ) {
                
                $dev_user_id = $dev_data['user_account'];
                
                $switch_link = wp_nonce_url( add_query_arg( array(
                  'action'  => 'switch_to_user',
                  'user_id' => $dev_user_id,
                  'nr'      => 1,
                ), wp_login_url() ), "switch_to_user_{$dev_user_id}" );
    
                $account_links = 
                  ' [<a target="_blank" href="/wp-admin/user-edit.php?user_id=' . $dev_user_id . '">Edit</a>] ' . 
                  ' [<a target="_blank" href="' . $switch_link . '">Switch to</a>] ';
              }
              else {
                $account_links = '';
              }
            ?>
            <tr>
              <td><a target="_blank" href="/developer/<?php echo $dev_data['slug']; ?>"><?php echo $dev_data['name']; ?></a></td>
              <td><?php echo $account_links; ?> </td>
              <?php echo $developer_setting_fields; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <p class="submit">  
       <input type="submit" id="ddb-button-save" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_SAVE_OPTIONS; ?>" />
      </p>
      
    </form>
    <?php 
    
  }

  public function render_approval_products_list() {

    $pending_products = get_posts( array(
      'post_type'      => 'product',
      'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
      'posts_per_page' => -1,
      'orderby'        => 'modified',
      'order'          => 'DESC',
      'meta_query'     => array(
        array(
          'key'     => 'pending_apd_review',
          'value'   => array( self::STATUS_PUBLISHED_AND_EDITED, self::STATUS_DRAFT, self::STATUS_APPROVED ),
          'compare' => 'IN',
        ),
      ),
    ) );
    ?>
    <h2><?php esc_html_e( 'Products Pending APD Review', 'ddb' ); ?></h2>

    <table class="ddb-table">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Developer', 'ddb' ); ?></th>
          <th><?php esc_html_e( 'Product title', 'ddb' ); ?></th>
          <th><?php esc_html_e( 'Draft?', 'ddb' ); ?></th>
          <th><?php esc_html_e( 'Status', 'ddb' ); ?></th>
          <th><?php esc_html_e( 'Last time updated', 'ddb' ); ?></th>
          <th><?php esc_html_e( 'Actions', 'ddb' ); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if ( ! empty( $pending_products ) ) : ?>
          <?php foreach ( $pending_products as $pending_product ) : ?>
            <?php
              $developer_name  = '';
              $developer_terms = get_the_terms( $pending_product->ID, 'developer' );
              $review_status   = get_post_meta( $pending_product->ID, 'pending_apd_review', true );
              $is_approved     = ( self::STATUS_APPROVED === (string) $review_status );
              $is_published    = ( 'publish' === $pending_product->post_status );

              if ( self::STATUS_APPROVED === (string) $review_status ) {
                $status_label = 'Approved';
              } elseif ( self::STATUS_PUBLISHED_AND_EDITED === (string) $review_status ) {
                $status_label = 'Previously published, needs review';
              } elseif ( self::STATUS_DRAFT === (string) $review_status ) {
                $status_label = 'Not published yet';
              }

              if ( ! is_wp_error( $developer_terms ) && ! empty( $developer_terms ) ) {
                $developer_names = wp_list_pluck( $developer_terms, 'name' );
                $developer_name  = implode( ', ', $developer_names );
              }
            ?>
            <tr class="ddb-approval-product-row" data-product-id="<?php echo esc_attr( (string) $pending_product->ID ); ?>">
              <td><?php echo esc_html( $developer_name ); ?></td>
              <td class="ddb-approval-product-title">
                <a class="ddb-approval-product-link" target="_blank" href="<?php echo esc_url( get_edit_post_link( $pending_product->ID ) ); ?>">
                  <?php echo esc_html( get_the_title( $pending_product ) ); ?>
                </a>
              </td>
              <td class="ddb-approval-product-draft"><?php echo ( $pending_product->post_status === 'draft' ? 'Yes' : 'No' ); ?></td>
              <td class="ddb-approval-product-status"><?php echo esc_html( $status_label ); ?></td>
              <td><?php echo esc_html( get_the_modified_date( 'Y-m-d H:i', $pending_product ) ); ?></td>
              <td>
                <?php if ( $is_approved && $is_published ) : ?>
                  <button type="button" class="button button-secondary ddb-move-product-to-draft-button" data-product-id="<?php echo esc_attr( (string) $pending_product->ID ); ?>">
                    <?php esc_html_e( 'Move to Draft', 'ddb' ); ?>
                  </button>
                <?php else : ?>
                  <?php if ( ! $is_approved ) : ?>
                    <button type="button" class="button button-secondary ddb-approve-product-button" data-product-id="<?php echo esc_attr( (string) $pending_product->ID ); ?>">
                      <?php esc_html_e( 'Approve', 'ddb' ); ?>
                    </button>
                  <?php endif; ?>
                  <button type="button" class="button button-primary ddb-approve-publish-product-button" data-product-id="<?php echo esc_attr( (string) $pending_product->ID ); ?>">
                    <?php esc_html_e( 'Approve and publish', 'ddb' ); ?>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="6"><?php esc_html_e( 'No products are currently pending APD review.', 'ddb' ); ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
  }

  public function ajax_approve_pending_product() {

    check_ajax_referer( 'ddb_approve_pending_product', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array(
        'message' => __( 'You are not allowed to approve products.', 'ddb' ),
      ), 403 );
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $product    = get_post( $product_id );

    if ( ! $product || 'product' !== $product->post_type ) {
      wp_send_json_error( array(
        'message' => __( 'Invalid product.', 'ddb' ),
      ), 400 );
    }

    update_post_meta( $product_id, 'pending_apd_review', self::STATUS_APPROVED );

    wp_send_json_success( array(
      'product_id' => $product_id,
    ) );
  }

  public function ajax_approve_and_publish_product() {

    check_ajax_referer( 'ddb_approve_and_publish_product', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array(
        'message' => __( 'You are not allowed to approve products.', 'ddb' ),
      ), 403 );
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $product    = get_post( $product_id );

    if ( ! $product || 'product' !== $product->post_type ) {
      wp_send_json_error( array(
        'message' => __( 'Invalid product.', 'ddb' ),
      ), 400 );
    }

    wp_update_post( array(
      'ID'          => $product_id,
      'post_status' => 'publish',
    ) );

    update_post_meta( $product_id, 'pending_apd_review', self::STATUS_APPROVED );

    wp_send_json_success( array(
      'product_id' => $product_id,
    ) );
  }

  public function ajax_move_product_to_draft() {

    check_ajax_referer( 'ddb_move_product_to_draft', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array(
        'message' => __( 'You are not allowed to update products.', 'ddb' ),
      ), 403 );
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $product    = get_post( $product_id );

    if ( ! $product || 'product' !== $product->post_type ) {
      wp_send_json_error( array(
        'message' => __( 'Invalid product.', 'ddb' ),
      ), 400 );
    }

    wp_update_post( array(
      'ID'          => $product_id,
      'post_status' => 'draft',
    ) );

    update_post_meta( $product_id, 'pending_apd_review', self::STATUS_DRAFT );

    wp_send_json_success( array(
      'product_id' => $product_id,
    ) );
  }
  
  public function render_payout_report_form() {
    
    $available_summaries = self::get_available_report_summaries();
    
    $summary_names = array();
    
    foreach ( $available_summaries as $timestamp => $summary ) {
      
      $start = $summary['start_date'] ?? '';
      $end = $summary['end_date'] ?? '';
      $gen_date = date('Y-m-d H:i', intval( $timestamp ) );
      $summary_names[$timestamp] = " From $start to $end (generated on $gen_date)";
    }
    
    $payout_field_set = array(
      array(
				'name'        => "payout_category",
				'type'        => 'dropdown',
				'label'       => 'Payout report type',
				'default'     => 'paypal',
        'options'     => array(
          'paypal'  => 'Paypal payment method',
          'others'  => 'All other payment methods',
          'summary' => 'Summary for all developers'
        ),
        'description' => ''
			),
      array(
				'name'        => "source_timestamp",
				'type'        => 'dropdown',
				'label'       => 'Select report batch to use',
				'default'     => '',
        'options'     => $summary_names,
        'value'       => ''
			)
		);
    
    ?> 

    <form method="GET" >
    
      <h2><?php esc_html_e('Generate Payout Reports', 'ddb'); ?></h2>
      
			<input type="hidden" name="page" value="ddb-settings" />
      
      <table class="ddb-global-table">
        <tbody>
          <?php self::display_field_set( $payout_field_set ); ?>
        </tbody>
      </table>
    
      
      <p class="submit">  
       <input type="submit" id="ddb-button-save" name="<?php echo self::BUTTON_SUMBIT; ?>" class="button button-primary" value="<?php echo self::ACTION_GENERATE_PAYOUT; ?>" />
      </p>
      
    </form>
    <?php 
	
  }

}