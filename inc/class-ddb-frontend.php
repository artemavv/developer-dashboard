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

      $out = '<div id="developer-dashboard">';
        
      $out .= "<H2>Total daily sales for {$developer_term->name} </h2>";
      
      $dev_sales = self::get_developer_sales_data( $developer_term->term_id, $allowed_days );
      $out .= self::render_total_daily_sales( $dev_sales );

      $out .= '<H2>Completed orders</h2>';
      
      $out .= self::render_orders_report_form_and_results( $developer_term );
      
      $out .= '<H2>Generate sales report</h2>';
      
      $out .= self::render_sales_report_form_and_results( $developer_term );
      
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
    
    if ( filter_input( INPUT_POST, 'ddb-button' ) ) {
      
      $developer_term = self::find_current_developer_term();
    
      if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
        
        $start_date = filter_input( INPUT_POST, self::FIELD_DATE_START ) ?: false;
        $end_date = filter_input( INPUT_POST, self::FIELD_DATE_END ) ?: false;

        switch ( filter_input( INPUT_POST, 'ddb-button' ) ) {
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
    
    $valid_start_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date );
    $valid_end_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date );
    
    if ( $start_date && $end_date && $valid_start_date && $valid_end_date && $start_date <= $end_date ) {
      if ( $report_type == 'sales' || $report_type == 'orders' ) {
        
        $report_data = Ddb_Report_Generator::get_orders_info( $start_date, $end_date, $developer_term );
       
        if ( is_array($report_data) && count( $report_data) ) {
          $out = self::render_orders_list( $report_data );
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
  
  public static function render_orders_report_form_and_results( object $developer_term ) {
   
    $out = "<H3>Orders for products of {$developer_term->name} from $start_date to $end_date</h3>";
    
    ob_start();
    
    $action_results = '';
    
    if ( filter_input( INPUT_POST, 'ddb-button' ) ) {
			$action_results = self::do_action(); // generate orders report if requested by a user
		}
		
    //echo(' TTT <pre>' . print_r( $this->developers, 1) . '</pre>');
    
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
    
    
    $action_results = '';
    
    if ( filter_input( INPUT_POST,'ddb-button' ) ) {
			$action_results = self::do_action(); // generate sales report if requested by a user
		}
		
    $start_date   = sanitize_text_field( filter_input( INPUT_POST, self::FIELD_DATE_START ) ?? self::get_earliest_allowed_date() );
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
  public static function render_orders_list( array $developer_sales, $report_type = 'sales' ) {
    
    $out = '';
    
    if ( $report_type == 'sales' ) {
      $columns = array(
        'first_name'      => 'First name',
        'last_name'       => 'Last name',
        'email'           => 'Email',
        'address'         => 'Address',
        'date'            => 'Order date',
        'product_name'    => 'Product',
        'price'           => 'Full Price',
        'after_coupon'    => 'Discounted price',
        'license_code'    => 'License code'
      );
    }
    else {
      $columns = array(
        'order_id'        => 'Order ID',
        'date'            => 'Order date',
        'full_name'       => 'Full name',
        'product_name'    => 'Product',
        'after_coupon'    => 'Paid price',
        'license_code'    => 'License code'
      );
    }
    
    $total = 0;
    
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
          <?php foreach ( $developer_sales as $order_id => $order_data ): ?>
            <tr>
              
              <?php if ( $report_type == 'sales' ) { // render table line with sale info
              
                $full_order_data = array_merge( 
                  $order_data, 
                  array( 
                    'order_id' => $order_id,
                    'full_name' => $order_data['first_name'] . ' ' . $order_data['first_name']
                  ) 
                );
              }
              else {
                $full_order_data = $order_data;
              }
              ?>
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