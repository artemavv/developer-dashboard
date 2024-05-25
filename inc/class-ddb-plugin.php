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
    
  public function __construct( $plugin_root ) {

		$this->plugin_root = $plugin_root;

		add_action( 'plugins_loaded', array( $this, 'initialize'), 10 );
	  
    if ( is_admin() ) {
      add_action('admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts') );
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
   
    add_filter( 'wp_new_user_notification_email', array( 'Ddb_Plugin', 'modify_email_notification_for_new_developers' ), 10, 3 );
    add_filter( 'wp_new_user_notification_email', array( 'Ddb_Plugin', 'attach_developer_taxonomy_to_new_user' ), 10, 3 );
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
    add_shortcode( 'developer_dashboard', array( 'Ddb_Frontend', 'render_developer_dashboard' ) );
    add_shortcode( 'display_content_for_developers_only', array( 'Ddb_Frontend', 'display_developer_content' ) );
    add_shortcode( 'display_content_for_affiliates_only', array( 'Ddb_Frontend', 'display_affiliate_content' ) );
	}
  
  public function register_admin_styles_and_scripts() {
    $file_src = plugins_url( 'css/ddb-admin.css', $this->plugin_root );
    wp_enqueue_style( 'ddb-admin', $file_src, array(), DDB_VERSION );
  }
  
  public function register_frontend_scripts_when_shortcode_present() {
    global $post;
    
    if ( true ) { //if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'developer_dashboard') ) {
      $file_src = plugins_url( 'css/ddb-front.css', $this->plugin_root );
      wp_enqueue_style( 'ddb-front', $file_src, array(), DDB_VERSION );
    }
  }

  /**
   * Callback function for 'wp_new_user_notification_email' filter.
   * 
   * This filter is applied in wp_new_user_notification() when a new user is created and email notification is sent to the user
   * 
   * The purpose of this callback is to modify email message for the case when it is sent to a developer
   * 
   * @param array   $email {
   *     Used to build wp_mail().
   *
   *     @type string $to      The intended recipient - New user email address.
   *     @type string $subject The subject of the email.
   *     @type string $message The body of the email.
   *     @type string $headers The headers of the email.
   * }
   * @param WP_User $user     User object for new user.
   * @param string  $blogname The site title.
   */
  public function modify_email_notification_for_new_developers( array $email, object $user, $blogname ) {
    
    
    $user_roles = implode( '', $user->roles );
    
    if ( $user_roles === self::DEV_ROLE_NAME ) { // this user is a developer
      
      ob_start;
      ?>
    <h3>Hey {company_name} Team,</h3>
    
    <p class="c0"><span class="c9">We&#39;re thrilled to announce a major upgrade to how you manage your sales with Audio Plugin Deals! We&#39;ve just launched a brand-new, personalized </span><strong>Developer Dashboard</strong><span class="c5">&nbsp;that puts you in complete control.</span></p><p class="c0"><span class="c14 c7">What&#39;s in it for you?</span></p>
    
    <ul class="c4 lst-kix_list_1-0 start">
        <li class="c8 li-bullet-0"><strong>Real-Time Sales Tracking:</strong><span class="c5">&nbsp;Instantly see how your deals are performing.</span></li>
        <li class="c8 li-bullet-0"><strong>Reports-on-demand:</strong><span class="c5">&nbsp;Generate sales reports on the fly, tailored to your needs.</span></li>
        <li class="c8 li-bullet-0"><strong>Easy Access to Past Reports:</strong><span class="c5">&nbsp;Quickly review previous reports stored on Dropbox, directly from your dashboard.</span></li>
    </ul>
    
    <h3><strong>How to Get Started:</strong></h3>
    
    <ol class="c4 lst-kix_list_2-0 start" start="1">
        <li class="c2 li-bullet-0"><strong>Your login:</strong></li></ol>
        <ul class="c4 lst-kix_list_3-1 start">
            <li class="c11 li-bullet-0"><span class="c5">Username: {username} (or use this email address)</span></li>
            <li class="c11 li-bullet-0"><span class="c9">Generate a new password here: </span><span class="c6"><a class="c3" href="{reset_url}">https://audioplugin.deals/reset-password/</a></span></li>
        </ul>
    <ol class="c4 lst-kix_list_2-0" start="2">
        <li class="c15 li-bullet-0"><strong>Access your dashboard:</strong><span class="c9">&nbsp;Once you have logged in, click &quot;Developer Dashboard&quot; on My Account page, or use this link: </span><span class="c6"><a class="c3" href="https://audioplugin.deals/developer-dashboard/">https://audioplugin.deals/developer-dashboard/</a></span></li>
    </ol>
    
    <p class="c1"><span class="c5">You&#39;re among the first to experience this exciting new feature! We&#39;d love your feedback. 
        Let us know if you encounter any issues or have suggestions for improvement.</span></p><p class="c0"><span class="c5">We&#39;re confident this dashboard will make your Audio Plugin Deals experience even smoother and more informative. Happy selling!</span></p><p class="c0"><span class="c5">Best,</span></p><p class="c0"><span class="c5">The Audio Plugin Deals Team</span></p>
    
      <?php
      
      $message = ob_get_contents();
      ob_end_clean();
      
      $email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' ); 
      
      $key = get_password_reset_key( $user );
      $reset_url = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
      $company_name = $user->first_name;
      
      $email['message'] = str_replace( 
        [ '{company_name}', '{reset_url}', '{username}'], 
        [ $company_name, $reset_url, $user->user_login ], // see attach_developer_taxonomy_to_new_user() for details 
        $message 
      );
    }
    
    return $email;
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
  public function attach_developer_taxonomy_to_new_user( array $email, object $user, $blogname ) {
   
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
    
	public function add_page_to_menu() {
    
		add_management_page(
			__( 'Developer Payout Dashboard' ),          // page title.
			__( 'Developer Payout Dashboard' ),          // menu title.
			'manage_options',
			'ddb-settings',			                // menu slug.
			array( $this, 'render_settings_page' )   // callback.
		);
  }
  
  
  public function do_action() {
    
    if ( isset( $_POST['ddb-button'] ) ) {
      
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
       /* 
        case self::ACTION_GENERATE_PAYOUT:
          self::generate_payroll( $_POST['report_type'], $_POST['source_timestamp'] );
        break;
        * 
        */
      }
    }
  }
  
  /**
   * Get list of developers: [ id => [ name, slug and settings ] ]
   * 
   * @return array
   */
  public static function get_developer_list_and_settings() {
    $developers     = get_terms( array( 'taxonomy' => 'developer', 'hide_empty' => false ) );
		$arr_developers = array();
    
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
   * 
   * option_name to search for in "wp_options" is in the format of 'aff_cron_results_XXXX', where XXX if $payroll_timestamp
   * 
   * $payout_category either 'paypal' or 'others'
   */
  public static function generate_payout_report() {
    
    $payout_category = $_GET['payout_category'];
    
    if ( in_array( $payout_category, array( 'paypal', 'others', 'summary' ) ) ) {
      
      $payroll_timestamp = $_GET['source_timestamp'];

      $filename = 'payout_report_' . $payout_category . '_' . date('Y-m-d', $payroll_timestamp );
      $format = 'csv';

      self::load_options();
      
      $global_profit_ratio = self::$option_values['global_default_profit_ratio'];
        
      $report_data = get_option( 'aff_cron_results_' . $payroll_timestamp );
      
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
    
    return $developers;
  }
  
  
	public function render_settings_page() {
    
    $action_results = '';
    
    if ( isset( $_POST['ddb-button'] ) ) {
			$action_results = $this->do_action();
		}
		
    $this->developers = self::get_developer_list_and_settings();
    
    self::load_options();
   
    $this->render_payout_report_form();
    $this->render_settings_form();
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
            
            ?>
            <tr>
              <td><a target="_blank" href="/developer/<?php echo $dev_data['slug']; ?>"><?php echo $dev_data['name']; ?></a></td>
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
  
  public function render_payout_report_form() {
    
    $available_summaries = self::get_available_report_summaries();
    
    $summary_names = array();
    
    foreach ( $available_summaries as $timestamp => $summary ) {
      
      $start = $summary['start_date'] ?? '';
      $end = $summary['end_date'] ?? '';
      $gen_date = date('Y-m-d H:i', $timestamp );
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
      
      
      <table class="ddb-global-table">
        <tbody>
          <?php self::display_field_set( $payout_field_set ); ?>
        </tbody>
      </table>
    
      
      <p class="submit">  
       <input type="submit" id="ddb-button-save" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_GENERATE_PAYOUT; ?>" />
      </p>
      
    </form>
    <?php 
  }

}