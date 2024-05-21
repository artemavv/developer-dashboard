<?php

class Ddb_Report_Generator extends Ddb_Core {
  
  /**
   * Returns list of lines suitable for the final report
   * ( each line describes purchase of a single WC product ) 
   * for a single order.
   * 
   * For each developer's product in that order, a separate line is generated - it will go into the final report
   */
  public static function get_single_order_info( int $order_id, object $developer_term ) {
  
    $order_lines = false;
    
    $order = new WC_Order( $order_id );

    if ( is_object( $order ) ) {
      $order_items_data = self::get_developer_items_in_order( $order, $developer_term );

      //echo('order_items_data<pre>');print_r($order_items_data);echo('</pre>');

      $order_lines = array();

      foreach ( $order_items_data as $product ) {

        $completed_date = $order->get_date_completed();
        $date_formatted = '?';
        
        if ( $completed_date instanceof WC_DateTime ) {
            $date = $completed_date->getTimestamp();
        } elseif ( ! is_int( $completed_date ) ) {
            $date = strtotime( $completed_date );
        }
        
        if ( ! empty( $date ) ) {
            $date_formatted = date_i18n( 'Y-m-d', $date );
        }
        
        $order_line = array(
          'order_id'        => $order_id,
          'first_name'      => $order->get_billing_first_name(),
          'last_name'       => $order->get_billing_last_name(),
          'email'           => $order->get_billing_email(),
          'address'         => $order->get_formatted_billing_address(),
          'date'            => $date_formatted,
          'product_name'    => $product['name'],
          'price'           => $product['price_before_coupon'],
          'after_coupon'    => $product['price_after_coupon'],
          'license_code'    => trim( strip_tags( $product['license_code'] ) )
        );
        
        $order_line['full_name'] = $order_line['first_name'] . ' ' . $order_line['last_name'];

        $order_lines[] = $order_line;
      }
    }

    return $order_lines;
  }

  /**
   * Get the list of matching orders ( those that include products provided by the specified developer),
   * within specified date range and with order sum greater than 0 
   */
  public static function get_paid_order_ids( string $start_date, string $end_date, string $developer_name ) {
    
    global $wpdb;
    
    $wp = $wpdb->prefix;
    
    $date_condition = $wpdb->prepare(
      " ( p.post_date >= %s AND p.post_date <= %s ) ",
      $start_date . " 00:00:00", 
      $end_date . " 23:59:59"
    );
      
    $developer_condition = $wpdb->prepare( "im.`meta_key` = 'developer_name' AND im.`meta_value` = %s ", $developer_name );
    $order_totals_condition = " pm.`meta_key` = '_order_total' AND ( pm.`meta_value` != '0.00' AND pm.`meta_value` != '0' )";
    
    $query_sql = "SELECT p.ID from {$wp}posts AS p
      LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
      LEFT JOIN `{$wp}woocommerce_order_items` AS oi on p.`ID` = oi.`order_id`
      LEFT JOIN `{$wp}woocommerce_order_itemmeta` AS im on im.`order_item_id` = oi.`order_item_id`
      WHERE $developer_condition
      AND $date_condition
      AND $order_totals_condition
      AND p.post_type = 'shop_order' AND p.post_status = 'wc-completed'
      ORDER BY p.post_date DESC";
    
    $ids = array();
    
    
    
    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );
    
    foreach ($sql_results as $row) {
      $ids[] = $row['ID'];
    }
    return $ids;
  }
  
  
  /**
   * Get the last N matching orders ( those that include products provided by the specified developer)
   */
  public static function get_last_order_ids( string $developer_name, int $amount = 20 ) {
    
    global $wpdb;
    
    $wp = $wpdb->prefix;
    
    $earliest_date = self::CUTOFF_DATE;
      
    $date_condition = $wpdb->prepare(
      " ( p.post_date >= %s ) ",
      $earliest_date . " 00:00:00", 
    );
        
    $amount_condition = $wpdb->prepare( ' LIMIT %d ', intval( $amount ) ?: 20 );
      
    $developer_condition = $wpdb->prepare( "im.`meta_key` = 'developer_name' AND im.`meta_value` = %s ", $developer_name );
    $order_totals_condition = " pm.`meta_key` = '_order_total' AND ( pm.`meta_value` != '0.00' AND pm.`meta_value` != '0' )";
    
    $query_sql = "SELECT p.ID from {$wp}posts AS p
      LEFT JOIN `{$wp}postmeta` AS pm on p.`ID` = pm.`post_id`
      LEFT JOIN `{$wp}woocommerce_order_items` AS oi on p.`ID` = oi.`order_id`
      LEFT JOIN `{$wp}woocommerce_order_itemmeta` AS im on im.`order_item_id` = oi.`order_item_id`
      WHERE $developer_condition
      AND $date_condition
      AND $order_totals_condition
      AND p.post_type = 'shop_order' AND p.post_status = 'wc-completed'
      ORDER BY p.post_date DESC $amount_condition ";
    
    $ids = array();
    
    $sql_results = $wpdb->get_results( $query_sql, ARRAY_A );
    
    foreach ($sql_results as $row) {
      $ids[] = $row['ID'];
    }
    return $ids;
  }

  
  /**
   * Returns list of developer products purchased in the specified order. 
   * 
   * NOTE: deal products are excluded!
   */
  public static function get_developer_items_in_order( object $order, object $developer_term ) {
    
    $results = array();     
    
    $items = $order->get_items();

    foreach ( $items as $key => $item ) {

      $item_id = $item->get_product_id();
      $is_shop_product = get_post_meta($item_id, '_product_type_single', true);


      $item_result = array();

      if ( has_term( $developer_term->term_id, 'developer', $item_id ) && $is_shop_product == 'yes' ) {

        $item_result['product_id']              = $item_id;
        $item_result['name']                    = $item['name'];
        $item_result['price_after_coupon']      = $order->get_item_total( $item, false, true );
        $item_result['price_before_coupon']     = $order->get_item_subtotal( $item, false, true );
        $item_result['license_code']            = false;
        $item_result['is_deal_product']         = false;
        $item_result['is_shop_product']         = false;

        $item_meta = $item->get_meta_data();

        foreach ( $item_meta as $meta_item ) {

          if ( $meta_item->key == 'bigdeal' && $meta_item->value == 1 ) {
            $item_result['is_deal_product'] = true;
          }

          if ($meta_item->key == 'shop_product' && $meta_item->value == 1) {
            $item_result['is_shop_product'] = true;
          }

          if (($meta_item->key == 'Coupon Code(s)') or ($meta_item->key == 'License Code(s)') ) {
            $codes = $meta_item->value;

            if ( is_array($codes) ) {
              $item_result['license_code'] = implode(', ', $codes);
            } else {
              $item_result['license_code'] = $codes;
            }
          }

          if ( ($meta_item->key == 'developer_name') ) {
            $item_result['developer_name'] = $meta_item->value;
          }

        } // endforeach

        if ( $item_result['developer_name'] != $developer_term->name ) {
          continue;
        }

        if ( $item_result['is_deal_product'] ) {
          continue;
        }

        if ( ! $item_result['is_shop_product'] ) {
          continue;
        }

        self::log(['$item_result', $item_result ] );

        $results[] = $item_result;
      } // end if correct product developer

    } // end for each item
      
    self::log(['$results', $results ] );
    
    return $results;
  }

  
  	
	/**
	 * Send headers for browser to download the file
	 */
	private static function echo_headers( $filename, $file_type = self::FILE_FORMAT_CSV ) {
    
    switch ( $file_type ) {
      case self::FILE_FORMAT_HTML:
        $content_type = 'text/html';
        $extension = 'html';
      break;
      case self::FILE_FORMAT_XLSX:
        $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $extension = 'xlsx';
      break;
      case self::FILE_FORMAT_CSV:
        $content_type = 'text/csv';
        $extension = 'csv';
      break;
    }
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: $content_type");
    header("Content-Disposition: attachment;Filename={$filename}.{$extension}");
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	}
  
  /**
   * Lists developers who are paid by some non-paypal methos
   * 
   * This report is triggered from backend area and shown to site admins
   * 
   * @param string $filename
   * @param array $report_data
   * @param array $developer_settings
   * @param float $global_profit_ratio
   * @param type $format
   */
  public static function generate_general_payroll_report( string $filename, array $report_data, array $developer_settings, float $global_profit_ratio, $format = self::FILE_FORMAT_CSV ) {
    
    self::echo_headers( $filename, $format );
    

    $report_lines = array(); 
    
    foreach ( $developer_settings as $dev_id => $developer ) {
        
      $dev_data_from_report = $report_data['devs'][ $dev_id ] ?: false;
      
      if ( $dev_data_from_report ) {
        $dev_name = $dev_data_from_report['name'];
        $earnings = $dev_data_from_report['summary']['gross_earnings_with_discount'] ?: 0;

        $dev_profit_ratio = 0.01 * $developer['profit_ratio']; // ratio is saved in percents. "2" is 2%, 0.02 
        $payment_method = self::$payment_methods_names[ $developer_settings[$dev_id]['payment_method'] ] ?: '';

        $developer_share = ( $developer['profit_ratio'] == self::USE_GLOBAL_PROFIT_RATIO ) ? $global_profit_ratio : $dev_profit_ratio ;

        $payout = round( $developer_share * $earnings, 2);

        $developer_share *= 100; // to show percents instead of fraction
        
        $report_lines[] =  [$dev_name, $payout, $payment_method];
        
      }
    }
    
    $report_headers = array(
      'dev_name'        => 'Developer name',
      'payout'          => 'Total developer profits',
      'payment_method'  => 'Payment method'
    );
    
    $formatted_report = self::format_report_data( $report_headers, $report_lines, $format );
    
    echo $formatted_report;
    die();
  }
  
  /**
   * Lists developers who are paid via PayPal
   * 
   * @param string $filename
   * @param array $report_data
   * @param array $developer_settings
   * @param float $global_profit_ratio
   * @param string $format
   */
  public static function generate_paypal_payroll_report( string $filename, array $report_data, array $developer_settings, float $global_profit_ratio, string $format = 'html' ) {
    
    self::echo_headers( $filename );
    
    $report_lines = array();
    
    foreach ( $developer_settings as $dev_id => $developer ) {
        
      $dev_data_from_report = $report_data['devs'][ $dev_id ] ?: false;
      
      if ( $dev_data_from_report ) {
        $dev_name = $dev_data_from_report['name'];
        $earnings = $dev_data_from_report['summary']['gross_earnings_with_discount'] ?: 0;

        $dev_profit_ratio = 0.01 * $developer['profit_ratio']; // ratio is saved in percents. "2" is 2%, 0.02 
        $paypal_address = $developer['paypal_address'] ?? '';

        $developer_share = ( $developer['profit_ratio'] == self::USE_GLOBAL_PROFIT_RATIO ) ? $global_profit_ratio : $dev_profit_ratio ;

        $payout = round( $developer_share * $earnings, 2);

        $report_line = [ $dev_name, $payout, $paypal_address, 'USD' ];
        
        $report_lines[] = $report_line;
      }
    }
    
    $report_headers = array(
      'dev_name'        => 'Developer name',
      'payout'          => 'Total developer profits',
      'payment_method'  => 'Paypal address',
      'currency'        => 'Currency'
    );
    
    $formatted_report = self::format_report_data( $report_headers, $report_lines, $format );
    echo $formatted_report;
    die();
  }
  
  
  /**
   * Lists all developers and their profits
   * 
   * @param string $filename
   * @param array $report_data
   * @param array $developer_settings
   * @param float $global_profit_ratioList
   */
  public static function generate_summary_report( string $filename, array $report_data, array $developer_settings, float $global_profit_ratio, $format = self::FILE_FORMAT_CSV ) {
    
    self::echo_headers( $filename );
    
    $report_lines = array();
    
    //foreach ( $report_data['devs'] as $dev_id => $dev_data ) {
    foreach ( $developer_settings as $dev_id => $developer ) {
        
      $dev_data_from_report = $report_data['devs'][ $dev_id ] ?: false;
      
      if ( $dev_data_from_report && is_array($developer) && count($developer) ) {
        
        $dev_name = $dev_data_from_report['name'];
        $earnings = $dev_data_from_report['summary']['gross_earnings_with_discount'] ?: 0;

        $dev_profit_ratio = 0.01 * $developer['profit_ratio']; // ratio is saved in percents. "2" is 2%, 0.02 
        $paypal_address = $developer['paypal_address'] ?? '';

        $developer_share = ( $developer['profit_ratio'] == self::USE_GLOBAL_PROFIT_RATIO ) ? $global_profit_ratio : $dev_profit_ratio ;

        $payout = round( $developer_share * $earnings, 2);

        $report_line = [ $dev_name, $payout ];
        
        $report_lines[] = $report_line;
      }
    }
    
    $report_headers = array(
      'dev_name'        => 'Developer name',
      'payout'          => 'Total profits (USD)'
    );
    
    $formatted_report = self::format_report_data( $report_headers, $report_lines, $format );
    echo $formatted_report;
    
    die();
  }
  
  public static function validate_and_generate_xlsx_report() {

    $developer_term = Ddb_Core::find_current_developer_term();

    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {

      $action = filter_input( INPUT_POST, Ddb_Core::BUTTON_SUMBIT );
      $start_date = filter_input( INPUT_POST, Ddb_Core::FIELD_DATE_START ) ?: false;
      $end_date = filter_input( INPUT_POST, Ddb_Core::FIELD_DATE_END ) ?: false;

      $validation_failed = Ddb_Frontend::validate_input( $start_date, $end_date );

      if ( $validation_failed === false ) {
        switch ( $action ) { // this may be expanded in the future, therefore using switch
          case Ddb_Frontend::ACTION_DOWNLOAD_ORDERS_REPORT: 
            $report_generated = self::generate_xlsx_report( $developer_term, $start_date, $end_date );

            if ( $report_generated ) {
              exit();
            }
          break;
        }
      }
    }
  }

  /**
   * 
   * @param object $developer_term
   * @param string $start_date
   * @param string $end_date
   * @return boolean
   */
  public static function generate_xlsx_report( object $developer_term, string $start_date, string $end_date ) {
  
    $report_is_ok = false;
    
    $orders_data = array();
    
    $paid_order_ids = self::get_paid_order_ids( $start_date, $end_date, $developer_term->name );

    foreach ( $paid_order_ids as $order_id ) {
      $order_lines = self::get_single_order_info( $order_id, $developer_term );

      if ( $order_lines ) {
        $orders_data = array_merge( $orders_data, $order_lines );
      }
    }
    
    if ( is_array($orders_data) && count($orders_data) ) {
      
      $report_is_ok = true;
      
      $columns = self::$report_columns['orders'];
      $report_lines = [];
      
      foreach ( $orders_data as $order_line ) {
        
        $report_line = [];
        foreach ( $columns as $key => $name ) {
          $report_line[] = $order_line[$key];
        }
        
        $report_lines[] = $report_line;
      }
            
      $report_data = array_merge( array( 0 => array_values($columns) ), $report_lines );
      
      
      //echo('222 $report_data<pre>' . print_r($report_data, 1) . '</pre>'); die();
      
      $filename = 'report_from_' . $start_date . '_to_' . $end_date;
    
      self::echo_headers( $filename, self::FILE_FORMAT_XLSX );

      $writer = new XLSXWriter();
      $writer->writeSheet( $report_data );
      $writer->writeToStdOut();
    }
    
    return $report_is_ok;
  }

    
  public static function format_report_data( $headers, $data, $format = self::FILE_FORMAT_CSV ) {
  
    $report = '';
    switch ( $format ) {
      case self::FILE_FORMAT_HTML:
        
        $report_headers .= '<thead>';
        foreach ( $headers as $value ) {
          $report_headers .= "<th>$value</th>";
        }
        $report_headers .= '</head>';
        
        $report_data = '';
        foreach ( $data as $row ) {
          $report_data .= '<tr>';
          foreach ( $row as $value ) {
            $report_data .= "<td>$value</td>";
          }
          $report_data .= '</tr>';
        }
        
        $report = '<html><body><table>' . $report_headers . '<tbody>' . $report_data . '</tbody></table></body></html>';
      break;
      case self::FILE_FORMAT_CSV:
        $report_headers = self::make_csv_line( $headers );
        $report_data = ''; 
        
        foreach ( $data as $row ) {
          $report_data .= self::make_csv_line( $row );
        }
        
        $report = $report_headers . $report_data;
      break;
      case self::FILE_FORMAT_XLSX: // wiil be generated later by XLSXWriter which needs data as a plain array
        $report = array_merge( $headers, $data );
    }
    return $report;
  }
  
  public static function make_report_line( $data, $format = self::FILE_FORMAT_CSV ) {
  
    $line = '';
    switch ( $format ) {
      case self::FILE_FORMAT_HTML:
        $line .= '<tr>';
        foreach ( $data as $value ) {
          $line .= "<td>$value</td>";
        }
        $line .= '</tr>';
      break;
      case self::FILE_FORMAT_CSV:
        $line = self::make_csv_line( $data );
    }
    return $line;
  }
  
}