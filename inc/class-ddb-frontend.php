<?php


class Ddb_Frontend extends Ddb_Core {

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

      $dev_sales = self::get_developer_sales_data( $developer_term->term_id );
      $product_sales = self::get_product_sales_data( $developer_term->term_id );

      $out = '<H2>Total daily sales for "' . $developer_term->name . '"</h2>';
      
      $out .= self::render_total_daily_sales( $dev_sales );

      $out .= '<H2>Daily product sales for "' . $developer_term->name . '"</h2>';
      
      $out .= self::render_product_daily_sales( $developer_term, $product_sales );
    }
    
    return $out;
  }
  
  
  public static function render_total_daily_sales( $sales_data ) {
    
    ob_start();
    ?>
    
    <table>
      <thead>
        <th>Day</th>
        <th>Sale</th>
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
  
  public static function render_product_daily_sales( object $developer_term, array $dev_sales ) {
    
    $developer_products = self::get_developer_products( $developer_term->slug );
    
    if ( is_array( $developer_products ) && count( $developer_products ) ) {
      
      ob_start();
      ?>

      <table class="products-table">
          <thead>
            <th>Product name</th>
            <th>Total sales</th>
            <?php echo self::get_allowed_days_header(); ?>
          </thead>
          <tbody>
            <?php foreach ( $developer_products as $product_id => $product_name ): ?>
                <tr>
                  <td><?php echo $product_name; ?></td>
                  <?php $dev_sales_row = self::render_allowed_product_sales( $dev_sales, $developer_term->term_id, $product_id ); ?>
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
      $out = '[No active WooCommerce products found for this developer]';
    }
    
    return $out;
  }
}