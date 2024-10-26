<?php

class Ddb_Report_Generator extends Ddb_Core {
  
	
	static $full_report_summary = array();
	
  /**
   * Returns list of lines suitable for the final report
   * ( each line describes purchase of a single WC product ) 
   * for a single order.
   * 
   * For each developer's product in that order, a separate line is generated - it will go into the final report
   */
  public static function get_single_order_info( int $order_id, ?object $developer_term, $product_id = false, $deal_products_only = false ) {
  
    $order_lines = false;
    
    $order = new WC_Order( $order_id );

    if ( is_object( $order ) ) {
      
      $order_items_data = self::filter_target_items_in_order( $order, $developer_term, $product_id, $deal_products_only );
      $order_lines = array();

      //self::wc_log( "get_single_order_info $order_id ORDER ITEMS " );
      //self::wc_log( "get_single_order_info", $order_items_data );
      
      foreach ( $order_items_data as $product_arr ) {

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
          'product_name'    => $product_arr['name'],
          'price'           => $product_arr['price_before_coupon'],
          'after_coupon'    => $product_arr['price_after_coupon'],
          'license_code'    => trim( strip_tags( $product_arr['license_code'] ) ),
          'total_refunded'  => $order->get_total_refunded(),
					'count_refunded'  => $order->get_item_count_refunded(),
        );
        
        $order_line['full_name'] = $order_line['first_name'] . ' ' . $order_line['last_name'];

        
        // if order has been refunded, check for the case with the partial refund 
        if ( $order_line['total_refunded'] || $order_line['count_refunded'] ) {

          self::log( " $order_id ORDER - CHECK FOR REFUND " );

          $order_line['developer_refunded'] = 0;

          $order_refunds = $order->get_refunds();

          // Check for the case when refunds are linked to specific order item(s)
          foreach( $order_refunds as $refund ) {
            foreach( $refund->get_items() as $key => $item ) {

              self::log( "Refunded item $key ");
              self::log( [$item->get_total(), $item->get_product_id(), $item->get_name() ] );

              foreach ( $order_items_data as $product_arr ) {

                if ($product_arr['product_id'] == $item->get_product_id() ) {
                  $refunded_sum = $item->get_total(); // $order_product['price_after_coupon'];
                  $refunded_quantity = $item->get_quantity(); // returns negative number e.g. -1

                  $order_line['developer_refunded'] += $refunded_sum;
                  break;
                }
              } 
            }
          }
          
          // Check for the case when the refund is linked to the order itself.
          // If the order consists of a single item, then we can attribure refund to tha item
          
          if ( $product_arr['sole_item_in_order'] ) {
            foreach( $order_refunds as $refund ) {
              
              $refunded_sum = $refund->get_total();
              
              self::log( " $order_id ORDER REFUND SIGLE SUM " . $refunded_sum );
              
              
              $order_line['developer_refunded'] += abs($refunded_sum);
            }
          }
        }
        
        $order_lines[] = $order_line;
      }
    }

    //self::wc_log( "get_single_order_info - $order_id ORDER LINES ", $order_lines );
    return $order_lines;
  }

  /**
   * Get the list of matching orders ( those that include products provided by the specified developer),
   * within specified date range and with order sum greater than 0 
   * 
   * @param $start_date string
   * @param $end_date string
   * @param $developer_name string
   * @param $report_settings array [ developer_id, product_id, deal_product_id, include_free_orders ] 
   */
  public static function get_target_order_ids( string $start_date, string $end_date, string $developer_name = '', array $report_settings = array() ) {
    
    $product_id = 0;
    
    if ( $report_settings['product_id'] ?? false ) {
      $product_id = $report_settings['product_id'];
    }
    elseif ( $report_settings['deal_product_id'] ?? false ) {
      $product_id = $report_settings['deal_product_id'];
    }
      
    if ( ! $developer_name && ! $product_id ) {
      return false; // not enough search info
    }
    
    global $wpdb;
    
    $wp = $wpdb->prefix;
    
    $date_condition = $wpdb->prepare(
      " ( wco.date_created_gmt >= %s AND wco.date_created_gmt <= %s ) ",
      $start_date . " 00:00:00", 
      $end_date . " 23:59:59"
    );
    
    if ( $product_id != 0 ) {
      $maybe_add_product_join = " LEFT JOIN `{$wp}woocommerce_order_itemmeta` AS im2 on im2.`order_item_id` = oi.`order_item_id` ";
      $product_condition = $wpdb->prepare( "im2.`meta_key` = '_product_id' AND im2.`meta_value` = %d ", $product_id );
    }
    else {
      $maybe_add_product_join = '';
      $product_condition = ' 1 = 1 ';
    }
    
    if ( $developer_name ) {
      $maybe_add_developer_join = " LEFT JOIN `{$wp}woocommerce_order_itemmeta` AS im on im.`order_item_id` = oi.`order_item_id` ";
      $developer_condition = $wpdb->prepare( "im.`meta_key` = 'developer_name' AND im.`meta_value` = %s ", $developer_name );
    } 
    else {
      $maybe_add_developer_join = '';
      $developer_condition = ' 1 = 1 ';
    }
    
    if ( $report_settings['include_free_orders'] ?? false ) {
      $order_totals_condition = " 1 = 1 ";
    }
    else {
      $order_totals_condition = " wco.total_amount > 0 ";
    }
    
    $query_sql = "SELECT wco.id as ID from {$wp}wc_orders AS wco
      LEFT JOIN `{$wp}woocommerce_order_items` AS oi on wco.`id` = oi.`order_id`
      $maybe_add_developer_join
      $maybe_add_product_join  
      WHERE $developer_condition
      AND $product_condition
      AND $date_condition
      AND $order_totals_condition
      AND wco.status = 'wc-completed'
      ORDER BY wco.date_created_gmt DESC";
    
		
		//self::wc_log( $query_sql );
		
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
  public static function get_last_order_ids( string $developer_name, int $amount = 20, $include_deals = false ) {
    
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
  
  
  public static function remove_duplicated_order_lines( $order_lines ) {

    $filtered_lines = array();

    foreach ( $order_lines as $line ) {
      if ( ! self::check_for_duplicated_line( $line['email'], $line['product_name'], $filtered_lines ) ) {
        $filtered_lines[] = $line;
      }
    }

    return $filtered_lines;
  }
  
  
  public static function check_for_duplicated_line( $billing_email, $product_name, $filtered_lines ) {

    $this_line_is_duplicated = false;
    
    foreach ( $filtered_lines as $line ) {

      $source_billing_email = $line['email'];
      $source_product_name = $line['product_name'];

      if ( $source_product_name == $product_name && $source_billing_email == $billing_email ) {
        $this_line_is_duplicated = true;
        break;
      }
    }

    return $this_line_is_duplicated;
  }
  
  /**
   * Returns list of target product items purchased in the specified order. 
   * 
   * There are two kind of targeting: 
   * a) get all products from a specific developer ( set by $developer_term )
   * b) get all products with specific ID ( $developer_term should be null in that case )
   * 
   * If $deal_products_only is false, ALL deal products will be excluded
   * 
   * If $deal_products_only is false, ONLY deal products will be included
   * 
   * @param object $order WC_Order
   * @param object $developer_term for the case when we search for all developer's products, null otherwise
   * @param int $target_product_id for the case when we search for specific product, zero otherwise
   * @param bool $deal_products_only
   */
  public static function filter_target_items_in_order( object $order, ?object $developer_term, $target_product_id = 0, $deal_products_only = false ) {
    
    $results = array();     
    
    $items = $order->get_items();

    $number_of_items  = count( $items );
    
    foreach ( $items as $key => $item ) {

      $item_id = $item->get_product_id();
      $is_shop_product = get_post_meta($item_id, '_product_type_single', true);

      $product_matches_target = false; 
      
      if ( is_object($developer_term) ) {
        $product_matches_target = has_term( $developer_term->term_id, 'developer', $item_id );
      }
      elseif ( $target_product_id > 0 ) {
        $product_matches_target = ( $target_product_id == $item_id );
      }

      $item_result = array();

      if ( $product_matches_target ) {
        
        // check for the match between target product and filter restriction
        if ( ($deal_products_only && $is_shop_product != 'yes') || (! $deal_products_only && $is_shop_product == 'yes') ) {

          $item_result['product_id']              = $item_id;
          $item_result['name']                    = $item['name'];
          $item_result['price_after_coupon']      = $order->get_item_total( $item, false, true );
          $item_result['price_before_coupon']     = $order->get_item_subtotal( $item, false, true );
          $item_result['license_code']            = false;
          $item_result['is_deal_product']         = false;
          $item_result['is_shop_product']         = false;
          $item_result['sole_item_in_order']      = false;

          $item_meta = $item->get_meta_data();

          foreach ( $item_meta as $meta_item ) {

            if ( $meta_item->key == 'bigdeal' && $meta_item->value == 1 ) {
              $item_result['is_deal_product'] = true;
            }

            if ( $meta_item->key == 'shop_product' && $meta_item->value == 1 ) {
              $item_result['is_shop_product'] = true;
            }

            if ( ($meta_item->key == 'Coupon Code(s)') or ($meta_item->key == 'License Code(s)') ) {
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

          } // endforeach meta

          if ( is_object($developer_term) && $item_result['developer_name'] != $developer_term->name ) {
            continue; // if developer is specified, then exclude products from other developers
          }

          if ( ! $deal_products_only && $item_result['is_deal_product'] ) {
            continue; // exclude deal products if we are NOT looking for deal products
          }

          if ( $deal_products_only && ! $item_result['is_deal_product'] ) {
            continue; // exclude non-deal products if we are looking for deal products
          }
          
          if ( $deal_products_only && $item_result['is_shop_product'] ) {
            continue; // exclude shop products if we are looking for deal products
          }

          self::log(['$item_result', $item_result ] );

          $results[] = $item_result;
          
        } // end if matched filter restriction
        
      } // end if correct product developer

    } // end foreach item
      
    self::log(['$results', $results ] );
    
    if ( $number_of_items == 1 && count($results) == 1 ) {
      $results[0]['sole_item_in_order'] = true;
    }
    
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
    
    foreach ( $developer_settings as $dev_id => $developer ) {
        
      $dev_data_from_report = $report_data['devs'][ $dev_id ] ?: false;
      
      if ( $dev_data_from_report && is_array($developer) && count($developer) ) {
        
        $dev_name = $dev_data_from_report->name;
        $earnings = $dev_data_from_report->summary['total_dev_profits'] ?: 0;

        $dev_profit_ratio = 0.01 * $developer['profit_ratio']; // ratio is saved in DB as percents. "2" is 2%, 0.02 

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
  
  
  /**
   * Called from Developer Dashboard (frontend)
   */
  public static function validate_and_generate_xlsx_report_for_developer() {

    $developer_term = Ddb_Core::find_current_developer_term();

    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {

      $action = filter_input( INPUT_POST, Ddb_Core::BUTTON_SUMBIT );
      $start_date = filter_input( INPUT_POST, Ddb_Core::FIELD_DATE_START ) ?: false;
      $end_date = filter_input( INPUT_POST, Ddb_Core::FIELD_DATE_END ) ?: false;

      $validation_failed = Ddb_Frontend::validate_input( $start_date, $end_date );

      if ( $validation_failed === false ) {
        switch ( $action ) { // this may be expanded in the future, therefore using switch
          case Ddb_Frontend::ACTION_DOWNLOAD_ORDERS_REPORT: 
            $report_generated_successfully = self::generate_xlsx_report( $developer_term, $start_date, $end_date );

            if ( $report_generated_successfully ) {
              exit(); // generate_xlsx_report() produced an output of XLSX file, so we must stop any further output
            }
          break;
        }
      }
    }
  }

  /**
   * Generates XLSX file for the developer
   * 
   * May be called both by admin (backend) and developer (frontend)
   * 
   * If called by developer, $report_settings is empty, but $developer_term must be object
   * 
   * 
   * @param object $developer_term
   * @param string $start_date
   * @param string $end_date
   * @param array $report_settings
   * @return boolean
   */
  public static function generate_xlsx_report( ?object $developer_term, string $start_date, string $end_date, array $report_settings = array() ) {
  
    self::load_options();
    
    $processed_orders = false;
    
    $orders_data = array();
    
    $developer_name = is_object( $developer_term ) ? $developer_term->name : '';

    $product_id = 0;
    $deal_products_only = false;
    
    if ( $report_settings['product_id'] ?? false ) {
      $product_id = $report_settings['product_id'];
    }
    elseif ( $report_settings['deal_product_id'] ?? false ) {
      $product_id = $report_settings['deal_product_id'];
      $deal_products_only = true;
    }
    
    if ( $developer_name || $product_id ) {
      
      $paid_order_ids = self::get_target_order_ids( $start_date, $end_date, $developer_name, $report_settings );
      
      foreach ( $paid_order_ids as $order_id ) {
        $order_lines = self::get_single_order_info( $order_id, $developer_term, $product_id, $deal_products_only );

        if ( $order_lines ) {
          $orders_data = array_merge( $orders_data, $order_lines );
        }
      }

      if ( is_array($orders_data) && count($orders_data) ) {

        $orders_data = self::remove_duplicated_order_lines( $orders_data );

				self::wc_log( "generate_xlsx_report - removed duplicates. Preparing report data....");
				
        // Prepare the body of report 

        $processed_orders = 0;

        $columns = self::$report_columns['orders'];
        $report_lines = [];
        $total = 0;
        $total_refunded = 0;
        $orders_refunded = 0;

        foreach ( $orders_data as $order_line ) {

          if ( $order_line['developer_refunded'] > 0 ) {
            $total_refunded += $order_line['developer_refunded'];
            $orders_refunded++;
          }

          $total += $order_line['after_coupon'];

          $report_line = [];
          foreach ( $columns as $key => $name ) {
            $report_line[] = $order_line[$key];
          }

          $report_lines[] = $report_line;
					
					$processed_orders++;
        }

        $total -= $total_refunded;
      
				// Prepare report summary and payout using individual developer payout settings
				$refund_summary = self::prepare_refund_summary( $total_refunded, $orders_refunded );
				$payout_summary = self::prepare_payout_summary( $total, $developer_term ); 
			
				// will be used later in generate_totals_report()
				self::$full_report_summary = array(
					'total_dev_profits'     => $total,
					'total_refunded'        => $total_refunded,
					'orders_refunded'       => $orders_refunded,
					'payout'                => $payout_summary[3]
				);
				
				
				self::wc_log( "generate_xlsx_report", self::$full_report_summary );
				
				
				
        $report_summary = array(
          [ 0 => '~~~~~~~~~~~~~~~~' ],
          $refund_summary,
          $payout_summary
        );

        $report_data = array_merge( array( 0 => array_values($columns) ), $report_lines, $report_summary );

				
				self::wc_log( "generate_xlsx_report", $report_data );
				
				self::wc_log( "generate_xlsx_report", $report_settings );
				
				
				if ( $report_settings['save_to_file'] ?? false ) {
					
					require_once( __DIR__ . '/../vendor/xlsxwriter.class.php' );
					
					$writer = new XLSXWriter();
					$writer->writeSheet( $report_data );
					$writer->writeToFile( $report_settings['save_path'] );
					
					update_option( 'ddb_last_generated_report', $report_settings['save_url'] );
					
				} else {
					
					require_once( __DIR__ . '/../vendor/xlsxwriter.class.php' );
					
					$filename = 'report_from_' . $start_date . '_to_' . $end_date;
					self::echo_headers( $filename, self::FILE_FORMAT_XLSX );
					$writer = new XLSXWriter();
					$writer->writeSheet( $report_data );
					$writer->writeToStdOut();
				}
      }
    }
    
    return $processed_orders;
  }
	
	public static function get_last_report_summary() {
		return self::$full_report_summary;
	}
	
  /**
   * Generates array with payout data for the specified developer
   * 
   * If developer is not provided, then calculate just the totals 
   * 
   * @param float $total
   * @param object $developer_term
   * @return array
   */
  public static function prepare_payout_summary( float $total, ?object $developer_term ) {
    
    $report_summary = [ 
      0 => 'Total:', 
      1 => $total
    ];
    
    if ( is_object( $developer_term ) ) {
      $payout_settings = self::find_developer_payout_settings( $developer_term );

      if ( $payout_settings['profit_ratio'] == self::USE_GLOBAL_PROFIT_RATIO ) {
        $global_profit_ratio = self::$option_values['global_default_profit_ratio'];
        $payout = $total * $global_profit_ratio;
      }
      else {
        $payout = $total * $payout_settings['profit_ratio'] * 0.01;
      }

      $report_summary[2] = 'Payout:';
      $report_summary[3] = $payout;
    }

    return $report_summary;
  }
	 
	/**
	 * Generates XLS file with developer summaries
	 * 
	 * NOT USED
	 * 
	 * @param string $start_date
	 * @param string $end_date
	 * @param array $developers
	 * @param array $report_settings
	 */
	public static function generate_totals_report( $start_date, $end_date, $developers, $report_settings ) {
		
		$columns = array(
			'Developer Name',
			'Total profit',
			'Payout'
		);
		
		$report_lines = array();
						
		foreach ( $developers as $developer ) {
			$report_lines[] = array(
				$developer->name,
				$developer->summary['total_dev_profits'],
				$developer->summary['payout'],
			);
		}
		
		$report_data = array_merge( array( 0 => array_values($columns) ), $report_lines);
		
		$filename = 'totals_report_from_' . $start_date . '_to_' . $end_date;

		require_once( __DIR__ . '/../vendor/xlsxwriter.class.php' );
		$writer = new XLSXWriter();
		$writer->writeSheet( $report_data );
		$writer->writeToFile( $report_settings['folder_path'] . '/' . $filename );
	}
	
	
  /**
   * Generates array with refund data for the report
   * 
   * If developer is not provided, then calculate just the totals 
   * 
   * @param float $total_refunded
   * @param int $count number of orders refunded
   * @return array
   */
  public static function prepare_refund_summary( float $total_refunded, int $count ) {
    
    $refund_summary = [ 
      0 => 'Total refunded:', 
      1 => $total_refunded,
      2 => 'Number of refunded orders:',
      3 => $count
    ];

    return $refund_summary;
  }
  
  
  /**
   * Generates HTML table for report about some products, based on $report_settings
   * 
   * a) $developer_term is provided: report will show all products of specified developer
   * b) $developer_term is null: report will show all sales of a product specified by $report_settings
   * 
   * @param object $developer_term
   * @param string $start_date
   * @param string $end_date
   * @param array $report_settings array [ developer_id, product_id, deal_product_id, include_free_orders ] 
   * @return string HTML for the report table
   */
  public static function generate_table_report( ?object $developer_term, string $start_date, string $end_date, array $report_settings ) {
  
    self::load_options();
    
    $out = '';
    
    $orders_data = array();

    $developer_name = is_object( $developer_term ) ? $developer_term->name : '';
    
    $paid_order_ids = self::get_target_order_ids( $start_date, $end_date, $developer_name, $report_settings );

    $deal_products_only = false;
    
    if ( $report_settings['product_id'] ?? false ) {
      $product_id = $report_settings['product_id'];
    }
    elseif ( $report_settings['deal_product_id'] ?? false ) {
      $product_id = $report_settings['deal_product_id'];
      $deal_products_only = true;
    }
    
    foreach ( $paid_order_ids as $order_id ) {
      $order_lines = self::get_single_order_info( $order_id, $developer_term, $product_id, $deal_products_only );

      if ( $order_lines ) {
        $orders_data = array_merge( $orders_data, $order_lines );
      }
    }
    
    if ( is_array($orders_data) && count($orders_data) ) {
      
      $orders_data = self::remove_duplicated_order_lines( $orders_data );
      
      // Prepare the body of report 
      
      $columns = self::$report_columns['orders'];
      
      $report_lines = [];
      $total = 0;
      $total_refunded = 0;
      $orders_refunded = 0;
      
      foreach ( $orders_data as $order_line ) {
        
        if ( $order_line['developer_refunded'] > 0 ) {
          $total_refunded += $order_line['developer_refunded'];
          $orders_refunded++;
        }
        
        $total += $order_line['after_coupon'];
        
        $report_line = [];
        foreach ( $columns as $key => $name ) {
          $report_line[] = $order_line[$key];
        }
        
        $report_lines[] = $report_line;
      }

      $total -= $total_refunded;
      
      $report_summary = array(
        [ 0 => '~~~~~~~~~~~~~~~~' ],
        self::prepare_refund_summary( $total_refunded, $orders_refunded ),
        self::prepare_payout_summary( $total, $developer_term ) // Prepare report summary and payout using individual developer payout settings
      );

      $report_data = array_merge( $report_lines, $report_summary );
      
      $out = self::format_report_data( $columns, $report_data, self::FILE_FORMAT_HTML );
    }
    else {
      $out = "<h2 style='color:red;'>Found no orders for the report ( from $start_date to $end_date, developer {$developer_term->name} )</h2>";
    }
    
    return $out;
  }
    
  public static function format_report_data( $headers, $data, $format = self::FILE_FORMAT_CSV ) {
  
    $report = '';
    switch ( $format ) {
      case self::FILE_FORMAT_HTML:
        
        $report_headers = '<thead>';
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
        
        $report = '<table class="ddb-table">' . $report_headers . '<tbody>' . $report_data . '</tbody></table>';
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