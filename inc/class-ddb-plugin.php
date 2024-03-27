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
    
    add_role('apd_developer', 'Product Developer', array(
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
        break;
        case self::ACTION_CLEAR:
          self::erase_developer_sales();
        break;
        case self::ACTION_CRONTEST:
          self::execute_cron();
        break;
        case self::ACTION_RANDOM:
          self::generate_random_sales();
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
   * @param array $dev_data
   */
  public static function get_developer_fields( int $dev_id, array $dev_data ) {
    
    $field_set = array(
			array(
				'name'    => "profit_ratio[$dev_id]",
				'type'    => 'dropdown',
				'label'   => 'Profit ratio',
				'default' => self::USE_GLOBAL_PROFIT_RATIO,
        'options' => self::$profit_ratio_options,
        'value'   => $dev_data['profit_ratio'] ?? ''
			),
      array(
				'name'    => "payment_method[$dev_id]",
				'type'    => 'dropdown',
				'label'   => 'Payment method',
				'default' => self::PM__NONE,
        'options' => self::$payment_methods_names,
        'value'   => $dev_data['payment_method'] ?? ''
			),
      array(
				'name'    => "paypal_address[$dev_id]",
				'type'    => 'text',
				'label'   => 'Paypal address',
				'default' => '',
        'value'   => $dev_data['paypal_address'] ?? ''
			),
      array(
				'name'    => "additional_notes[$dev_id]",
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
  
  
  
  // TODO finish
  public function update_developer_settings( $term_id ) {
    
    
    // TODO check field name and contents
    if ( isset( $_POST['developer_email'][$term_id] ) ) {
        update_term_meta($term_id, 'developer_email', sanitize_text_field( $_POST['developer_email'] ) );
    }
    if (isset($_POST['developer_payout'])) {
        update_term_meta($term_id, 'developer_method', $_POST['developer_payout']);
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
    //echo(' TTT $dev_sales<pre>' . print_r( $dev_sales, 1) . '</pre>');
    ?> 

		<h1><?php esc_html_e('APD Developers profiles & settings', 'ddb'); ?></h1>
    
    <form method="POST" >
      
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
       <input type="submit" id="ddb-button-erase" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_SAVE_OPTIONS; ?>" />
      </p>
      
    </form>
    <?php 
  }

}