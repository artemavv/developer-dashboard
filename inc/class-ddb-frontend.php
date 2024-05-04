<?php


class Ddb_Frontend extends Ddb_Core {

  public const ACTION_GENERATE_REPORT = 'Generate sales report';
  
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
    
    $out = '';
    
    $input_fields = [
      'user_id'      => 0,
      'title'        => 'Developer Dashboard',
    ];
    
    extract( shortcode_atts( $input_fields, $atts ) );
    
    if ( $user_id != 0 ) {
      
      $user = get_user_by( 'id', $user_id );
      
      // Check whether that user exists and is actually a Product Developer
      if ( $user && is_array( $user->roles ) && in_array( self::DEV_ROLE_NAME, $user->roles ) ) {
        $developer_term = self::find_developer_term_by_user_id( $user_id );
      }
    }
    
    if ( is_object( $developer_term ) ) {

      $allowed_days = self::generate_allowed_days();
      $dev_sales = self::get_developer_sales_data( $developer_term->term_id, $allowed_days );
      $product_sales = self::get_product_sales_data( $developer_term->term_id, $allowed_days );

      //echo(' TTT product_sales  <pre>' . print_r( $product_sales, 1) . '</pre>');

      $out = '<H2>Total daily sales for "' . $developer_term->name . '"</h2>';
      
      $out .= self::render_total_daily_sales( $dev_sales );

      $out .= '<H2>Daily product sales for "' . $developer_term->name . '"</h2>';
      
      $out .= self::render_product_daily_sales( $developer_term, $product_sales, $allowed_days );
      
      $out .= '<H2>Generate sales report</h2>';
      
      $out .= self::render_report_form( $developer_term );
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
    
    if ( isset( $_POST['ddb-button'] ) ) {
      
      $start_date = $_POST['report_date_start'] ?? false;
      $end_date = $_POST['report_date_end'] ?? false;
      
      switch ( $_POST['ddb-button'] ) {
        case self::ACTION_GENERATE_REPORT:
          $out = self::validate_input_and_generate_report( $start_date, $end_date );
        break;
        case self::ACTION_GENERATE_SALES_TABLE:
          $out = self::generate_sales_table( $start_date, $end_date );
        break;
        
      }
    }
    
    return $out;
  }
    
  public static function render_report_form( $developer_term ) {
    
    ob_start();
    
    $action_results = '';
    
    if ( isset( $_POST['ddb-button'] ) ) {
			$action_results = self::do_action();
		}
		
    self::load_options();
    
    //echo(' TTT <pre>' . print_r( $this->developers, 1) . '</pre>');
    
    $date_start = $_POST['report_date_start'] ?? self::get_earliest_allowed_date();
    $date_end = $_POST['report_date_end'] ?? self::get_today_date();
    
    $report_field_set = array(
      array(
				'name'        => "report_date_start",
				'type'        => 'text',
				'label'       => 'Start date',
				'default'     => '',
        'value'       => $date_start,
        'description' => 'Enter date in YYYY-MM-DD format'
			),
      array(
				'name'        => "report_date_end",
				'type'        => 'text',
				'label'       => 'End date',
				'default'     => '',
        'value'       => $date_end,
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
       <input type="submit" id="ddb-button-generate" name="ddb-button" class="button button-primary" value="<?php echo self::ACTION_GENERATE_REPORT; ?>" />
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
    
    <table>
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
                  <td><?php echo $product_name; ?></td>
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