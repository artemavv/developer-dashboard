<?php

class Ddb_Report_Generator extends Ddb_Core {
  
  
  public static function generate_html( string $start_date, string $end_date, array $report_lines ) {
    
    $out = '';
    
    ob_start();

    $total = 0; 

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
    ?> 

    <h3 style='color:green;'>Orders found from <?php echo $start_date; ?> to <?php echo $end_date; ?></h3>

    <table class="ddb-report-table">
      <thead>
        <?php foreach ( $columns as $key => $name): ?>
          <th><?php echo $name; ?></th>
        <?php endforeach; ?>
      </thead>
      <tbody>
        <?php foreach ( $report_lines as $line ): ?>
          <tr>
            <?php foreach ( $columns as $key => $name): ?>
              <td class="<?php echo $key; ?>" >
                <?php echo $line[$key]; ?>
              </td>
            <?php endforeach; ?>
          </tr>
          <?php $total += $line['after_coupon']; ?>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h4>Total sales: <?php echo $total; ?></h4>
    <?php 
    $out = ob_get_contents();
    ob_end_clean();

    return $out; 
  }
  
  /* deprecated
  public static function generate_report_for_developer( $start_date, $end_date ) {
    
    $html = '';
    
    $report_data = array();
    
    $developer_term = self::find_current_developer_term();
    
    if ( is_object( $developer_term ) ) {
      
      $report_lines = self::get_orders_info( $start_date, $end_date, $developer_term );
      
      if ( ! count( $report_lines ) ) {
        $html = "<h3 style='color:darkred;'>No sales found in the specified date range ( from $start_date to $end_date )</h3>";
      }
      else {
        $html = Ddb_Report_Generator::generate_html( $start_date, $end_date, $report_lines );
        $html .= Ddb_Report_Generator::generate_csv_to_be_copied( $report_lines );
      }
    }
    else {
      $html = '<h3>Not authorized</h3>';
    }
    
    return $html;
  }
  */
  
  public static function generate_csv_to_be_copied( $report_lines ) {
    
    $out = "<script>
      const copyToClipboard = str => {
        const el = document.createElement('textarea');
        el.value = str;
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
      };</script><button onclick='copyToClipboard(\"Mppp\")'>ТТТ</button>";
    
    return $out;
  }
  /**
   * Returns list of purchases of developer's product, in form of array for each separate purchase:
   * 
    First Name
    Last Name
    Email
    Billing address
    Completed Date
    Product name
    Price
    License code
   * 
   * ( each line describes purchase of a single WC product ) 
   * 
   * NOTE: deal products are excluded from the report!
   */
  public static function get_orders_info( string $start_date, string $end_date, object $developer_term ) {
    
    $orders_info = array();
    
    $paid_order_ids = self::get_paid_order_ids( $start_date, $end_date, $developer_term->name );
    
    foreach ($paid_order_ids as $order_id ) {
      $order_lines = self::get_single_order_info( $order_id, $developer_term );
      
      if ( $order_lines ) {
        $orders_info = array_merge( $orders_info, $order_lines );
      }
    }
    
    return $orders_info;
  }
  
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
          'license_code'    => trim($product['license_code'])
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
	private static function echo_headers( $filename, $file_type = 'csv' ) {
    
    switch ( $file_type ) {
      case 'html':
        $content_type = 'text/html';
        $extension = 'html';
      break;
      case 'xls':
        $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $extension = 'xls';
      break;
      case 'csv':
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
   * @param string $filename
   * @param array $report_data
   * @param array $developer_settings
   * @param float $global_profit_ratio
   * @param type $format
   */
  public static function generate_general_payroll_report( string $filename, array $report_data, array $developer_settings, float $global_profit_ratio, $format = 'csv' ) {
    
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
  public static function generate_summary_report( string $filename, array $report_data, array $developer_settings, float $global_profit_ratio, $format = 'csv' ) {
    
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
  
    
  public static function format_report_data( $headers, $data, $format = 'csv' ) {
  
    $report = '';
    switch ( $format ) {
      case 'html':
        
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
      case 'csv':
        $report_headers = self::make_csv_line( $headers );
        $report_data = ''; 
        
        foreach ( $data as $row ) {
          $report_data .= self::make_csv_line( $row );
        }
        
        $report = $report_headers . $report_data;
    }
    return $report;
  }
  
  public static function make_report_line( $data, $format = 'csv' ) {
  
    $line = '';
    switch ( $format ) {
      case 'html':
        $line .= '<tr>';
        foreach ( $data as $value ) {
          $line .= "<td>$value</td>";
        }
        $line .= '</tr>';
      break;
      case 'csv':
        $line = self::make_csv_line( $data );
    }
    return $line;
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