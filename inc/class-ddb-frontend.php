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
    
    $input_fields = [
      'user_id'      => 0,
      'term_id'      => 0,
      'title'        => 'Developer Dashboard',
    ];
    
    extract( shortcode_atts( $input_fields, $atts ) );
    
    if ( $user_id != 0 ) {
      $developer_term = self::find_developer_term_by_user_id( $user_id );
      $term_id = $developer_term->term_id ?? false;
    }
    elseif ( $term_id != 0 ) {
      $developer_term = get_term_by( 'term_id', $term_id, 'developer' );
    }
    
    if ( is_object( $developer_term ) ) {
      
      $sales_data = self::get_developer_sales_data( $term_id );

      $out = '<table><thead><th>Day</th><th>Sale</th></thead><tbody>';

      foreach ( $sales_data as $day => $day_sales ) {
        $out .= "<tr><td>$day</td><td>$day_sales</td></tr>";
      }

      $out .= '<tbody></table>';
    }
    
    
    return $out;
  }
  
}