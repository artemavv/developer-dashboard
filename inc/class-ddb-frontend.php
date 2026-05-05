<?php


class Ddb_Frontend extends Ddb_Core {

  // Actions triggered by buttons in frontend area
  public const ACTION_GENERATE_SALES_REPORT = 'Show sales report';
  public const ACTION_SHOW_ORDERS_REPORT = 'Show report';
  public const ACTION_DOWNLOAD_ORDERS_REPORT = 'Export report to Excel';
  public const ACTION_CREATE_DEVELOPER_PRODUCT = 'Create product';
  public const ACTION_EDIT_DEVELOPER_PRODUCT = 'Update product';
  public const ACTION_DELETE_DEVELOPER_PRODUCT = 'Delete product';
  public const ACTION_EDIT_PRODUCT_LICENSE = 'Update license keys';
  public const NONCE_ACTION_CREATE_PRODUCT = 'ddb_create_product';
  public const NONCE_ACTION_EDIT_PRODUCT = 'ddb_edit_product';
  public const NONCE_ACTION_EDIT_LICENSE = 'ddb_edit_license';
  private static $product_form_notice_html = '';
  private static $product_form_repopulate_values = false;
  /** @var bool Set when a developer product was deleted in this request (used to show products list). */
  private static $product_deleted_this_request = false;
  /** @var bool Set when a draft product was created in this request (create-product section shows notice only). */
  private static $draft_product_created_this_request = false;

  public static function get_product_form_notice_html() {
    return self::$product_form_notice_html;
  }

  public static function should_repopulate_product_form_values() {
    return self::$product_form_repopulate_values;
  }

  /**
   * Whether the current request deleted a product via the edit form (success path).
   *
   * @return bool
   */
  public static function product_was_deleted_this_request() {
    return self::$product_deleted_this_request;
  }

  /**
   * Whether the current request created a draft product via the create form (success path).
   *
   * @return bool
   */
  public static function draft_product_created_this_request() {
    return self::$draft_product_created_this_request;
  }

  
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
  public static function render_developer_orders_dashboard( $atts ) {
    
    $out = self::notice_error_html( esc_html__( 'Not authorized', DDB_TEXT_DOMAIN ) );
    
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
      
      //$out .= self::render_deals_report_form_and_results( $developer_term );
      
      $payout_settings = self::find_developer_payout_settings( $developer_term );
      
      if ( is_array($payout_settings) && count($payout_settings) ) {
        $out .= self::render_payout_settings( $payout_settings );
      }
      
      $out .= '</div>';
    }
    
    return $out;
  }

  /**
   * "Your Products" tab: lists WooCommerce products in the `developer` taxonomy for the logged-in developer.
   *
   * @param array $atts Shortcode attributes (same as developer_dashboard).
   * @return string HTML
   */
  public static function render_developer_products_dashboard( $atts ) {
    $out = self::notice_error_html( esc_html__( 'Not authorized', DDB_TEXT_DOMAIN ) );

    $input_fields = array(
      'user_id' => 0,
    );
    extract( shortcode_atts( $input_fields, $atts ) );

    if ( 0 !== (int) $user_id ) {
      $user = get_user_by( 'id', (int) $user_id );
    } else {
      $user = wp_get_current_user();
    }

    $developer_term = false;
    if ( $user && is_array( $user->roles ) && in_array( self::DEV_ROLE_NAME, $user->roles, true ) ) {
      $developer_term = self::find_developer_term_by_user_id( $user->ID );
    }

    if ( ! is_object( $developer_term ) || ! is_a( $developer_term, 'WP_Term' ) ) {
      return $out;
    }

    if ( ! post_type_exists( 'product' ) ) {
      return '<div id="developer-dashboard" class="ddb-developer-products"><p>' . esc_html__( 'The product catalog is not available on this site.', DDB_TEXT_DOMAIN ) . '</p></div>';
    }

    $all_products = get_posts(
      array(
        'post_type'              => 'product',
        'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
        'posts_per_page'         => -1,
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
        'tax_query'              => array(
          array(
            'taxonomy' => 'developer',
            'field'    => 'term_id',
            'terms'    => (int) $developer_term->term_id,
          ),
        ),
      )
    );

    $published   = array();
    $unpublished = array();
    foreach ( $all_products as $product_post ) {
      if ( 'publish' === $product_post->post_status ) {
        $published[] = $product_post;
      } else {
        $unpublished[] = $product_post;
      }
    }

    $url_product_wizard = esc_url( add_query_arg( 'section', 'product-wizard' ) );

    $out  = '<div id="developer-dashboard" class="ddb-developer-products">';
    $out .= '<div class="ddb-developer-products__header">';
    $out .= '<h2>' . esc_html__( 'Your products', DDB_TEXT_DOMAIN ) . '</h2>';
    $out .= '</div>';
    $out .= self::render_developer_product_list_section( __( 'Published', DDB_TEXT_DOMAIN ), $published, true );
    $out .= self::render_developer_unpublished_product_list( __( 'Unpublished', DDB_TEXT_DOMAIN ), $unpublished );
    $out .= '</div>';

    $out .= '<p class="ddb-developer-products__actions">'
      . '<a class="ddb-button-create-product" href="' . $url_product_wizard . '">' . esc_html__( 'Add product', DDB_TEXT_DOMAIN ) . '</a> '
      . '</p>';

    return $out;
  }

  /**
   * Process create-product POST: draft WooCommerce product + developer taxonomy.
   *
   * @param WP_Term $developer_term Current developer taxonomy term.
   * @return string|int HTML notice (escaped), or 1 when a draft product was created successfully.
   */
  private static function handle_developer_create_product_submission( $developer_term ) {
    if ( ! isset( $_POST['ddb_create_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddb_create_product_nonce'] ) ), self::NONCE_ACTION_CREATE_PRODUCT ) ) {
      return self::notice_error_html( esc_html__( 'Security check failed. Please try again.', DDB_TEXT_DOMAIN ) );
    }

    $form_data = Ddb_Product_Form::collect_product_form_data_from_request();

    if ( '' === $form_data['title'] ) {
      return self::notice_error_html( esc_html__( 'Please enter a product title.', DDB_TEXT_DOMAIN ) );
    }

    $product_id = self::insert_draft_product_for_developer( $form_data, $developer_term );
    if ( is_wp_error( $product_id ) ) {
      return self::notice_error_html( esc_html( $product_id->get_error_message() ) );
    }
    if ( ! $product_id ) {
      return self::notice_error_html( esc_html__( 'Could not create the product. Please try again or contact support.', DDB_TEXT_DOMAIN ) );
    }

    // to set the default template for the product
    update_post_meta( $product_id, '_jet_woo_template', 1498604 );
    update_post_meta( $product_id, 'select-template-type-for-product-page', 'singleshopproducttemplate' );
		update_post_meta( $product_id, 'enable-overview-section', '1' );
		update_post_meta( $product_id, 'enable_media_section', '1' );
		update_post_meta( $product_id, 'enable-soundcloud--key-features-section', '1' );
		update_post_meta( $product_id, 'enable-other-product-information-section', '1' );
    update_post_meta( $product_id, 'overview-heading', 'Overview' );
    update_post_meta( $product_id, 'media-title', 'Media' );
    update_post_meta( $product_id, 'key-features', 'Key Features' );
    update_post_meta( $product_id, 'pending_apd_review', self::STATUS_DRAFT );
    
    
    self::apply_wizard_product_meta_defaults( $product_id );

    return (int) $product_id;
  }

  /**
   * Process edit-product POST: update title and short description for a developer-owned product.
   *
   * @param WP_Term $developer_term Current developer taxonomy term.
   * @return string HTML notice (escaped).
   */
  private static function handle_developer_edit_product_submission( $developer_term ) {
    if ( ! isset( $_POST['ddb_create_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddb_create_product_nonce'] ) ), self::NONCE_ACTION_EDIT_PRODUCT ) ) {
      return self::notice_error_html( esc_html__( 'Security check failed. Please try again.', DDB_TEXT_DOMAIN ) );
    }

    $product_id = absint( filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
    if ( ! $product_id ) {
      $product_id = absint( filter_input( INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
    }
    if ( ! $product_id ) {
      return self::notice_error_html( esc_html__( 'Invalid product ID.', DDB_TEXT_DOMAIN ) );
    }

    $developer_user_id = (int) get_term_meta( (int) $developer_term->term_id, 'user_account', true );
    if ( ! self::is_product_from_developer( $product_id, $developer_user_id ) ) {
      return self::notice_error_html( esc_html__( 'You are not allowed to edit this product.', DDB_TEXT_DOMAIN ) );
    }

    if ( Ddb_Product_Wizard::is_deal_product( $product_id ) ) {
      return self::notice_error_html( esc_html__( 'Deal products cannot be edited with the wizard.', DDB_TEXT_DOMAIN ) );
    }

    $form_data = Ddb_Product_Form::collect_product_form_data_from_request();

    if ( '' === $form_data['title'] ) {
      return self::notice_error_html( esc_html__( 'Please enter a product title.', DDB_TEXT_DOMAIN ) );
    }

    
    $product               = wc_get_product( $product_id );
    $was_published         = ( 'publish' === get_post_status( $product_id ) );
    $was_draft             = ( 'draft' === get_post_status( $product_id ) );
    $pending_review_status = (string) get_post_meta( $product_id, 'pending_apd_review', true );
    $edit_product_origin   = filter_input( INPUT_POST, 'ddb_edit_product_origin', FILTER_DEFAULT );
    $is_wizard_edit_submit = ( is_string( $edit_product_origin ) && 'wizard' === sanitize_key( wp_unslash( $edit_product_origin ) ) );

    if ( ! $product ) {
      return self::notice_error_html( esc_html__( 'Could not load the product.', DDB_TEXT_DOMAIN ) );
    }
    
    $product->set_name( $form_data['title'] );
    $product->set_short_description( $form_data['short_description'] );
    if ( '' !== $form_data['slug'] ) {
      $product->set_slug( $form_data['slug'] );
    }
    $regular_price = isset( $form_data['regular_price'] ) ? (string) $form_data['regular_price'] : '';
    $product->set_regular_price( $regular_price );
    if ( $was_published && $is_wizard_edit_submit ) {
      $product->set_status( 'draft' );
    }
    try {
      $product->save();

    } catch ( Exception $e ) {
      return self::notice_error_html( esc_html__( 'Could not save the product.', DDB_TEXT_DOMAIN ) );
    }
  

    $result = self::handle_developer_product_license_submission( $developer_term );

    Ddb_Product_Form::persist_product_meta( $product_id, $form_data['meta'] );
    self::apply_wizard_product_meta_defaults( $product_id );

    if ( $was_published && $is_wizard_edit_submit ) {
      update_post_meta( $product_id, 'pending_apd_review', self::STATUS_PUBLISHED_AND_EDITED );
    } elseif ( $was_draft && self::STATUS_PUBLISHED_AND_EDITED !== $pending_review_status ) {
      update_post_meta( $product_id, 'pending_apd_review', self::STATUS_DRAFT );
    }

    Ddb_Product_Form::apply_product_taxonomy_fields_from_request( $product_id );
    update_post_meta( $product_id, 'apd_dev_last_time_edited', time() );

    $upload_result = Ddb_Product_Form::process_product_image_uploads( $product_id );

    if ( is_wp_error( $upload_result ) ) {
      $result .= self::notice_error_html( esc_html( $upload_result->get_error_message() ) );
    }
    else {
      if ( $was_published && $is_wizard_edit_submit ) {
        $result .= self::notice_success_html_with_products_link( esc_html__( 'Product updated and moved to draft for review.', DDB_TEXT_DOMAIN ), $product_id );
      } else {
        $result .= self::notice_success_html_with_products_link( esc_html__( 'Product updated.', DDB_TEXT_DOMAIN ), $product_id );
      }
    }

    return $result;
  }

  /**
   * Apply wizard-only product meta defaults and normalizations.
   *
   * @param int $product_id Product ID.
   * @return void
   */
  private static function apply_wizard_product_meta_defaults( $product_id ) {
    $active_wizard_step = filter_input( INPUT_POST, 'ddb_active_wizard_step', FILTER_DEFAULT );
    if ( ! is_string( $active_wizard_step ) || '' === $active_wizard_step ) {
      return;
    }


    $value_price = get_post_meta( $product_id, 'value-price', true );
    if ( ! is_string( $value_price ) ) {
      return;
    }

    $value_price = trim( $value_price );
    if ( '' === $value_price ) {
      return;
    }

    if ( false === strpos( $value_price, '$' ) && false === stripos( $value_price, 'USD' ) ) {
      update_post_meta( $product_id, 'value-price', '$' . $value_price );
    }
  }

  /**
   * Process delete-product POST for a developer-owned unpublished product.
   *
   * @param WP_Term $developer_term Current developer taxonomy term.
   * @return string HTML notice (escaped).
   */
  private static function handle_developer_delete_product_submission( $developer_term ) {
    if ( ! isset( $_POST['ddb_create_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddb_create_product_nonce'] ) ), self::NONCE_ACTION_EDIT_PRODUCT ) ) {
      return self::notice_error_html( esc_html__( 'Security check failed. Please try again.', DDB_TEXT_DOMAIN ) );
    }

    $product_id = absint( filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
    if ( ! $product_id ) {
      return self::notice_error_html( esc_html__( 'Invalid product ID.', DDB_TEXT_DOMAIN ) );
    }

    $developer_user_id = (int) get_term_meta( (int) $developer_term->term_id, 'user_account', true );
    if ( ! self::is_product_from_developer( $product_id, $developer_user_id ) ) {
      return self::notice_error_html( esc_html__( 'You are not allowed to delete this product.', DDB_TEXT_DOMAIN ) );
    }

    if ( 'publish' === get_post_status( $product_id ) ) {
      return self::notice_error_html( esc_html__( 'Published products cannot be deleted from the developer dashboard.', DDB_TEXT_DOMAIN ) );
    }

    if ( function_exists( 'wc_get_product' ) ) {
      $wc_product = wc_get_product( $product_id );
      if ( $wc_product ) {
        $wc_product->delete( true );
      } else {
        wp_delete_post( $product_id, true );
      }
    } else {
      wp_delete_post( $product_id, true );
    }

    if ( get_post( $product_id ) ) {
      return self::notice_error_html( esc_html__( 'Could not delete the product. Please try again.', DDB_TEXT_DOMAIN ) );
    }

    self::$product_deleted_this_request = true;

    return self::notice_success_html_with_products_link( esc_html__( 'Product deleted.', DDB_TEXT_DOMAIN ) );
  }

  /**
   * Process license-keys POST for a developer-owned product.
   *
   * @param WP_Term $developer_term Current developer taxonomy term.
   * @return string HTML notice (escaped).
   */
  private static function handle_developer_product_license_submission( $developer_term ) {
    if ( ! isset( $_POST['ddb_edit_license_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddb_edit_license_nonce'] ) ), self::NONCE_ACTION_EDIT_LICENSE ) ) {
      return ''; //self::notice_error_html( esc_html__( 'Security check failed. Please try again.', DDB_TEXT_DOMAIN ) );
    }

    $product_id = absint( filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
    if ( ! $product_id ) {
      return self::notice_error_html( esc_html__( 'Invalid product ID.', DDB_TEXT_DOMAIN ) );
    }

    $developer_user_id = (int) get_term_meta( (int) $developer_term->term_id, 'user_account', true );
    if ( ! self::is_product_from_developer( $product_id, $developer_user_id ) ) {
      return self::notice_error_html( esc_html__( 'You are not allowed to edit licenses for this product.', DDB_TEXT_DOMAIN ) );
    }

    $raw = isset( $_POST['ddb_product_license_keys'] ) ? wp_unslash( $_POST['ddb_product_license_keys'] ) : '';
    if ( ! is_string( $raw ) ) {
      $raw = '';
    }

    $result = Ddb_License_Manager::sync_draft_licenses_from_text( $product_id, $raw, $developer_user_id );
    if ( is_wp_error( $result ) ) {
      return self::notice_error_html( esc_html( $result->get_error_message() ) );
    }
    elseif ( intval( $result ) > 0) {

      if ( intval( $result ) == 1 ) {
        $message = esc_html__( 'License keys are updated, but one key was skipped as invalid.', DDB_TEXT_DOMAIN );
      }
      else {
        $message = 'License keys are updated, but ' . intval( $result ) . ' keys were skipped as invalid.';
      }

      return self::notice_success_html( $message );
    }

    return ''; // no message indicates success, no need to print anything
  }

  /**
   * @param array{
   *   title: string,
   *   short_description: string,
   *   slug: string,
   *   regular_price: string,
   *   meta: array<string, string|array>
   * }         $form_data      Sanitized output of Ddb_Product_Form::collect_product_form_data_from_request().
   * @param WP_Term $developer_term Assigned developer term.
   * @return int|\WP_Error|false Product ID on success.
   */
  private static function insert_draft_product_for_developer( array $form_data, $developer_term ) {
    $title           = $form_data['title'] ?? '';
    $short           = $form_data['short_description'] ?? '';
    $slug            = $form_data['slug'] ?? '';
    $regular_price   = isset( $form_data['regular_price'] ) ? (string) $form_data['regular_price'] : '';
    $meta            = isset( $form_data['meta'] ) && is_array( $form_data['meta'] ) ? $form_data['meta'] : array();

    if ( class_exists( 'WC_Product_Simple' ) ) {
      try {
        $product = new WC_Product_Simple();
        $product->set_name( $title );
        $product->set_short_description( $short );
        if ( '' !== $slug ) {
          $product->set_slug( $slug );
        }
        $product->set_regular_price( $regular_price );
        $product->set_status( 'draft' );
        $product_id = $product->save();
      } catch ( Exception $e ) {
        return new WP_Error( 'ddb_wc_product', __( 'Could not save the product. Code 25', DDB_TEXT_DOMAIN ) );
      }
      if ( ! $product_id ) {
        return new WP_Error( 'ddb_wc_product', __( 'Could not save the product. Code 26', DDB_TEXT_DOMAIN ) );
      }
    } else {
      return new WP_Error( 'ddb_wc_product', __( 'Could not save the product. Code 27', DDB_TEXT_DOMAIN ) );
    }

    if ( (int) $product_id ) {
      $assigned = wp_set_object_terms( (int) $product_id, array( (int) $developer_term->term_id ), 'developer' );
      if ( is_wp_error( $assigned ) ) {
        wp_delete_post( (int) $product_id, true );
        return $assigned;
      }
      Ddb_Product_Form::persist_product_meta( (int) $product_id, $meta );

      Ddb_Product_Form::apply_product_taxonomy_fields_from_request( (int) $product_id );

      $upload_result = Ddb_Product_Form::process_product_image_uploads( (int) $product_id );
      if ( is_wp_error( $upload_result ) ) {
        return $upload_result;
      }
    }

    return (int) $product_id;
  }

  /**
   * @param string $heading Section title.
   * @param array  $posts   List of WP_Post (product).
   * @param bool   $as_public_links When true, published items link to the storefront URL.
   * @return string HTML
   */
  private static function render_developer_product_list_section( $heading, array $posts, $as_public_links ) {
    $html = '<section class="ddb-product-list-section"><h4>' . esc_html( $heading ) . '</h4>';
    if ( empty( $posts ) ) {
      $html .= '<p class="ddb-product-list-empty">' . esc_html__( 'No products in this list.', DDB_TEXT_DOMAIN ) . '</p>';
    } else {
      $html .= '<div class="ddb-product-table-wrapper"><table class="ddb-product-table products-table"><thead><tr>';
      $html .= '<th scope="col" class="ddb-product-table__col-title">' . esc_html__( 'Product title', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-price">' . esc_html__( 'Product price', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-licenses">' . esc_html__( 'Available license keys', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-deal">' . esc_html__( 'Deal product', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-action">' . esc_html__( 'Actions', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '</tr></thead><tbody>';
      foreach ( $posts as $post ) {
        $product_id = (int) $post->ID;
        $is_deal_product = Ddb_Product_Wizard::is_deal_product( $product_id );
        $wizard_edit_url = add_query_arg(
          array(
            'section'    => 'product-wizard',
            'product_id' => $product_id,
          ),
          ''
        );
        $html      .= '<tr class="ddb-product-table__row ddb-product-table__row--' . esc_attr( $post->post_status ) . '">';
        $html      .= '<td class="ddb-product-table__title">';
        if ( $as_public_links && 'publish' === $post->post_status ) {
          $html .= '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
        } else {
          $html .= '<span class="ddb-product-table__title-text">' . esc_html( get_the_title( $post ) ) . '</span>';
          $html .= ' <span class="ddb-product-table__status">(' . esc_html( self::get_product_status_label( $post->post_status ) ) . ')</span>';
        }
        $html .= '</td>';
        $html .= '<td class="ddb-product-table__price">' . self::get_developer_dashboard_product_price_html( $product_id ) . '</td>';
        $available_licenses = Ddb_License_Manager::count_licenses( $product_id, 'available' );
        $html              .= '<td class="ddb-product-table__licenses">' . esc_html( (string) $available_licenses ) . '</td>';
        $html           .= '<td class="ddb-product-table__deal">' . ( $is_deal_product ? '+' : '' ) . '</td>';
        $html           .= '<td class="ddb-product-table__action">';
        if ( ! $is_deal_product ) {
          $html           .= '<a class="button ddb-button-edit-wizard" href="' . esc_url( $wizard_edit_url ) . '">' . esc_html__( 'Edit product', DDB_TEXT_DOMAIN ) . '</a>';
        }
        else {
          $html           .= '<a class="button ddb-button-disabled" disabled="disabled" href="#">Not editable</a>';
        }
        $html           .= '</td>';
        $html           .= '</tr>';
      }
      $html .= '</tbody></table></div>';
    }
    $html .= '</section>';
    return $html;
  }

  /**
   * Render unpublished products with edit action.
   *
   * @param string $heading Section title.
   * @param array  $posts   List of WP_Post (product).
   * @return string HTML
   */
  private static function render_developer_unpublished_product_list( $heading, array $posts ) {
    $html = '<section class="ddb-product-list-section"><h4>' . esc_html( $heading ) . '</h4>';
    if ( empty( $posts ) ) {
      $html .= '<p class="ddb-product-list-empty">' . esc_html__( 'No products in this list.', DDB_TEXT_DOMAIN ) . '</p>';
    } else {
      $html .= '<div class="ddb-product-table-wrapper"><table class="ddb-product-table products-table"><thead><tr>';
      $html .= '<th scope="col" class="ddb-product-table__col-title">' . esc_html__( 'Product title', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-status">' . esc_html__( 'Status', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-licenses">' . esc_html__( 'Draft keys', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-licenses">' . esc_html__( 'Available keys', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '<th scope="col" class="ddb-product-table__col-action">' . esc_html__( 'Actions', DDB_TEXT_DOMAIN ) . '</th>';
      $html .= '</tr></thead><tbody>';
      foreach ( $posts as $post ) {
        $product_id = (int) $post->ID;
        $is_deal_product = Ddb_Product_Wizard::is_deal_product( $product_id );
        $wizard_edit_url = add_query_arg(
          array(
            'section'    => 'product-wizard',
            'product_id' => $product_id,
          ),
          ''
        );

        $licenses_url   = add_query_arg(
          array(
            'section'    => 'edit-product',
            'product_id' => $product_id,
            'ddb_tab'    => 'license-keys',
          ),
          ''
        );

        $licenses_count = Ddb_License_Manager::count_licenses( $product_id, 'draft' );
        $available_licenses = Ddb_License_Manager::count_licenses( $product_id, 'available' );

        $title_text   = get_the_title( $post );
        $preview_link = get_preview_post_link( $post );
        $preview_url  = ( is_string( $preview_link ) && '' !== $preview_link ) ? $preview_link : '';
        $title_cell   = ! $is_deal_product
          ? '<a class="ddb-product-table__title-link" href="' . esc_url( $wizard_edit_url ) . '">' . esc_html( $title_text ) . '</a>'
          : '<span class="ddb-product-table__title-text">' . esc_html( $title_text ) . '</span>';
        $preview_target_url = '' !== $preview_url ? $preview_url : ( ! $is_deal_product ? $wizard_edit_url : '' );

        $html      .= '<tr class="ddb-product-table__row ddb-product-table__row--' . esc_attr( $post->post_status ) . '">';
        $html      .= '<td class="ddb-product-table__title">' . $title_cell . '</td>';
        $html      .= '<td class="ddb-product-table__status">' . esc_html( self::get_unpublished_product_status_label( $post->post_status ) ) . '</td>';
        $html      .= '<td class="ddb-product-table__licenses">' . esc_html( (string) $licenses_count ) . '</td>';
        $html      .= '<td class="ddb-product-table__licenses">' . esc_html( (string) $available_licenses ) . '</td>';
        $html .= '<td class="ddb-product-table__action">';
        if ( ! $is_deal_product ) {
          $html .= '<a class="button ddb-button-edit-wizard" href="' . esc_url( $wizard_edit_url ) . '">' . esc_html__( 'Edit product', DDB_TEXT_DOMAIN ) . '</a>';
        }
        if ( '' !== $preview_target_url ) {
          $html .= '<a class="button ddb-button-edit-draft" href="' . esc_url( $preview_target_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Preview on website', DDB_TEXT_DOMAIN ) . '</a>';
        }
        $html .= '<a class="button ddb-button-edit-licenses" href="' . esc_url( $licenses_url ) . '">' . esc_html__( 'License keys', DDB_TEXT_DOMAIN ) . '</a>';
        $html .= '</td>';
        $html .= '</tr>';
      }
      $html .= '</tbody></table></div>';
    }
    $html .= '</section>';
    return $html;
  }

  /**
   * @param string $status Raw post status slug.
   * @return string Human-readable label for unpublished products.
   */
  private static function get_unpublished_product_status_label( $status ) {
    if ( 'draft' === $status ) {
      return __( 'Draft', DDB_TEXT_DOMAIN );
    }
    if ( 'private' === $status ) {
      return __( 'In Review', DDB_TEXT_DOMAIN );
    }
    return __( 'In Review', DDB_TEXT_DOMAIN );
  }

  /**
   * Formatted product price for the developer products table.
   *
   * @param int $product_id Product post ID.
   * @return string HTML (escaped where plain text).
   */
  private static function get_developer_dashboard_product_price_html( $product_id ) {
    if ( function_exists( 'wc_get_product' ) ) {
      $wc_product = wc_get_product( $product_id );
      if ( $wc_product ) {
        return wp_kses_post( $wc_product->get_price_html() );
      }
    }
    $price = get_post_meta( $product_id, '_price', true );
    if ( '' !== $price && false !== $price && function_exists( 'wc_price' ) ) {
      return wp_kses_post( wc_price( $price ) );
    }
    return esc_html__( 'N/A', DDB_TEXT_DOMAIN );
  }

  /**
   * @param string $status Raw post status slug.
   * @return string Human-readable label.
   */
  private static function get_product_status_label( $status ) {
    $object = get_post_status_object( $status );
    if ( $object && ! empty( $object->label ) ) {
      return $object->label;
    }
    return $status;
  }

  /**
   * Wraps escaped text in the standard dashboard error notice markup.
   *
   * @param string $message Escaped HTML-safe message body.
   * @return string
   */
  public static function notice_error_html( $message ) {
    return '<p class="ddb-notice ddb-notice--error">' . $message . '</p>';
  }

  /**
   * Wraps escaped text in the standard dashboard success notice markup.
   *
   * @param string $message Escaped HTML-safe message body.
   * @return string
   */
  private static function notice_success_html( $message ) {
    return '<p class="ddb-notice ddb-notice--success">' . $message . '</p>';
  }

  /**
   * Success notice with a trailing link to the developer products list.
   *
   * @param string $leading_message Escaped translated leading sentence.
   * @return string
   */
  private static function notice_success_html_with_products_link( $leading_message, $product_id = 0 ) {
    $products_url = esc_url( remove_query_arg( 'product_id', add_query_arg( 'section', 'products' ) ) );
    $message      = $leading_message . ' <a href="' . $products_url . '">' . esc_html__( 'View your products', DDB_TEXT_DOMAIN ) . '</a>.';
    $product_id   = absint( $product_id );

    if ( $product_id > 0 ) {
      $product_post = get_post( $product_id );
      if ( $product_post && 'product' === $product_post->post_type ) {
        $preview_link = get_preview_post_link( $product_post );
        if ( ! is_string( $preview_link ) || '' === $preview_link ) {
          $preview_link = get_permalink( $product_id );
        }

        if ( is_string( $preview_link ) && '' !== $preview_link ) {
          $message .= ' <a href="' . esc_url( $preview_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Show product preview on website', DDB_TEXT_DOMAIN ) . '</a>.';
        }
      }
    }

    return self::notice_success_html( $message );
  }

  /**
   * Checks whether a product belongs to the developer term linked to a user.
   *
   * @param int $product_id Product post ID.
   * @param int $developer_user_id Developer user ID.
   * @return bool
   */
  public static function is_product_from_developer( $product_id, $developer_user_id ) {
    $product_id        = absint( $product_id );
    $developer_user_id = absint( $developer_user_id );

    if ( ! $product_id || ! $developer_user_id ) {
      return false;
    }

    $product_post = get_post( $product_id );
    if ( ! $product_post || 'product' !== $product_post->post_type ) {
      return false;
    }

    $developer_term = self::find_developer_term_by_user_id( $developer_user_id );
    if ( ! is_object( $developer_term ) || ! is_a( $developer_term, 'WP_Term' ) ) {
      return false;
    }

    return has_term( (int) $developer_term->term_id, 'developer', $product_id );
  }

  /**
   * Handle create/edit product and license form submissions from the dashboard.
   *
   * @param string|false|null $action Value of the submit button (see BUTTON_SUMBIT).
   * @return string|null Notice HTML if this was a product-related action; null otherwise.
   */
  private static function do_product_action( $action ) {

    $developer_term = self::find_current_developer_term();

    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term' ) ) {
      if ( self::ACTION_EDIT_DEVELOPER_PRODUCT === $action ) {
        $notice_html = self::handle_developer_edit_product_submission( $developer_term );
      } elseif ( self::ACTION_DELETE_DEVELOPER_PRODUCT === $action ) {
        $notice_html = self::handle_developer_delete_product_submission( $developer_term );
      } else {
        $notice_html = self::handle_developer_create_product_submission( $developer_term );
      }
    } else {
      $notice_html = self::notice_error_html( esc_html__( 'Not authorized', DDB_TEXT_DOMAIN ) );
    }

    if ( is_int( $notice_html ) && $notice_html > 0 ) {
      self::$draft_product_created_this_request = true;
      $notice_html                              = self::notice_success_html_with_products_link( esc_html__( 'Draft product created.', DDB_TEXT_DOMAIN ) );
      self::$product_form_notice_html           = $notice_html;
      self::$product_form_repopulate_values     = false;
    } else {
      self::$product_form_notice_html       = $notice_html;
      self::$product_form_repopulate_values = ( strpos( (string) $notice_html, 'ddb-notice--success' ) === false );
    }

    return $notice_html;
  }

  public static function do_frontend_action() {

    $out = '';

    $action = filter_input( INPUT_POST, self::BUTTON_SUMBIT );

    self::$product_form_notice_html           = '';
    self::$product_form_repopulate_values     = false;
    self::$product_deleted_this_request       = false;
    self::$draft_product_created_this_request = false;

    $product_actions = array(
      self::ACTION_CREATE_DEVELOPER_PRODUCT,
      self::ACTION_EDIT_DEVELOPER_PRODUCT,
      self::ACTION_DELETE_DEVELOPER_PRODUCT,
      self::ACTION_EDIT_PRODUCT_LICENSE,
    );

    if ( in_array( $action, $product_actions, true ) ) {
      
      $product_result = self::do_product_action( $action );
      if ( null !== $product_result ) {
        return $product_result;
      }
    }

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
      $out = self::notice_error_html( esc_html__( 'Not Authorized to view this report', DDB_TEXT_DOMAIN ) );
    }
    
    return $out;
  }
  
  /**
   * Check the form input and returns HTML with report of requested type
   * 
   * Called from Developer Dashboard (frontend)
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
    
        $paid_order_ids = Ddb_Report_Generator::get_target_order_ids( $start_date, $end_date, $developer_term->name );

        foreach ( $paid_order_ids as $order_id ) {
          $order_lines = Ddb_Report_Generator::get_single_order_info( $order_id, $developer_term );

          if ( $order_lines ) {
            $report_data = array_merge( $report_data, $order_lines );
          }
        }
    
        if ( is_array($report_data) && count($report_data) ) {
         

          $report_data = Ddb_Report_Generator::remove_duplicated_order_lines( $report_data );

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
      $actual_ratio = $payout_settings['profit_ratio'];
    }

    $out .= '<div style="display:inline-block; width: 40%"><h2>Payout settings</h2>';
    
    $out .= '<p><strong>Profit ratio</strong>: ' . ( $actual_ratio ) . '%</p>';
    $out .= '<p><strong>Payment method</strong>: ' . ( self::$payment_methods_names[$payout_settings['payment_method']] ?? '?' ) . '</p>';

    if ( $payout_settings['payment_method'] == self::PM__PAYPAL && $payout_settings['paypal_address'] ) {
      $out .= '<p><strong>PayPal address</strong>: ' . $payout_settings['paypal_address'] . '</p>';
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
  
  /**
   * Displays developer orders completed since the start of payment cycle.
   * 
   * New cycle starts 20th of each month.
   * 
   * @param object $developer_term
   * @return string html
   */
  public static function render_orders_in_current_cycle( object $developer_term ) {
    
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
        
        $report_data = Ddb_Report_Generator::remove_duplicated_order_lines( $report_data );
        
        $num = count($report_data);
        
        $out = self::render_orders_list( $report_data, 'orders' );
      }
      else {
        $out = "<h3>Found no orders with products by {$developer_term->name} in the current cycle</h3>";
      }
      
      $current_day = date('j');

      // Expected output:
      // December 25th:  Current cycle is from December 20th to January 19th
      // June 12th:  Current cycle is from May 20th to June 19th

      if ( $current_day > 20 ) {
        $month1 = date('F'); // current month
        $month2 = date('F', time() - 30 * 86400); // next month
      }
      else {
        $month1 = date('F', time() - 30 * 86400); // previous month
        $month2 = date('F'); // current month
      }
        
      $out .= "<small>Current payment cycle is from $month1 20th to $month2 19th</small>";
    }

    return $out;
  }
  
  
  public static function render_last_n_deal_orders( object $developer_term ) {
    $out = '';
    
    if ( is_object( $developer_term ) && is_a( $developer_term, 'WP_Term') ) {
      
      $report_data = array();

      $last_order_ids = Ddb_Report_Generator::get_last_order_ids( $developer_term->name, 20, true );

      foreach ($last_order_ids as $order_id ) {
        $order_lines = Ddb_Report_Generator::get_single_order_info( $order_id, $developer_term );

        if ( $order_lines ) {
          $report_data = array_merge( $report_data, $order_lines );
        }
      }
        
      if ( is_array($report_data) && count($report_data) ) {
        
        // $num = count($report_data); $out = "<h3>Last {$num} orders with products by {$developer_term->name}</h3>";
        $out = self::render_orders_list( $report_data, 'orders' );
      }
      else {
        $out = "<h3>Found no deal sales of products by {$developer_term->name}</h3>";
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
      $action_results = self::do_frontend_action(); // generate orders report if requested by a user
    }
    else {
      $action_results = self::render_orders_in_current_cycle( $developer_term );
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
    <form method="POST" enctype="multipart/form-data">
      
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
  
  
  /**
   * Shows list of deal sales and report generation form
   * 
   * @param object $developer_term
   * @return string html
   */
  public static function render_deals_report_form_and_results( object $developer_term ) {
    
    ob_start();
    
    if ( filter_input( INPUT_POST, self::BUTTON_SUMBIT ) ) {
      $action_results = self::do_frontend_action(); // generate deals report if requested by a user
    }
    else {
      $action_results = self::render_last_n_deal_orders( $developer_term );
    }
    
    return $action_results; 
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
        $payout = $total * $payout_settings['profit_ratio'] * 0.01; // ratio is saved in DB as percents. "2" is 2%, 0.02 
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