<?php


class Ddb_Frontend extends Ddb_Core {

  // Actions triggered by buttons in frontend area
  public const ACTION_GENERATE_SALES_REPORT = 'Show sales report';
  public const ACTION_SHOW_ORDERS_REPORT = 'Show report';
  public const ACTION_DOWNLOAD_ORDERS_REPORT = 'Export report to Excel';
  
  /**
   * Handler for the "display_content_for_developers_only" shortcode
   * 
   * @param array $atts
   * @param string $content
   * @return string html
   */
  public static function display_developer_content( $atts, string $content = '' ) {
    
    $user = wp_get_current_user();

    // Check whether that user exists and is actually a Product Developer
    if ( $user && is_array( $user->roles ) && in_array( self::DEV_ROLE_NAME, $user->roles ) ) {
      $developer_term = self::find_developer_term_by_user_id( $user->ID );

      if ( is_object( $developer_term ) ) {
        return do_shortcode( $content ); // show content placed inside the shortcode
      }
    }
    
    return false;
  }
  
  
  /**
   * Handler for the "display_content_for_affiliates_only" shortcode
   * 
   * @param array $atts
   * @param string $content
   * @return string html
   */
  public static function display_affiliate_content( $atts, string $content = '' ) {
    
    $user = wp_get_current_user();

    // Check whether that user exists and is affiliate
    if ( $user ) {
      
      $is_affiliate = self::check_if_user_affiliate( $user->ID );
      
      if ( $is_affiliate ) {
        return do_shortcode( $content ); // show content placed inside the shortcode
      }
    }
    
    return false;
  }
  
  /**
   * Handler for "developer_dashboard" shortcode
   * 
   * Renders the table with developer sales.
   * 
   * 
   * @param array $atts
   * @return string HTML 
   */
  public static function render_developer_dashboard( $atts ) {
    
    $out = '<h3>Not authorized</h3>';
    
    $developer_term = false;
    
    $input_fields = [
      'user_id'      => 0,
      'title'        => 'Developer Dashboard',
    ];
    
    extract( shortcode_atts( $input_fields, $atts ) );
    
    if ( $user_id != 0 ) {
      $user = get_user_by( 'id', $user_id );
    }
    else {
      $user = wp_get_current_user();
    }
    
    // Check whether that user exists and is actually a Product Developer
    if ( $user && is_array( $user->roles ) && in_array( self::DEV_ROLE_NAME, $user->roles ) ) {
      $developer_term = self::find_developer_term_by_user_id( $user->ID );
    }
    
    
    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {

      self::load_options();
    
      $out = '<div id="developer-dashboard">';
      
      if ( filter_input( INPUT_POST, self::BUTTON_SUMBIT ) ) {
        $out .= '<h2>Orders report</h2>';
      }
      else {
        $out .= '<h2>Most Recent Orders</h2>';
      }
      
      $out .= self::render_orders_report_form_and_results( $developer_term );
      
      
      $payout_settings = self::find_developer_payout_settings( $developer_term );
      
      if ( is_array($payout_settings) && count($payout_settings) ) {
        $out .= self::render_payout_settings( $payout_settings );
      }
      
      $out .= '</div>';
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
  
  public static function do_action() {
    
    $out = '';
    
    $action = filter_input( INPUT_POST, self::BUTTON_SUMBIT );
      
    $developer_term = self::find_current_developer_term();

    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {

      $start_date = filter_input( INPUT_POST, self::FIELD_DATE_START ) ?: false;
      $end_date = filter_input( INPUT_POST, self::FIELD_DATE_END ) ?: false;

      switch ( $action ) {
        case self::ACTION_GENERATE_SALES_REPORT:
          $out = self::validate_input_and_generate_report( $developer_term, $start_date, $end_date, 'sales' );
        break;
        case self::ACTION_SHOW_ORDERS_REPORT:
          $out = self::validate_input_and_generate_report( $developer_term, $start_date, $end_date, 'orders' );
        break;
        case self::ACTION_DOWNLOAD_ORDERS_REPORT:

          // in the usual case this action will be performed by 'ddb_generate_excel_report' function in 'init' hook
          // which outputs XLSX file and terminates PHP script. 
          // If PHP script is still running then ddb_generate_excel_report() detected some invalid inputs or found no orders.
          // therefore we need to run additional checks and inform user about invalid inputs or empty generated report.
          $validation_failed = self::validate_input( $start_date, $end_date );

          if ( $validation_failed === false ) { // all inputs are valid, then it must be an empty generated report
            $out = "<h3 style='color:darkred;'>No orders found in the specified date range ( from $start_date to $end_date )</h3>";
          }
          else {
            $out = $validation_failed;
          }
        break;
      }
    }
    else {
      $out = '<h3>Not Authorized to view this report</h3>';
    }
    
    return $out;
  }
  
  /**
   * Check the form input and returns HTML with report of requested type+
   * 
   * @param object $developer_term WP_Term object
   * @param string $start_date date in Y-m-d format
   * @param string $end_date date in Y-m-d format
   * @param string $report_type either 'sales' or 'orders'
   * @return string report HTML
   */
  public static function validate_input_and_generate_report( object $developer_term, string $start_date, string $end_date, string $report_type = 'sales') {
    
    $out = ''; 
    
    $validation_failed = self::validate_input( $start_date, $end_date );
    
    if ( $validation_failed === false ) {
      if ( $report_type == 'sales' || $report_type == 'orders' ) {
        
        $report_data = array();
    
        $paid_order_ids = Ddb_Report_Generator::get_paid_order_ids( $start_date, $end_date, $developer_term->name );

        foreach ( $paid_order_ids as $order_id ) {
          $order_lines = Ddb_Report_Generator::get_single_order_info( $order_id, $developer_term );

          if ( $order_lines ) {
            $report_data = array_merge( $report_data, $order_lines );
          }
        }
    
        if ( is_array($report_data) && count($report_data) ) {
          
          $out = "<h3>Search result for $report_type between $start_date and $end_date</h3>";
          
          $out .= self::render_orders_list( $report_data, $report_type );
          $out .= self::render_orders_summary( $report_data, $developer_term );
          $out .= self::generate_csv_to_be_copied( $report_data, $report_type );
        }
        else {
          $out = "<h3 style='color:darkred;'>No $report_type found in the specified date range ( from $start_date to $end_date )</h3>";
        }
      }
    }
    else {
     $out = $validation_failed; // show error messages
    }
    
    return $out;
  }
  
  /**
   * Returns false if no errors found in the entered dates.
   * 
   * Returns html-formatted error message otherwise
   * 
   * @param string $start_date
   * @param string $end_date
   * @return boolean|string
   */
  public static function validate_input( $start_date, $end_date ) {
    
    $valid_start_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) && self::validate_date( $start_date );
    $valid_end_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) && self::validate_date( $end_date );
    
    
    if ( $start_date && $end_date && $valid_start_date && $valid_end_date && $start_date <= $end_date ) {
      return false;
    }
    else {
      
      $out = '';
      
      // html5 date input produces YYYY-MM-DD dates, 
      // but user may enter date in other formats (depending on browser and OS settings )
      // therefore we have to be vague about the correct format
      
      if ( ! $valid_start_date ) {
        $out .= '<h3 style="color:red;">Start date is not in valid format</h3>'; 
      }
      if ( ! $valid_end_date ) {
        $out .= '<h3 style="color:red;">End date is not in valid format</h3>';
      }
      if ( $start_date > $end_date ) {
        $out .= '<h3 style="color:orange;">Please make sure that the start date is earlier or equal to the end date</h3>';
      }
    }
    
    return $out;
  }
  
  public static function validate_date($date, $format = 'Y-m-d' ) {
    $d = DateTime::createFromFormat( $format, $date );
    return $d && $d->format( $format ) == $date;
  }
  
  /**
   * Renders the list of payout settings
   * 
   * @param array $payout_settings
   * @return string
   */
  public static function render_payout_settings( array $payout_settings ) {
    
    $out = '';

    // in the DB these values are stored as fractions ( 0.12 for 12% profit ratio)
    // need to multiply by 100 to show values as percents

    if ( $payout_settings['profit_ratio'] == self::USE_GLOBAL_PROFIT_RATIO ) {
      $global_profit_ratio = self::$option_values['global_default_profit_ratio'];
      $actual_ratio = $global_profit_ratio * 100;
    }
    else {
      $actual_ratio = $payout_settings['profit_ratio'] * 100;
    }

    $out .= '<div style="display:inline-block; width: 40%"><h2>Payout settings</h2>';
    
    $out .= '<p><strong>Profit ratio</strong>: ' . ( $actual_ratio ) . '%</p>';
    $out .= '<p><strong>Payment method</strong>: ' . ( self::$payment_methods_names[$payout_settings['payment_method']] ?? '?' ) . '</p>';

    if ( $payout_settings['payment_method'] == self::PM__PAYPAL && $payout_settings['paypal_address'] ) {
      $out .= '<p><strong>PayPal address</strong>: ' . $payout_settings['paypal_address'] . '%</p>';
    }
    
    $out .= '</div>';

    if ( $payout_settings['dropbox_folder_url'] ) {
      
      $out .= '<div style="display:inline-block; width: 40%"><h2>Reports Archive</h2>';
      
      $out .= '<p><strong><a target="_blank" href="' . $payout_settings['dropbox_folder_url'] . '">Download from Dropbox folder</a></strong>'
        . '<br><small>Reports in archives include sales that occurred before April 16, 2024</small></p>';
      $out .= '</div>';
    }
    
    
    return $out;
  }
  
  public static function render_last_n_orders( object $developer_term ) {
    
    $out = '';
    
    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
      
      $report_data = array();

      $last_order_ids = Ddb_Report_Generator::get_last_order_ids( $developer_term->name );

      foreach ($last_order_ids as $order_id ) {
        $order_lines = Ddb_Report_Generator::get_single_order_info( $order_id, $developer_term );

        if ( $order_lines ) {
          $report_data = array_merge( $report_data, $order_lines );
        }
      }
        
      if ( is_array($report_data) && count($report_data) ) {
        
        $num = count($report_data);
        
        //$out = "<h3>Last {$num} orders with products by {$developer_term->name}</h3>";
        $out = self::render_orders_list( $report_data, 'orders' );
      }
      else {
        $out = "<h3>Found no orders with products by {$developer_term->name}</h3>";
      }
    }

    return $out;
  }
  
  /**
   * Shows list of orders and report generation form
   * 
   * @param object $developer_term
   * @return string html
   */
  public static function render_orders_report_form_and_results( object $developer_term ) {
    
    ob_start();
    
    if ( filter_input( INPUT_POST, self::BUTTON_SUMBIT ) ) {
      $action_results = self::do_action(); // generate orders report if requested by a user
    }
    else {
      $action_results = self::render_last_n_orders( $developer_term );
    }
    
    $start_date   = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_START ) ?? date( 'Y-m-d', strtotime("-7 days") ) );
    $end_date     = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_END ) ?? self::get_today_date() );
    
    $report_field_set = array(
      array(
				'name'        => "report_date_start",
				'type'        => 'date',
				'label'       => 'Start date',
				'default'     => '',
        'min'         => self::get_earliest_allowed_date(),
        'value'       => $start_date,
        'description' => '' //'Earliest allowed date is ' . date(' F d', strtotime( self::get_earliest_allowed_date() ) )
			),
      array(
				'name'        => "report_date_end",
				'type'        => 'date',
				'label'       => 'End date',
				'default'     => '',
        'min'         => self::get_earliest_allowed_date(),
        'value'       => $end_date,
        'description' => ''
			),
		);

    echo $action_results;
    
    ?> 

    <h3>Create a new report</h3>
    <form method="POST" >
      
      <table class="ddb-report-form-table">
        <tbody>
          <?php self::display_field_set( $report_field_set ); ?>
        </tbody>
      </table>
      
      <p class="submit">  
       <input type="submit" id="ddb-button-generate" name="ddb-button" class="button button-primary" 
              value="<?php echo self::ACTION_SHOW_ORDERS_REPORT; ?>" style="background-color: bisque;" />
       <input type="submit" id="ddb-button-generate" name="ddb-button" class="button button-primary" 
              value="<?php echo self::ACTION_DOWNLOAD_ORDERS_REPORT; ?>" style="background-color: gainsboro;"/>
      </p>
      
    </form>
    <?php 
    $out = ob_get_contents();
		ob_end_clean();

    return $out; 
  }
  
  public static function render_total_daily_sales( $sales_data ) {
    
    ob_start();
    ?>
    
    <table class="sales-table">
      <thead>
        <th>Day</th>
        <th>Sales</th>
      </thead>
      <tbody>

      <?php foreach ( $sales_data as $day => $day_sales ): ?>
        <tr>
          <td><?php echo $day; ?></td>
          <td><?php echo $day_sales; ?></td>
        </tr>
      <?php endforeach; ?>

      <tbody>
    </table>
      
		<?php 
		$out = ob_get_contents();
		ob_end_clean();

    return $out; 
  }
  
  /**
   * Prepares HTML code for the generated report
   * 
   * @param array $developer_sales
   * @param string $report_type either 'sales' or 'orders'
   * @return string HTML
   */
  public static function render_orders_list( array $developer_sales, $report_type = 'orders' ) {
    
    $out = '';
    
    $columns = self::$report_columns[$report_type] ?? array();
    
    if ( is_array( $developer_sales ) && count( $developer_sales ) ) {
      
      ob_start();
      ?>

      <table class="ddb-table <?php echo $report_type; ?>-list">
        <thead>
          <?php foreach ( $columns as $key => $name): ?>
            <th><?php echo $name; ?></th>
          <?php endforeach; ?>
        </thead>
        <tbody>
          <?php foreach ( $developer_sales as $order_data ): ?>
            <tr>
              <?php foreach ( $columns as $key => $name): ?>
                <td class="<?php echo $key; ?>" >
                  <?php echo $order_data[$key]; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
        </table>
      <?php 
      
      $out = ob_get_contents();
      ob_end_clean();
    }
    
    return $out;
  }
  
  /**
   * Receives report data and generates summary for the report 
   * (uses individual developer payout settings for payout calculations)
   * 
   * @param array $report_data
   * @param object $developer_term
   */
  public static function render_orders_summary( array $report_data, object $developer_term ) {
   
    $out = '';
    
    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
      $payout_settings = self::find_developer_payout_settings( $developer_term );
      
      $total = 0;
      
      foreach ( $report_data as $order_data ) {
        $total += $order_data['after_coupon'];
      }
      
      if ( $payout_settings['profit_ratio'] == self::USE_GLOBAL_PROFIT_RATIO ) {
        $global_profit_ratio = self::$option_values['global_default_profit_ratio'];
        $payout = $total * $global_profit_ratio;
      }
      else {
        $payout = $total * $payout_settings['profit_ratio'];
      }
      
      $formatter = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
      
      $total_formatted = $formatter->formatCurrency( $total, "USD" );
      $payout_formatted = $formatter->formatCurrency( $payout, "USD" );
      
      $out = "<p><strong>Total</strong>: {$total_formatted} <br><strong>Payout</strong>: {$payout_formatted}</p>";
    }
    
    return $out;
  }
  
  
  /**
   * Prepares CSV text the generated report,
   * and button to copy that text
   * 
   * @param array $report_data
   * @param string $report_type either 'sales' or 'orders'
   * @return string CSV
   */
  public static function generate_csv_to_be_copied( array $report_data, $report_type = 'sales' ) {
    
    $columns = self::$report_columns[$report_type] ?? array();
    
    $csv_data = self::make_csv_line( $columns );
        
    foreach ( $report_data as $row ) {
      
      $clean_row = [];
      
      foreach ( $columns as $column_name => $column_label ) {
        $target_str = $row[$column_name] ?? $column_label;
        
        // remove HTML tags before saving as CSV output
        $clean_str = trim( str_replace( [ '<br>', '<br/>', '<strong>', '</strong>' ], ' ', $target_str ) );
        $clean_row[] = $clean_str;
      }
      
      $csv_data .= self::make_csv_line( $clean_row );
    }
          
    $out = "<span id='el_to_copy' style='display:none;'>{$csv_data}</span>
      <script>
      const copyToClipboard = element_id => {
      
        const source = document.getElementById(element_id);
        
        if ( source ) {
        
          const original_csv = source.innerHTML;
          
          const clean_csv = original_csv.replaceAll('<br>', ' '); // just in case

          const el = document.createElement('textarea');
          
          el.value = clean_csv; // value to be copied 
          
          el.setAttribute('readonly', '');
          el.style.position = 'absolute';
          el.style.left = '-9999px';
          document.body.appendChild(el);
          const selected =
            document.getSelection().rangeCount > 0
              ? document.getSelection().getRangeAt(0)
              : false;
          el.select();
          document.execCommand('copy');
          document.body.removeChild(el);
          if (selected) {
            document.getSelection().removeAllRanges();
            document.getSelection().addRange(selected);
          }
          
          alert('Data copied');
        }
      };
      </script>
      <button onclick='copyToClipboard(\"el_to_copy\")'>Copy report data to clipboard</button>
      <br/><br/><br/>";
    
    return $out;
  }
  
  public static function render_product_daily_sales( object $developer_term, array $dev_sales, array $allowed_days ) {
    
    $developer_products = self::get_developer_products( $developer_term->slug );
    
    if ( is_array( $developer_products ) && count( $developer_products ) ) {
        
      ob_start();
      ?>

      <table class="products-table">
          <thead>
            <th>Product name</th>
            <th>Total sales</th>
            <?php echo self::get_days_header( $allowed_days ); ?>
          </thead>
          <tbody>
            <?php foreach ( $developer_products as $product_id => $product_name ): ?>
                <tr>
                  <td class="product-name"><?php echo $product_name; ?></td>
                  <?php 
                  
                    // $dev_sales_row contains TD cells for total and for all of $allowed_days
                    $dev_sales_row = self::render_product_sales( $dev_sales, $product_id, $allowed_days ); 
                  ?>
                  <?php echo $dev_sales_row; ?>
                </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php 
      $out = ob_get_contents();
      ob_end_clean();
    }
    else {
      $out = '[No active WooCommerce products found for ' . $developer_term->name . ']';
    }
    
    return $out;
  }
}