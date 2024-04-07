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
      add_action('admin_enqueue_scripts', array($this, 'register_admin_styles_and_scripts'));
    }
    
		add_action( 'admin_menu', array( $this, 'add_page_to_menu' ) );
    
    add_role( self::DEV_ROLE_NAME, 'Product Developer', array(
      'read'              => true,
      'create_posts'      => false,
      'edit_posts'        => false,
      'edit_others_posts' => false,
      'publish_posts'     => false,
      'manage_categories' => false,
    ));
    
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
	}
  
  public function register_admin_styles_and_scripts() {
    $file_src = plugins_url( 'css/ddb-admin.css', $this->plugin_root );
    wp_enqueue_style( 'ddb-admin', $file_src, array(), DDB_VERSION );
  }
  
    
	public function add_page_to_menu() {
    
		add_management_page(
			__( 'Developer Payout Settings' ),          // page title.
			__( 'Developer Payout Settings' ),          // menu title.
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
        case self::ACTION_CLEAR:
          self::erase_developer_sales();
        break;
        
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
  
	public function render_settings_page() {
    
    $action_results = '';
    
    if ( isset( $_POST['ddb-button'] ) ) {
			$action_results = $this->do_action();
		}
		
    $this->developers = self::get_developer_list_and_settings();
    
    self::load_options();
    
    //echo(' TTT <pre>' . print_r( $this->developers, 1) . '</pre>');
    
    
    $global_field_set = array(
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
    
    
    $a = do_shortcode('[developer_dashboard user_id="106996"]');
    
    echo $a;
    ?> 

    <form method="POST" >
    
      <h2><?php esc_html_e('Global Developer settings', 'ddb'); ?></h2>
      
      
      <table class="ddb-global-table">
        <tbody>
          <?php self::display_field_set( $global_field_set ); ?>
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

}