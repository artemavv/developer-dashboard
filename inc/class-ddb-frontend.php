<?php


class Ddb_Frontend extends Ddb_Core {

  public const ACTION_GENERATE_SALES_REPORT = 'Generate sales report';
  public const ACTION_GENERATE_ORDERS_REPORT = 'Generate orders report';
  
  public const FIELD_DATE_START       = 'report_date_start';
  public const FIELD_DATE_END         = 'report_date_end';
    
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
    
      $allowed_days = self::generate_allowed_days();

      $out = '<div id="developer-dashboard" style="margin: 30px;">';
      
      $out .= '<H2>Payout settings</h2>';
      
      $out .= self::render_payout_settings( $developer_term );
      
      $out .= '<H2>List of completed orders</h2>';
      
      $out .= self::render_orders_report_form_and_results( $developer_term );
      
      /* $out .= '<H2>List of sales</h2>';
      
      $out .= self::render_sales_report_form_and_results( $developer_term ); */
      
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
  
  public static function do_action_if_triggered( $action ) {
    
    $out = '';
    
    if ( filter_input( INPUT_POST, 'ddb-button' ) === $action ) {
      
      $developer_term = self::find_current_developer_term();
    
      if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
        
        $start_date = filter_input( INPUT_POST, self::FIELD_DATE_START ) ?: false;
        $end_date = filter_input( INPUT_POST, self::FIELD_DATE_END ) ?: false;

        switch ( $action ) {
          case self::ACTION_GENERATE_SALES_REPORT:
            $out = self::validate_input_and_generate_report( $developer_term, $start_date, $end_date, 'sales' );
          break;
          case self::ACTION_GENERATE_ORDERS_REPORT:
            $out = self::validate_input_and_generate_report( $developer_term, $start_date, $end_date, 'orders' );
          break;
        }
      }
      else {
        $out = '<h3>Not Authorized to view this report</h3>';
      }
    }
    
    return $out;
  }
  
  /**
   * Check the forn input and returns HTML with report of requested type+
   * 
   * @param object $developer_term WP_Term object
   * @param string $start_date date in Y-m-d format
   * @param string $end_date date in Y-m-d format
   * @param string $report_type either 'sales' or 'orders'
   * @return string report HTML
   */
  public static function validate_input_and_generate_report( object $developer_term, string $start_date, string $end_date, string $report_type = 'sales') {
    
    $out = ''; 
    
    $valid_start_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) && self::validate_date( $start_date );
    $valid_end_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) && self::validate_date( $end_date );
    
    if ( $start_date && $end_date && $valid_start_date && $valid_end_date && $start_date <= $end_date ) {
      if ( $report_type == 'sales' || $report_type == 'orders' ) {
        
        $report_data = array();
    
        $paid_order_ids = Ddb_Report_Generator::get_paid_order_ids( $start_date, $end_date, $developer_term->name );

        foreach ($paid_order_ids as $order_id ) {
          $order_lines = Ddb_Report_Generator::get_single_order_info( $order_id, $developer_term );

          if ( $order_lines ) {
            $report_data = array_merge( $report_data, $order_lines );
          }
        }
    
        if ( is_array($report_data) && count($report_data) ) {
          
          $out = "<h3>Found $report_type of products from {$developer_term->name} from $start_date to $end_date</h3>";
          $out .= self::render_orders_list( $report_data, $report_type );
          $out .= self::generate_csv_to_be_copied( $report_data, $report_type );
        }
        else {
          $out = "<h3 style='color:darkred;'>No $report_type found in the specified date range ( from $start_date to $end_date )</h3>";
        }
      }
    }
    else {
      if ( ! $valid_start_date ) {
        $out .= '<h3 style="color:red;">Start date must be in YYYY-MM-DD format</h3>';
      }
      if ( ! $valid_end_date ) {
        $out .= '<h3 style="color:red;">End date must be in YYYY-MM-DD format</h3>';
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
  
  public static function render_payout_settings( object $developer_term ) {
    
    $out = '';
    
    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
      
      $ratio = get_term_meta( $developer_term->term_id, 'profit_ratio', true );
      $payment_method = get_term_meta( $developer_term->term_id, 'payment_method', true );
      $paypal_address = get_term_meta( $developer_term->term_id, 'paypal_address', true );
      $dropbox_folder_url = get_term_meta( $developer_term->term_id, 'dropbox_folder_url', true );  
    
      // in the DB these values are stored as fractions ( 0.12 for 12% profit ratio)
      // need to multiply by 100 to show values as percents
      
      if ( $ratio == self::USE_GLOBAL_PROFIT_RATIO ) {
        $global_profit_ratio = self::$option_values['global_default_profit_ratio'];
        $actual_ratio = $global_profit_ratio * 100;
      }
      else {
        $actual_ratio = $ratio * 100;
      }
      
      $out .= '<p><strong>Profit ratio</strong>: ' . ( $actual_ratio ) . '%</p>';
      $out .= '<p><strong>Payment method</strong>: ' . ( self::$payment_methods_names[$payment_method] ?? '?' ) . '</p>';
      
      if ( $payment_method == self::PM__PAYPAL && $paypal_address ) {
        $out .= '<p><strong>PayPal address</strong>: ' . $paypal_address . '%</p>';
      }
      
      if ( $dropbox_folder_url ) {
        $out .= '<p><strong>Reports Archive</strong>: <a href="' . $dropbox_folder_url . '">download from Dropbox folder</a></p>';
      }
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
        $out = "<h3>Last {$num} orders with products by {$developer_term->name}</h3>";
        $out .= self::render_orders_list( $report_data, 'orders' );
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
    
    if ( filter_input( INPUT_POST, 'ddb-button' ) ) {
      $action_results = self::do_action_if_triggered( self::ACTION_GENERATE_ORDERS_REPORT ); // generate orders report if requested by a user
    }
    else {
      $action_results = self::render_last_n_orders( $developer_term );
    }
    
    $start_date   = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_START ) ?? date( 'Y-m-d', strtotime("-7 days") ) );
    $end_date     = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_END ) ?? self::get_today_date() );
    
    $report_field_set = array(
      array(
				'name'        => "report_date_start",
				'type'        => 'text',
				'label'       => 'Start date',
				'default'     => '',
        'value'       => $start_date,
        'description' => 'Enter date in YYYY-MM-DD format'
			),
      array(
				'name'        => "report_date_end",
				'type'        => 'text',
				'label'       => 'End date',
				'default'     => '',
        'value'       => $end_date,
        'description' => 'Enter date in YYYY-MM-DD format'
			),
		);

    echo $action_results;
    
    ?> 

    <form method="POST" >
      
      <table class="ddb-report-form-table">
        <tbody>
          <?php self::display_field_set( $report_field_set ); ?>
        </tbody>
      </table>
      
      <p class="submit">  
       <input type="submit" id="ddb-button-generate" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_GENERATE_ORDERS_REPORT; ?>" />
      </p>
      
    </form>
    <?php 
    $out = ob_get_contents();
		ob_end_clean();

    return $out; 
  }
    
  public static function render_sales_report_form_and_results( $developer_term ) {
    
    ob_start();
    
    $action_results = self::do_action_if_triggered( self::ACTION_GENERATE_SALES_REPORT ); // generate orders report if requested by a user
    
    $start_date   = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_START ) ?: self::get_earliest_allowed_date() );
    $end_date     = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_END ) ?: self::get_today_date() );
    
    $report_field_set = array(
      array(
				'name'        => "report_date_start",
				'type'        => 'text',
				'label'       => 'Start date',
				'default'     => '',
        'value'       => $start_date,
        'description' => 'Enter date in YYYY-MM-DD format'
			),
      array(
				'name'        => "report_date_end",
				'type'        => 'text',
				'label'       => 'End date',
				'default'     => '',
        'value'       => $end_date,
        'description' => 'Enter date in YYYY-MM-DD format'
			),
		);

    echo $action_results;
    ?> 

    <form method="POST" >
      
      <table class="ddb-report-form-table">
        <tbody>
          <?php self::display_field_set( $report_field_set ); ?>
        </tbody>
      </table>
      
      <p class="submit">  
       <input type="submit" id="ddb-button-generate" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_GENERATE_SALES_REPORT; ?>" />
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
    
    $total = 0;
    
    if ( is_array( $developer_sales ) && count( $developer_sales ) ) {
      
      //echo('<pre>'); echo print_r( $developer_sales, 1 ); echo('</pre>');
      
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
            <?php $total += $order_data['after_coupon']; ?>
          <?php endforeach; ?>
        </tbody>
        </table>

        <p>Total: <?php echo $total; ?></p>
      <?php 
      
      $out = ob_get_contents();
      ob_end_clean();
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