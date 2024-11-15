<?php


class Ddb_Core {

  public const CUTOFF_DATE = '2024-04-16'; // dates earlier than this day are not allowed 
  
  
  // this data is provided by 'developer-top-sellers' plugin
  public const OPTION_NAME_FULL = 'developer_sales_full';
  
  public const DEV_ROLE_NAME = 'apd_developer';
  
	public static $prefix = 'ddb_';
	
  // Available file format for the generated reports
  public const FILE_FORMAT_XLSX   = 'xlsx';
  public const FILE_FORMAT_HTML   = 'html';
  public const FILE_FORMAT_CSV    = 'csv';
  
  // names of HTML fields in the form
  public const FIELD_DATE_START       = 'report_date_start';
  public const FIELD_DATE_END         = 'report_date_end';
  
  // name of the submit button that triggers POST form
  public const BUTTON_SUMBIT = 'ddb-button';
  
  // Actions triggered by buttons in backend area
  public const ACTION_SAVE_OPTIONS = 'Save settings';
  public const ACTION_GENERATE_PAYOUT = 'Generate payout report';
  public const ACTION_GENERATE_REPORT_XLSX = 'Generate sales report (XLSX file)';
  public const ACTION_GENERATE_REPORT_TABLE = 'Generate sales report (show table)';
	
	// Actions triggered by buttons in backend area, in regards to scheduled generation
	public const ACTION_START_CRON_REPORTS_GENERATION = 'Start generating reports';
	public const ACTION_RESTART_CRON_REPORTS_GENERATION = 'Re-generate stuck reports';
	public const ACTION_STOP_CRON_REPORTS_GENERATION = 'Stop generating reports';
  
  /**
   * Special value on developer profile indicating to use global profit ratio.
   * 
   * must be negative integer ( to distinguish it from legitimate custom profit ratios)
   */
  public const USE_GLOBAL_PROFIT_RATIO    = -1;
  
  public const PM__NONE                   = false;
  public const PM__BANK_CHECK             = 'check';
  public const PM__WISE                   = 'wise';
  public const PM__TRANSFER_WIRE          = 'wire';
  public const PM__TRANSFER_INTERNAL      = 'internal';
  public const PM__TRANSFER_ACH           = 'ach';
  public const PM__PAYPAL                 = 'paypal';
  public const PM__BOFA                   = 'bofa';
  
  public static $option_names = [
    'include_notes_into_report'     => false,
    'global_default_profit_ratio'   => 50
  ];
  
	public static $default_option_values = [
    'include_notes_into_report' => false
	];
    
  /**
   * List of settings used for each individual developer profile.
   * 
   * Format: [ setting name => default setting value ]
   * 
   * @var array
   */
	public static $dev_profile_settings = [
    'profit_ratio'        => self::USE_GLOBAL_PROFIT_RATIO,
    'paypal_address'      => '',
    'user_account'        => 0,
    'dropbox_folder_url'  => 0,
    'payment_method'      => 0, //self::PM__BANK_CHECK,
    'payment_currency'    => 'USD', // not used at the moment, but plugin could be expanded in the future
    'additional_notes'    => ''
	];
  

  /**
   * List of names for different payment methods
   * @var array
   */
  public static $payment_methods_names = [
    self::PM__NONE                        => 'Not set',
    self::PM__BANK_CHECK                  => 'Bank Check',
    self::PM__WISE                        => 'Wise',
    self::PM__TRANSFER_WIRE               => 'Bank Transfer (Wire)',
    self::PM__TRANSFER_INTERNAL           => 'Bank Transfer (Internal)',
    self::PM__TRANSFER_ACH                => 'Bank Transfer ACH',
    self::PM__PAYPAL                      => 'PayPal',
    self::PM__BOFA                        => 'Bank of America',
  ];
  
  public static $profit_ratio_options = [
    self::USE_GLOBAL_PROFIT_RATIO => 'Global', "0%" ,"1%", "2%", "3%", "4%", "5%", "6%", "7%", "8%", "9%", "10%", "11%", "12%", "13%", "14%", "15%", "16%", "17%", "18%", "19%", "20%", "21%", "22%", "23%", "24%", "25%", "26%", "27%", "28%", "29%", "30%", "31%", "32%", "33%", "34%", "35%", "36%", "37%", "38%", "39%", "40%", "41%", "42%", "43%", "44%", "45%", "46%", "47%", "48%", "49%", "50%", "51%", "52%", "53%", "54%", "55%", "56%", "57%", "58%", "59%", "60%", "61%", "62%", "63%", "64%", "65%", "66%", "67%", "68%", "69%", "70%", "71%", "72%", "73%", "74%", "75%", "76%", "77%", "78%", "79%", "80%", "81%", "82%", "83%", "84%", "85%", "86%", "87%", "88%", "89%", "90%", "91%", "92%", "93%", "94%", "95%", "96%", "97%", "98%", "99%", "100%"
  ];
  
  
  /**
   * Column sets and labels, for different report types
   * 
   * 
   * @var array
   */
	public static $report_columns = [
    'sales' => [
      'first_name'      => 'First name',
      'last_name'       => 'Last name',
      'email'           => 'Email',
      'address'         => 'Address',
      'date'            => 'Order date',
      'product_name'    => 'Product',
      'after_coupon'    => 'Paid price',
      'license_code'    => 'License code',
      'developer_refunded' => 'Refunded amount'
    ],
    'orders' => [
      'order_id'        => 'Order ID',
      'date'            => 'Order date',
      'full_name'       => 'Full name',
      'email'           => 'Email',
      'product_name'    => 'Product',
      'after_coupon'    => 'Paid price',
      'license_code'    => 'License code',
      'developer_refunded' => 'Refunded amount'
    ],
  ];
  
	public static $option_values = array();

	public static function init() {
		self::load_options();
	}

	public static function load_options() {
		$stored_options = get_option( 'ddb_options', array() );
    
		foreach ( self::$option_names as $option_name => $default_option_value ) {
			if ( isset( $stored_options[$option_name] ) ) {
				self::$option_values[$option_name] = $stored_options[$option_name];
			}
			else {
				self::$option_values[$option_name] = $default_option_value;
			}
		}
	}

	protected function display_messages( $error_messages, $messages ) {
		$out = '';
		if ( count( $error_messages ) ) {
			foreach ( $error_messages as $message ) {
				$out .= '<div class="notice-error settings-error notice is-dismissible"><p>'
				. '<strong>'
				. $message
				. '</strong></p>'
				. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
				. '</div>';
			}
		}
		if ( count( $messages ) ) {
			foreach ( $messages as $message ) {
				$out .= '<div class="notice-info notice is-dismissible"><p>'
				. '<strong>'
				. $message
				. '</strong></p>'
				. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
				. '</div>';
			}
		}

		return $out;
	}
  
  /**
   * Returns earliest allowed date in YYYY-MM-DD format
   * 
   * @return string
   */
  public static function get_earliest_allowed_date() {
    return self::CUTOFF_DATE;
  }
  
  /**
   * Returns today date in YYYY-MM-DD format
   * 
   * @return string
   */
  public static function get_today_date() {
    return date('Y-m-d');
  }
  
  /**
   * Finds developer taxonomy term by its id
   * 
   * @param integer $developer_id
   * @return object
   */
  public static function find_developer_term_by_id( int $developer_id ) {
  
    $developer_term = get_term_by( 'term_id', $developer_id, 'developer' );
    return $developer_term; 
  }
  
  /**
   * Finds developer taxonomy term (if any) linked to the current user account.
   * 
   * @return object
   */
  public static function find_current_developer_term() {
    return self::find_developer_term_by_user_id( get_current_user_id() );
  }
  
  /**
   * Finds corresponding developer taxonomy term linked to the provided user account.
   * 
   * @param integer $user_id
   * @return object
   */
  public static function find_developer_term_by_user_id( int $user_id ) {
  
    global $wpdb;
    $developer_term = false;
    
    if ( ! $user_id ) {
      return false;
    }
    
    $wp = $wpdb->prefix;
    $query_sql  = $wpdb->prepare( "SELECT term_id FROM {$wp}termmeta AS tm WHERE tm.`meta_key` = 'user_account' AND tm.`meta_value` = %s ", $user_id );
    
    $sql_results = $wpdb->get_row($query_sql, ARRAY_A);

    if ( is_array( $sql_results ) ) {
      $term_id = $sql_results['term_id'];
      $developer_term = self::find_developer_term_by_id( $term_id );
    }
    
    return $developer_term; 
  }
  
  /**
   * Finds payout settings for the specified developer 
   * 
   * @return array or false
   */
  public static function find_developer_payout_settings( object $developer_term ) {
    
    $developer_data = false;
    
    if ( is_object( $developer_term ) ) {
      
      $developer_data = array();
      
      foreach ( self::$dev_profile_settings as $setting_name => $default_value ) {
        $value =  get_term_meta( $developer_term->term_id, $setting_name, /* single? */ true );
        $developer_data[ $setting_name ] = $value ?: $default_value; // Elvis operator! 
      }
    }
    
    return $developer_data;
  }
  
  /**
   * Check the current user - does he have developoer role?
   * 
   * @return boolean
   */
  public static function is_authorized_developer() {
    
    $developer_term = self::find_current_developer_term();
    
    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
      return false;
    }
    return false;
  }
  
  /**
   * Checks user affiliate status which is set by WP Affiliates plugin
   * 
   * @param integer $user_id
   * @return boolean
   */
  public static function check_if_user_affiliate( int $user_id ) {
  
    global $wpdb;
    
    if ( ! $user_id ) {
      return false;
    }
    
    $wp = $wpdb->prefix;
    $query_sql  = $wpdb->prepare( "SELECT count(*) as count FROM `{$wp}affiliate_wp_affiliates` AS af WHERE af.`status` = 'active' AND af.`user_id` = %s ", $user_id );
    
    $sql_results = $wpdb->get_row($query_sql, ARRAY_A);

    if ( is_array( $sql_results ) && $sql_results['count'] > 0 ) {
      return true;
    }
    
    return false;
  }
  
  /**
   * Gathers daily sales data for the specified developer 
   * 
   * @param integer $developer_id
   * @return array
   */
  public static function get_developer_sales_data( int $developer_id, array $allowed_days ) {

		$arr_sales = array();
    
    $dev_sales = get_option( self::OPTION_NAME_FULL, array() );
      
    if ( is_array( $dev_sales ) && count( $dev_sales ) ) {
      
      foreach ( $allowed_days as $date ) {
        if (array_key_exists( $date, $dev_sales ) ) {
          $day_sales = $dev_sales[$date];
          $developer_sales = $day_sales[ $developer_id ]['developer'] ?? '---';
          $arr_sales[$date] = $developer_sales;
        }
        else {
          $arr_sales[$date] = false;
        }
      }
    }
    
    return $arr_sales;
  }
  
  /**
   * Gathers daily sales data for all products of the specified developer 
   * 
   * @param integer $developer_id
   * @return array
   */
  public static function get_product_sales_data( int $developer_id, array $allowed_days ) {

		$arr_sales = array();
    
    $dev_sales = get_option( self::OPTION_NAME_FULL, array() );
      
    if ( is_array( $dev_sales ) && count( $dev_sales ) ) {
      
      foreach ( $allowed_days as $date) {
        if (array_key_exists( $date, $dev_sales ) ) {
          $day_sales = $dev_sales[$date];
          $developer_sales = $day_sales[ $developer_id ]['products'] ?? false;
          $arr_sales[$date] = $developer_sales;
        }
        else {
          $arr_sales[$date] = false;
        }
      }
    }
    
    return $arr_sales;
  }
  
  
  /**
   * Prepares an array of date strings in Y-m-d format
   * 
   * 
   * @param int $n
   * @return array [ 'Y-m-d' ]
   */
  public static function generate_allowed_days() {
    
    $days = self::generate_last_n_days( 30 );
    
    $allowed_days = array(); 
    
    foreach ( $days as $date ) {
      if ( $date > self::CUTOFF_DATE ) { // restrict available dates
        $allowed_days[] = $date;
      }
    }
    
    return $allowed_days;
  }
  
  /**
   * Prepares an array of date strings in Y-m-d format
   * 
   * [ '2024-03-29', '2024-03-28', '2024-03-27', '2024-03-26', ... ]
   * 
   * @param int $n
   * @return array [ 'Y-m-d' ]
   */
  public static function generate_last_n_days( int $n ) {
    
    $days = array();
    
    for ($i = 0; $i < $n; $i++) {    
      $days[] = date('Y-m-d', strtotime("-$i days") );
    }
    
    return $days;
  }
  
  public static function get_days_header( $allowed_days ) {
    
    $out = '';
  
    foreach ( $allowed_days as $date ) {
      $out = "<th class='vertical'>$date</th>" . $out;  
    }
    
    return $out;
  }
  
  public static function render_product_sales( $dev_sales, $product_id, $allowed_days ) {
    $out = '';
    
    $total_value = 0;
    foreach ( $allowed_days as $date ) {
        
      $value = '--';

      if ( isset( $dev_sales[$date]) && is_array($dev_sales[$date]) ) {
        $value = $dev_sales[$date][$product_id] ?? '---' ;
        $total_value += $dev_sales[$date][$product_id] ?? 0;
      }

      $out = "<td class='$date'>" . $value . "</td>" . $out;
    }
    
    
    $out = "<td class='total'>" . $total_value . "</td>" . $out;
    
    return $out;
  }
  
  /**
   * Finds all previously saved report summary entries in the database
   * 
   * Here is a separate plugin 'Woocommerce Affiliate Reports' which saves report summary data in 'wp_options' table
   * with option_key in the format 'aff_cron_results_XXXX', where XXX is a timestamp.
   * 
   */
  public static function get_available_report_summaries() {
    global $wpdb;
    $wp = $wpdb->prefix;
    
    $summary_key_prefix = 'ddb_cron_results_';
    
    $query_sql = "SELECT o.`option_name` as name, o.`option_value` AS value from {$wp}options AS o
        WHERE o.`option_name` LIKE '%{$summary_key_prefix}%' ORDER BY o.`option_name` DESC ";
        
    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );

    $report_summaries = array();
    
    foreach ( $sql_results as $row ) {
      
      $summary_name     = str_replace( $summary_key_prefix, '', $row['name'] );
      $summary_content   = unserialize($row['value']);
      
      $report_summaries[$summary_name] = $summary_content;
    }
    
    return $report_summaries;
  }
  
  
  
  /**
   * Finds all active WooCommerce products linked to a developer
   * 
   * returns array[ product_id => product_name ]
   * 
   * @global object $wpdb
   * @return array
   */
  public static function get_all_developer_products() {
    global $wpdb;
    $wp = $wpdb->prefix;
    
    $query_sql = "SELECT p.ID AS pid, p.post_title AS name from {$wp}posts AS p
        JOIN {$wp}term_relationships tr     ON p.ID = tr.object_id
        JOIN {$wp}term_taxonomy tt          ON tr.term_taxonomy_id = tt.term_taxonomy_id
        JOIN {$wp}terms t                   ON tt.term_id = t.term_id
				WHERE p.`post_status` = 'publish' AND p.`post_type` = 'product'
        AND tt.taxonomy = 'developer' ";
          
    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );

    $products = array();
    
    foreach ( $sql_results as $row ) {
      
      $product_id     = $row['pid'];
      $product_name   = $row['name'];
      
      $products[$product_id] = $product_name;
    }
    
    return $products;
  }
  
  
  /**
   * Finds all active WooCommerce products marked as "deal products"
   * 
   * returns array[ product_id => product_name ]
   * 
   * @global object $wpdb
   * @return array
   */
  public static function get_all_deal_products() {
    global $wpdb;
    $wp = $wpdb->prefix;
    
    $query_sql = "SELECT p.ID AS pid, p.post_title AS name from {$wp}posts AS p
        JOIN {$wp}postmeta AS pm                 ON pm.post_id = p.ID
				WHERE p.`post_status` = 'publish' AND p.`post_type` = 'product'
        AND pm.`meta_key` = '_product_big_deal' AND pm.`meta_value` = 'yes' ";
          
    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );

    $products = array();
    
    foreach ( $sql_results as $row ) {
      
      $product_id     = $row['pid'];
      $product_name   = $row['name'];
      
      $products[$product_id] = $product_name;
    }
    
    return $products;
  }

  
  /**
   * Finds all active WooCommerce products for the specific developer
   * 
   * returns array[ product_id => product_name ]
   * 
   * @global object $wpdb
   * @return array
   */
  public static function get_developer_products( string $developer_slug ) {
    global $wpdb;
    $wp = $wpdb->prefix;
    
    $query_sql = "SELECT p.ID AS pid, p.post_title AS name from {$wp}posts AS p
        JOIN {$wp}term_relationships tr     ON p.ID = tr.object_id
        JOIN {$wp}term_taxonomy tt          ON tr.term_taxonomy_id = tt.term_taxonomy_id
        JOIN {$wp}terms t                   ON tt.term_id = t.term_id
				WHERE p.`post_status` = 'publish' AND p.`post_type` = 'product'
        AND tt.taxonomy = 'developer' AND t.slug = '{$developer_slug}' ";
        
    /*
    SELECT p.ID, p.post_title, p.post_type
FROM wp_posts p
JOIN wp_term_relationships tr ON p.ID = tr.object_id
JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'your_post_type'
  AND tt.taxonomy = 'category'
  AND t.slug IN ('category1', 'category2', 'category3');
     * 
     */
        
    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );

    $developer_products = array();
    
    foreach ( $sql_results as $row ) {
      
      $product_id     = $row['pid'];
      $product_name   = $row['name'];
      
      $developer_products[$product_id] = $product_name;
    }
    
    return $developer_products;
  }

  
  /**
   * Returns HTML table rows each containing field, field name, and field description
   * 
   * @param array $field_set 
   * @return string HTML
   */
	public static function render_fields_row( $field_set ) {
    
    $out = '';
    
		foreach ( $field_set as $field ) {
			
			$value = $field['value'];
			
			if ( ( ! $value) && ( $field['type'] != 'checkbox' ) ) {
				$value = $field['default'] ?? '';
			}
			
			$out .= self::display_field_in_row( $field, $value );
		}
    
    return $out;
	}
	
	/**
	 * Generates HTML code for input row in table
	 * @param array $field
	 * @param array $value
   * @return string HTML
	 */
	public static function display_field_in_row($field, $value) {
    
		$label = $field['label']; // $label = __($field['label'], DDB_TEXT_DOMAIN);
		
		$value = htmlspecialchars($value);
		$field['id'] = str_replace( '_', '-', $field['name'] );
		
		// 1. Make HTML for input
		switch ($field['type']) {
			case 'text':
				$input_HTML = self::make_text_field( $field, $value );
				break;
			case 'dropdown':
				$input_HTML = self::make_dropdown_field( $field, $value );
				break;
			case 'textarea':
				$input_HTML = self::make_textarea_field( $field, $value );
				break;
			case 'checkbox':
				$input_HTML = self::make_checkbox_field( $field, $value );
				break;
			case 'hidden':
				$input_HTML = self::make_hidden_field( $field, $value );
				break;
			default:
				$input_HTML = '[Unknown field type "' . $field['type'] . '" ]';
		}
		
		
		// 2. Make HTML for table cell
		switch ( $field['type'] ) {
			case 'hidden':
				$table_cell_html = <<<EOT
		<td class="col-hidden" style="display:none;" >{$input_HTML}</td>
EOT;
				break;
			case 'text':
			case 'textarea':
			case 'checkbox':
			default:
				$table_cell_html = <<<EOT
		<td>{$input_HTML}</td>
EOT;
				
		}

		return $table_cell_html;
	}
  
  
  
	/**
	 * Generates HTML code with TR rows containing specified field set
   * 
	 * @param array $field
	 * @param mixed $value
   * @return string HTML
	 */
	public static function display_field_set( $field_set ) {
		foreach ( $field_set as $field ) {

			$value = $field['value'] ?? false;
			
      $field['id'] = str_replace( '_', '-', $field['name'] );

			echo self::make_field( $field, $value );
		}
	}
	
  
	/**
	 * Generates HTML code with TR row containing specified field input
   * 
	 * @param array $field
	 * @param mixed $value
   * @return string HTML
	 */
	public static function make_field( $field, $value ) {
		$label = $field['label'];
		
		if ( ! isset( $field['style'] ) ) {
			$field['style'] = '';
		}
		
		// 1. Make HTML for input
		switch ( $field['type'] ) {
			case 'checkbox':
				$input_html = self::make_checkbox_field( $field, $value );
				break;
			case 'text':
				$input_html = self::make_text_field( $field, $value );
				break;
			case 'date':
				$input_html = self::make_date_field( $field, $value );
				break;
			case 'dropdown':
				$input_html = self::make_dropdown_field( $field, $value );
				break;
			case 'textarea':
				$input_html = self::make_textarea_field( $field, $value );
				break;
			case 'hidden':
				$input_html = self::make_hidden_field( $field, $value );
				break;
			default:
				$input_html = '[Unknown field type "' . $field['type'] . '" ]';
		}
		
		if (isset($field['display'])) {
			$display = $field['display'] ? 'table-row' : 'none';
		}
		else {
			$display = 'table-row';
		}
		
		// 2. Make HTML for table row
		switch ($field['type']) {
			case 'checkbox':
				$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td colspan="3" class="col-checkbox">{$input_html}<label for="ddb_{$field['id']}">$label</label></td>
		</tr>
EOT;
				break;
			case 'hidden':
				$table_row_html = <<<EOT
		<tr style="display:none" >
			<td colspan="3" class="col-hidden">{$input_html}</td>
		</tr>
EOT;
				break;
			case 'dropdown':
			case 'text':
			case 'textarea':
			default:
				if (isset($field['description']) && $field['description']) {
					$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td class="col-name" style="{$field['style']}"><label for="ddb_{$field['id']}">$label</label></td>
			<td class="col-input">{$input_html}</td>
			<td class="col-info">
				{$field['description']}
			</td>
		</tr>
EOT;
				}
				else {
				$table_row_html = <<<EOT
		<tr style="display:{$display}" >
			<td class="col-name" style="{$field['style']}"><label for="ddb_{$field['id']}">$label</label></td>
			<td class="col-input">{$input_html}</td>
			<td class="col-info"></td>
		</tr>
EOT;
				}
		}

		
		return $table_row_html;
	}
	

	/**
	 * Generates HTML code for hidden input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_hidden_field($field, $value) {
		$out = <<<EOT
			<input type="hidden" id="ddb_{$field['id']}" name="{$field['name']}" value="{$value}">
EOT;
		return $out;
	}	
	
	/**
	 * Generates HTML code for text field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_text_field($field, $value) {
		$out = <<<EOT
			<input type="text" id="ddb_{$field['id']}" name="{$field['name']}" value="{$value}" class="ddb-text-field">
EOT;
		return $out;
	}
  
	/**
	 * Generates HTML code for date field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_date_field($field, $value) {
    
    $min = $field['min'] ?? '2023-01-01';
    
		$out = <<<EOT
			<input type="date" id="ddb_{$field['id']}" name="{$field['name']}" value="{$value}" min="{$min}" class="ddb-date-field">
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for textarea input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_textarea_field($field, $value) {
		$out = <<<EOT
			<textarea id="ddb_{$field['id']}" name="{$field['name']}" cols="{$field['cols']}" rows="{$field['rows']}" value="">{$value}</textarea>
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for dropdown list input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_dropdown_field($field, $value) {
    
    $autocomplete = $field['autocomplete'] ?? false;
    
    $class = $autocomplete ? 'ddb-autocomplete' : '';
    
    $out = "<select class='$class' name='{$field['name']}' id='ddb_{$field['id']}' >";

		foreach ($field['options'] as $optionValue => $optionName) {
			$selected = ((string)$value == (string)$optionValue) ? 'selected="selected"' : '';
			$out .= '<option '. $selected .' value="' . $optionValue . '">' . $optionName .'</option>';
		}
		
		$out .= '</select>';
		return $out;
	}
	
	
	/**
	 * Generates HTML code for checkbox 
	 * @param array $field
	 */
	public static function make_checkbox_field($field, $value) {
		$chkboxValue = $value ? 'checked="checked"' : '';
		$out = <<<EOT
			<input type="checkbox" id="ddb_{$field['id']}" name="{$field['name']}" {$chkboxValue} value="1" class="ddb-checkbox-field"/>
EOT;
		return $out;
	}	
	
	public static function create_url_for_reports( $params, $timestamp = false ) {
    
    if ( ! $timestamp ) {
			$timestamp = time();
		}
		
    $folder_name = $timestamp . '-from-' . $params['start_date'] . '-to-' . $params['end_date'];
    
    $upload = wp_upload_dir();
    
    $folder_url = trailingslashit( $upload['url'] ) . 'ddb-reports/' . $folder_name;
		
		return $folder_url;
	}
	
	public static function create_folder_for_reports( $params, $timestamp = false ) {
    
    if ( ! $timestamp ) {
			$timestamp = time();
		}
		
		$folder_name = $timestamp . '-from-' . $params['start_date'] . '-to-' . $params['end_date'];
		
    $upload = wp_upload_dir();
    $full_path = trailingslashit( $upload['path'] ) . 'ddb-reports/' . $folder_name;
    
    wp_mkdir_p( $full_path );

		// remove all files in that folder, if any
		$files = glob($full_path . '/*'); // get all file names
		
		foreach( $files as $file ) { 
			if ( is_file($file) ) {
				unlink($file); 
			}
		}

		return $full_path;
	}
	
	public static function log($data) {

		$filename = pathinfo( __FILE__, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR .'log.txt';
		if ( isset($_REQUEST['ddb_log_to_screen']) && $_REQUEST['ddb_log_to_screen'] == 1 ) {
			echo( 'log::<pre>' . print_r($data, 1) . '</pre>' );
		}
		else {
			file_put_contents($filename, date("Y-m-d H:i:s") . " | " . print_r($data,1) . "\r\n\r\n", FILE_APPEND);
		}
	}
	
  /**
	 * Write into WooCommerce log. 
	 * 
	 * @param string $message
	 * @param array $data
	 */
	public static function wc_log(string $message, array $data = array() ) {

		$data['source'] = 'developer-dashboard';

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->info(
							$message,
							$data
			);
		}
	}
  
  // code taken from https://www.php.net/manual/en/function.fputcsv.php
  public static function make_csv_line( array $fields) : string {
    
    $f = fopen('php://memory', 'r+');
    if (fputcsv($f, $fields) === false) {
        return false;
    }
    rewind($f);
    $csv_line = stream_get_contents($f);
    return rtrim($csv_line) . "\r\n";
  }
  
  
}
