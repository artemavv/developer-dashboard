<?php


class Ddb_Core {

  public const CUTOFF_DATE = '2024-03-12';
  
  public const OPTION_NAME_FULL = 'developer_sales_full';
  
  public const DEV_ROLE_NAME = 'apd_developer';
  
	public static $prefix = 'ddb_';
	
  
  public const ACTION_SAVE_OPTIONS = 'Save settings';
  /**
   * Special value om developer profile indicating to use global profit ratio.
   * 
   * must be negative integer ( to distinguish it from legitimate custom profir ratios)
   */
  public const USE_GLOBAL_PROFIT_RATIO    = -1;
  
  public const PM__NONE                   = false;
  public const PM__BANK_CHECK             = 'check';
  public const PM__WISE                   = 'wise';
  public const PM__TRANSFER_WIRE          = 'wire';
  public const PM__TRANSFER_INTERNAL      = 'internal';
  public const PM__TRANSFER_ACH           = 'ach';
  public const PM__PAYPAL                 = 'paypal';
  

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
  ];
  
  public static $profit_ratio_options = [
    self::USE_GLOBAL_PROFIT_RATIO => 'Global', "0%" ,"1%", "2%", "3%", "4%", "5%", "6%", "7%", "8%", "9%", "10%", "11%", "12%", "13%", "14%", "15%", "16%", "17%", "18%", "19%", "20%", "21%", "22%", "23%", "24%", "25%", "26%", "27%", "28%", "29%", "30%", "31%", "32%", "33%", "34%", "35%", "36%", "37%", "38%", "39%", "40%", "41%", "42%", "43%", "44%", "45%", "46%", "47%", "48%", "49%", "50%", "51%", "52%", "53%", "54%", "55%", "56%", "57%", "58%", "59%", "60%", "61%", "62%", "63%", "64%", "65%", "66%", "67%", "68%", "69%", "70%", "71%", "72%", "73%", "74%", "75%", "76%", "77%", "78%", "79%", "80%", "81%", "82%", "83%", "84%", "85%", "86%", "87%", "88%", "89%", "90%", "91%", "92%", "93%", "94%", "95%", "96%", "97%", "98%", "99%", "100%"
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
   * Finds corresponding developer taxonomy term linked to the provided user account.
   * 
   * @param integer $user_id
   * @return object
   */
  public static function find_developer_term_by_user_id( int $user_id ) {
  
    global $wpdb;
    $developer_term = false;
    
    $wp = $wpdb->prefix;
    $query_sql  = $wpdb->prepare( "SELECT term_id FROM {$wp}termmeta AS tm WHERE tm.`meta_key` = 'user_account' AND tm.`meta_value` = %s ", $user_id );
    
    $sql_results = $wpdb->get_row($query_sql, ARRAY_A);

    if ( is_array( $sql_results ) ) {
      $term_id = $sql_results['term_id'];
      $developer_term = get_term_by( 'term_id', $term_id, 'developer' );
    }
    
    return $developer_term; 
  }
  
  /**
   * Gathers daily sales data for the specified developer 
   * 
   * @param integer $developer_id
   * @return array
   */
  public static function get_developer_sales_data( int $developer_id ) {

		$arr_sales = array();
    
    $dev_sales = get_option( self::OPTION_NAME_FULL, array() );
      
    if ( is_array( $dev_sales ) && count( $dev_sales ) ) {
      
      $actual_dates = self::generate_last_n_days( 30 );
      
      foreach ( $dev_sales as $date => $day_sales ) {
        if ( in_array( $date, $actual_dates ) && $date > self::CUTOFF_DATE ) {
          
          $developer_sales = $day_sales[ $developer_id ]['developer'] ?? '---';
          $arr_sales[$date] = $developer_sales;
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
  public static function get_product_sales_data( int $developer_id ) {

		$arr_sales = array();
    
    $dev_sales = get_option( self::OPTION_NAME_FULL, array() );
      
    if ( is_array( $dev_sales ) && count( $dev_sales ) ) {
      
      $actual_dates = self::generate_last_n_days( 30 );
      
      foreach ( $dev_sales as $date => $day_sales ) {
        if ( in_array( $date, $actual_dates ) && $date > self::CUTOFF_DATE ) {
          
          $developer_sales = $day_sales[ $developer_id ]['products'] ?? false;
          $arr_sales[$date] = $developer_sales;
        }
      }
    }
    
    return $arr_sales;
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
  
  public static function get_allowed_days_header() {
    
    $out = '';
    // Loop through the last 30 days
    for ($i = 0; $i < 30; $i++) {
        
        $date = date('Y-m-d', strtotime("-$i days") );
        
        if ( $date > self::CUTOFF_DATE ) { // restrict available dates
          $out = "<th>$date</th>" . $out;
        }
    }
    
    return $out;
  }
  
  public static function render_allowed_product_sales( $dev_sales, $dev_id, $product_id ) {
    $out = '';
    
    $total_value = 0;
    for ($i = 0; $i < 30; $i++) {
        
        $date = date('Y-m-d', strtotime("-$i days") );
        
        if ( $date > self::CUTOFF_DATE ) { // restrict available dates

          $value = '--';

          if ( isset( $dev_sales[$date]) && is_array($dev_sales[$date]) ) {
            $value = $dev_sales[$date][$dev_id]['products'][$product_id] ?? '---' ;
            $total_value += $dev_sales[$date][$dev_id]['products'][$product_id] ?? 0;
          }
        }
        
        $out = "<td>" . $value . "</td>" . $out;
    }
    
    
    $out = "<td class='total'>" . $total_value . "</td>" . $out;
    
    return $out;
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

			$value = false;

			if (isset($field['value'])) {
				$value = $field['value'];
			}
      
      $field['id'] = str_replace( '_', '-', $field['name'] );

			if ( ( ! $value ) && ( !in_array( $field['type'], array( 'checkbox' ) ) ) ) {
				$value = isset( $field['default'] ) ? $field['default'] : '';
			}
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
			<td class="col-name" style="{$field['style']}"><label for="wppn_{$field['id']}">$label</label></td>
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
		$out = '<select name="' . $field['name'] . '" id="ddb_' . $field['id'] . '">';

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
	
	public static function log($data) {

		$filename = pathinfo( __FILE__, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR .'log.txt';
		if ( isset($_REQUEST['ddb_log_to_screen']) && $_REQUEST['ddb_log_to_screen'] == 1 ) {
			echo( 'log::<pre>' . print_r($data, 1) . '</pre>' );
		}
		else {
			file_put_contents($filename, date("Y-m-d H:i:s") . " | " . print_r($data,1) . "\r\n\r\n", FILE_APPEND);
		}
	}
  
}
