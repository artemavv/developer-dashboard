<?php

class Ddb_Product_Form extends Ddb_Core {

	/**
	 * Product form sections and fields. Keys are section ids; each value is a map of field id => definition.
	 * Optional 'name' overrides the POST input name (default: ddb_pf_{field_id} with hyphens as underscores).
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	public static $fields = array(
		'general' => array(
			'product_name' => array(
				'name'        => 'ddb_product_title',
				'label'       => 'Product title',
				'type'        => 'text',
				'required'    => true,
				'class'       => 'ddb-text-field large-text',
				'maxlength'   => 200,
				'description' => '',
			),
			'short_description' => array(
				'name'        => 'ddb_product_short_description',
				'label'       => 'Short description',
				'type'        => 'richtext',
				'required'    => true,
				'cols'        => 40,
				'rows'        => 15,
				'class'       => 'large-text',
				'description' => '',
			),
			'image_for_single_shop_page' => array(
				'label'       => 'Main Header Image',
				'type'        => 'image',
				'required'    => false,
				'meta_key'    => true,
				'description' => 'Recommended 1920x300 (32:5).',
			),
			'_thumbnail_id' => array(
				'label'       => 'Top Section Product Image',
				'type'        => 'image',
				'required'    => false,
				'meta_key'    => true,
				'description' => 'Recommended 314x440 (1:1.4).',
			),
			'permalink_slug' => array(
				'label'       => 'Product URL slug',
				'type'        => 'text',
				'required'    => false,
				'description' => '',
			),
			'regular_price' => array(
				'label'       => 'Regular price',
				'type'        => 'number',
				'required'    => false,
				'class'       => 'ddb-text-field',
				'min'         => '0',
				'step'        => '0.01',
				'description' => '',
			),
		),
		'overview' => array(
			/*'overview-heading' => array(
				'label'       => 'Overview Section Heading',
				'type'        => 'text',
				'required'    => false,
				'meta_key'    => true,
				'description' => 'This is the heading text that will be shown on top of the section.',
			),*/
			'description_overview' => array(
				'label'       => 'Overview Section Subheading',
				'type'        => 'text',
				'required'    => false,
				'meta_key'    => true,
				'description' => 'This is the subheading text that will be shown below the heading in the overview section.',
			),
			'overiew-product-title' => array( // exact actual field name: overiew-product-title , with a typo
				'label'       => 'Overview Product Title',
				'type'        => 'text',
				'required'    => false,
				'meta_key'    => true,
				'description' => '',
			),
			'overview-product-description' => array(
				'label'       => 'Overview Product Description',
				'type'        => 'richtext',
				'required'    => false,
				'meta_key'    => true,
				'description' => '',
			),
			'overiew-product-image' => array( // exact actual field name: overiew-product-image , with a typo
				'label'       => 'Overview Section Product Image',
				'type'        => 'image',
				'required'    => false,
				'meta_key'    => true,
				'description' => 'Preferably with transparent background.',
			),
			'overview-image' => array(
				'label'       => 'Overview Section Header Background Image',
				'type'        => 'image',
				'required'    => false,
				'meta_key'    => true,
				'description' => 'Recommended 1920x300 (32:5).',
			),
			'button-url' => array(
				'label'       => 'Developer Product URL',
				'type'        => 'url',
				'required'    => false,
				'meta_key'    => true,
				'description' => '',
			),
			'value-price' => array(
				'label'       => 'Overview Price (USD)',
				'type'        => 'text',
				'required'    => false,
				'meta_key'    => true,
				'description' => '',
			)
		),
		'media' => array(/*
			'media-title' => array(
				'label'       => 'Media Section Header Text',
				'type'        => 'text',
				'required'    => false,
                'meta_key'    => true,
				'description' => '',
			),*/
			'media-background-image' => array(
				'label'       => 'Media Section Header Background Image',
				'type'        => 'image',
				'required'    => false,
				'meta_key'    => true,
				'description' => 'Recommended 1920x300 (32:5).',
			),
			'media-description' => array(
				'label'       => 'Media Section Description',
				'type'        => 'text', // short text with a single line 
				'required'    => false,
                'meta_key'    => true,
				'description' => '',
			),
			'youtube-videos' => array(
				'label'       => 'YouTube playlist enabled',
				'type'        => 'checkbox',
				'required'    => false,
                'meta_key'    => true,
				'description' => 'Turning on this checkbox will hide the single YouTube video block and will enable YouTube playlist.',
			),
			'video_url_for_mainpage_banner' => array(
				'label'       => 'YouTube Video URL',
				'type'        => 'url',
				'required'    => false,
                'meta_key'    => true,
				'description' => '',
			),
			'youtube_playlist_url' => array(
				'label'       => 'YouTube Playlist ID',
				'type'        => 'text',
				'required'    => false,
                'meta_key'    => true,
				'description' => '',
			),
		),
		'emails' => array(
			'apd_product_email_heading' => array(
				'label'       => 'Email subject',
				'type'        => 'text',
				'required'    => false,
				'meta_key'    => true,
				'description' => '',
			),
			'apd_product_email_template' => array(
				'label'       => 'Email template',
				'type'        => 'richtext',
				'required'    => false,
				'meta_key'    => true,
				'cols'        => 40,
				'rows'        => 15,
				'description' => '<br> Avaliable shortcodes: '
									. '<br>{product_name}, {customer_name}, {coupon_code}, {company_name}, {redeem_link}, {url}',
				'default_value' => '<p style="margin-bottom: 12px;">Hi there!</p>
<p style="margin-bottom: 12px;">Thank you for your purchase of {product_name}.</p>
Click the following link to redeem your purchase: {coupon_code}
<p style="margin-bottom: 12px;">Please review installation instructions here: <a href="your link here">your link here</a></p>
<p style="margin-bottom: 12px;">In case you need help, you can reach out directly to {company_name} via this email address: <a href="mailto:your email">your email</a></p>
<p style="margin-bottom: 12px;">Best regards,
APD Support.</p>'
			),
		),
        'features' => array(
			'add_features' => array(
				'label'       => 'Key Features',
				'type'        => 'textarea',
				'required'    => false,
				'description' => 'Use bullet list format: each line starts with "* ".',
			),
			'soundcloud_url' => array(
				'label'       => 'SoundCloud Embed URL',
				'type'        => 'url',
				'required'    => false,
                'meta_key'    => true,
				'description' => '',
			),
			'podcast_url' => array(
				'label'       => 'Podcast URL',
				'type'        => 'url',
				'required'    => false,
                'meta_key'    => true,
				'description' => '',
			),
		),
        'other_product_information' => array(
            '_redeem_link' => array(
                'label' => 'Redeem Link URL',
                'type' => 'url',
				'meta_key'    => true,
                'required' => false,
            ),
            'lto_company_name' => array(
                'label' => 'Company/Developer Name',
                'type' => 'text',
				'meta_key'    => true,
                'required' => false,
            ),
            '_sku' => array(
                'label' => 'SKU',
                'type' => 'text',
                'required' => false,
				'meta_key'    => true,
            ),
			/* TODO - add emails 
            'download_instructions' => array( // meta_key: n/a in provided payload
                'label' => 'Download Instructions',
                'type' => 'textarea',
                'required' => true,
            ),*/
			'_minumum_price' => array(
                'label' => 'Minimum Price',
                'type' => 'number',
				'meta_key'    => true,
                'required' => false,
            ),
			'extra_instrument_category' => array(
				'label' => 'Add new instrument into list',
				'type' => 'text',
				'meta_key' => false,
				'required' => false,
			),
			'extra_format_category' => array(
				'label' => 'Add new format into list',
				'type' => 'text',
				'meta_key' => false,
				'required' => false,
			),
        ),
	);

	public static $taxonomy_fields = array(
		'product_categories' => array( //  taxonomy mapping (product_cat)
			'label' => 'Product Categories',
			'type' => 'multiselect',
			'taxonomy' => 'product_cat',
			'required' => false,
		),
		'instrument_categories' => array( // taxonomy mapping (instrument_type)
			'label' => 'Instrument Categories',
			'type' => 'multiselect',
			'taxonomy' => 'instrument_type',
			'required' => false,
		),
		'supported_formats' => array( // taxonomy mapping (format_type)
			'label' => 'Supported Formats',
			'type' => 'multiselect',
			'taxonomy' => 'format_type',
			'required' => false,
		),
	);


	/**
	 * Supported field types for display_field_set() on the product form.
	 *
	 * @var string[]
	 */
	protected static $supported_field_types = array(
		'text',
		'textarea',
		'url',
		'richtext',
		'checkbox',
		'hidden',
		'date',
		'dropdown',
		'image',
		'number',
	);

	/**
	 * Human-readable heading for each section key.
	 *
	 * @param string $section_key Section key from self::$fields.
	 * @return string
	 */
	private static function get_product_form_section_heading( $section_key ) {
		$map = array(
			'general'  => __( 'General Product Info ', DDB_TEXT_DOMAIN ),
			'overview' => __( 'Overview Section', DDB_TEXT_DOMAIN ),
			'media'    => __( 'Media Section', DDB_TEXT_DOMAIN ),
			'emails'   => __( 'Emails', DDB_TEXT_DOMAIN ),
			'features' => __( 'Product Features', DDB_TEXT_DOMAIN ),
			'other_product_information' => __( 'Other Product Information', DDB_TEXT_DOMAIN ),
		);
		return $map[ $section_key ] ?? $section_key;
	}

	/**
	 * POST name for a field (must match name used when reading $_POST on save).
	 *
	 * @param string               $field_key Field id within the section.
	 * @param array<string, mixed> $field_def Field definition.
	 * @return string
	 */
	public static function get_product_field_post_name( $field_key, array $field_def ) {
		if ( ! empty( $field_def['name'] ) && is_string( $field_def['name'] ) ) {
			return $field_def['name'];
		}
		return 'ddb_pf_' . str_replace( array( '-', ' ' ), '_', $field_key );
	}

	/**
	 * Convert Key Features textarea to Jet-style `add-features` post meta (item-N => array( 'add-features' => text )).
	 * Delegates to {@see Manage_Product_Features::format_product_features_for_save()} when that class is loaded.
	 *
	 * @param string $product_features Raw textarea (lines may start with "* ").
	 * @return array<string, array<string, string>>
	 */
	private static function format_add_features_for_save( $product_features ) {
		if ( class_exists( 'Manage_Product_Features' ) ) {
			return Manage_Product_Features::format_product_features_for_save( $product_features );
		}
		$features_array          = explode( "\n", (string) $product_features );
		$features_array_prepared = array();
		$count                   = 0;
		foreach ( $features_array as $feature ) {
			if ( strpos( $feature, '*' ) === 0 ) {
				$feature = trim( substr( $feature, 1 ) );
			}
			if ( ! empty( $feature ) ) {
				$features_array_prepared[ 'item-' . $count ] = array( 'add-features' => stripslashes( $feature ) );
				++$count;
			}
		}
		return $features_array_prepared;
	}

	/**
	 * Read $_POST and return sanitized product title, excerpt, slug, and meta (for fields with meta_key => true).
	 *
	 * @return array{title: string, short_description: string, slug: string, regular_price: string, meta: array<string, string|array>}
	 */
	public static function collect_product_form_data_from_request() {
		$title             = '';
		$short_description = '';
		$slug              = '';
		$regular_price     = '';
		$meta              = array();

		foreach ( self::$fields as $section_fields ) {
			if ( ! is_array( $section_fields ) ) {
				continue;
			}
			foreach ( $section_fields as $field_key => $field_def ) {
				if ( ! is_array( $field_def ) ) {
					continue;
				}
				$type = isset( $field_def['type'] ) ? (string) $field_def['type'] : 'text';
				$name = self::get_product_field_post_name( $field_key, $field_def );

				if ( 'product_name' === $field_key ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$title = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
					continue;
				}
				if ( 'short_description' === $field_key ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$raw_short_description = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
					$short_description     = is_string( $raw_short_description ) ? wp_kses_post( $raw_short_description ) : '';
					continue;
				}
				if ( 'permalink_slug' === $field_key ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$raw  = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
					$slug = sanitize_title( is_string( $raw ) ? $raw : '' );
					continue;
				}
				if ( 'regular_price' === $field_key ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
					$raw = is_string( $raw ) ? trim( $raw ) : '';
					if ( '' === $raw ) {
						$regular_price = '';
					} elseif ( function_exists( 'wc_format_decimal' ) ) {
						$regular_price = wc_format_decimal( $raw );
					} else {
						$regular_price = sanitize_text_field( $raw );
					}
					continue;
				}

				if ( 'add_features' === $field_key ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
					$raw = is_string( $raw ) ? $raw : '';
					// Meta key is `add-features` (Jet Elements / theme), not the field id.
					$meta['add-features'] = self::format_add_features_for_save( $raw );
					continue;
				}

				if ( empty( $field_def['meta_key'] ) ) {
					continue;
				}

				if ( 'image' === $type ) {
					continue;
				}

				if ( 'checkbox' === $type ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$meta[ $field_key ] = ! empty( $_POST[ $name ] ) ? '1' : '';
					continue;
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! isset( $_POST[ $name ] ) ) {
					$meta[ $field_key ] = '';
					continue;
				}

				$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				switch ( $type ) {
					case 'textarea':
						$meta[ $field_key ] = sanitize_textarea_field( $raw );
						break;
					case 'richtext':
						$meta[ $field_key ] = is_string( $raw ) ? wp_kses_post( $raw ) : '';
						break;
					case 'url':
						$meta[ $field_key ] = esc_url_raw( $raw );
						break;
					default:
						$meta[ $field_key ] = sanitize_text_field( $raw );
				}
			}
		}

		if ( '' === $slug && $title ) {
			$slug = sanitize_title( $title );
		}

		return array(
			'title'             => $title,
			'short_description' => $short_description,
			'slug'              => $slug,
			'regular_price'     => $regular_price,
			'meta'              => $meta,
		);
	}

	/**
	 * Write product meta keys from the developer product form.
	 *
	 * @param int                            $product_id Post ID.
	 * @param array<string, string|array> $meta      Meta key => value (e.g. `add-features` is an array structure).
	 */
	public static function persist_product_meta( $product_id, array $meta ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return;
		}
		foreach ( $meta as $key => $value ) {
			if ( ! is_string( $key ) || '' === $key ) {
				continue;
			}
			update_post_meta( $product_id, $key, $value );
		}
	}

	/**
	 * Handle file inputs for product form fields of type `image` with `meta_key`.
	 * Uses media_handle_upload(); stores attachment ID in post meta under the field key.
	 *
	 * @param int $product_id Product post ID (attachment parent).
	 * @return true|\WP_Error True if nothing uploaded or all OK; WP_Error on upload failure.
	 */
	public static function process_product_image_uploads( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return true;
		}

		if ( empty( $_FILES ) || ! is_array( $_FILES ) ) {
			return true;
		}

		foreach ( self::$fields as $section_fields ) {
			if ( ! is_array( $section_fields ) ) {
				continue;
			}
			foreach ( $section_fields as $field_key => $field_def ) {
				if ( ! is_array( $field_def ) ) {
					continue;
				}
				$type = isset( $field_def['type'] ) ? (string) $field_def['type'] : 'text';
				if ( 'image' !== $type || empty( $field_def['meta_key'] ) ) {
					continue;
				}

				$name = self::get_product_field_post_name( $field_key, $field_def );
				if ( ! isset( $_FILES[ $name ] ) || ! is_array( $_FILES[ $name ] ) ) {
					continue;
				}

				$file = $_FILES[ $name ];
				if ( ! isset( $file['error'] ) || UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
					continue;
				}
				if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
					return new WP_Error(
						'ddb_image_upload',
						sprintf(
							/* translators: %s: field input name */
							__( 'Image upload error for %s.', DDB_TEXT_DOMAIN ),
							$name
						)
					);
				}
				if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
					return new WP_Error(
						'ddb_image_upload',
						sprintf(
							/* translators: %s: field input name */
							__( 'Invalid image upload for %s.', DDB_TEXT_DOMAIN ),
							$name
						)
					);
				}

				if ( ! function_exists( 'media_handle_upload' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in caller.
				$attachment_id = media_handle_upload( $name, $product_id );
				if ( is_wp_error( $attachment_id ) ) {
					return $attachment_id;
				}

				update_post_meta( $product_id, $field_key, (string) (int) $attachment_id );
			}
		}

		return true;
	}

	/**
	 * Raw value for a field (post meta, post fields, or $_POST when repopulating).
	 *
	 * @param string               $field_key Field id.
	 * @param array<string, mixed> $field_def Field definition.
	 * @param string               $type      Field type.
	 * @param WP_Post|null         $curr_product Product when editing.
	 * @param bool                 $repopulate Whether to prefer $_POST.
	 * @return mixed
	 */
	protected static function get_product_field_raw_value( $field_key, array $field_def, $type, $curr_product, $repopulate ) {
		$name = self::get_product_field_post_name( $field_key, $field_def );

		if ( 'add_features' === $field_key ) {
			if ( $repopulate ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST[ $name ] ) ) {
					$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					return is_string( $raw ) ? sanitize_textarea_field( $raw ) : '';
				}
				return '';
			}
			if ( $curr_product instanceof WP_Post ) {
				$features = get_post_meta( $curr_product->ID, 'add-features', true );
				$list     = '';
				if ( is_array( $features ) ) {
					foreach ( $features as $feature ) {
						if ( isset( $feature['add-features'] ) && is_string( $feature['add-features'] ) ) {
							$list .= '* ' . $feature['add-features'] . "\n";
						}
					}
				}
				return $list;
			}
			return '';
		}

		if ( $repopulate ) {
			if ( 'checkbox' === $type ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				return isset( $_POST[ $name ] ) && (bool) wp_unslash( $_POST[ $name ] );
			}
			if ( 'image' === $type && $curr_product instanceof WP_Post ) {
				$cur = get_post_meta( $curr_product->ID, $field_key, true );
				return is_string( $cur ) ? $cur : '';
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ $name ] ) ) {
				$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				switch ( $type ) {
					case 'textarea':
						return sanitize_textarea_field( $raw );
					case 'richtext':
						return is_string( $raw ) ? wp_kses_post( $raw ) : '';
					case 'url':
						return esc_url_raw( $raw );
					case 'number':
						$raw = is_string( $raw ) ? trim( $raw ) : '';
						if ( '' === $raw ) {
							return '';
						}
						return function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $raw ) : sanitize_text_field( $raw );
					case 'text':
					case 'hidden':
					case 'date':
					case 'dropdown':
						return sanitize_text_field( $raw );
					default:
						return sanitize_text_field( $raw );
				}
			}
		}

		if ( $curr_product instanceof WP_Post ) {
			if ( 'product_name' === $field_key ) {
				return $curr_product->post_title;
			}
			if ( 'short_description' === $field_key ) {
				return $curr_product->post_excerpt;
			}
			if ( 'permalink_slug' === $field_key ) {
				return $curr_product->post_name;
			}
			if ( 'regular_price' === $field_key && function_exists( 'wc_get_product' ) ) {
				$wc_product = wc_get_product( $curr_product->ID );
				if ( $wc_product ) {
					return (string) $wc_product->get_regular_price( 'edit' );
				}
				return '';
			}
			if ( ! empty( $field_def['meta_key'] ) ) {
				$meta = get_post_meta( $curr_product->ID, $field_key, true );
				return is_string( $meta ) ? $meta : ( is_scalar( $meta ) ? (string) $meta : '' );
			}
			return $field_def['default'] ?? '';
		}

		if ( 'richtext' === $type && isset( $field_def['default_value'] ) && is_string( $field_def['default_value'] ) ) {
			return $field_def['default_value'];
		}

		return $field_def['default'] ?? '';
	}

	/**
	 * Escape/format value for Ddb_Core input helpers.
	 *
	 * @param string $type Field type.
	 * @param mixed  $raw  Raw value.
	 * @return mixed bool for checkbox; string otherwise
	 */
	protected static function format_field_value_for_display( $type, $raw ) {
		if ( false === $raw || null === $raw ) {
			$raw = '';
		}
		switch ( $type ) {
			case 'checkbox':
				return (bool) $raw;
			case 'richtext':
				return is_string( $raw ) ? $raw : '';
			case 'textarea':
				return esc_textarea( (string) $raw );
			case 'number':
			case 'text':
			case 'url':
			case 'hidden':
			case 'date':
			case 'dropdown':
			case 'image':
			default:
				return esc_attr( (string) $raw );
		}
	}

	/**
	 * Build field set array for one section for display_field_set().
	 *
	 * @param array<string, array<string, mixed>> $section_fields Section from self::$fields.
	 * @param WP_Post|null                        $curr_product   Product when editing.
	 * @param bool                                $repopulate     Whether to prefer $_POST.
	 * @return array<int, array<string, mixed>>
	 */
	protected static function build_section_field_set( array $section_fields, $edit_product, $repopulate ) {
		$out = array();
		foreach ( $section_fields as $field_key => $field_def ) {
			if ( ! is_array( $field_def ) ) {
				continue;
			}
			$type = isset( $field_def['type'] ) ? (string) $field_def['type'] : 'text';
			if ( ! in_array( $type, self::$supported_field_types, true ) ) {
				continue;
			}

			$raw = self::get_product_field_raw_value( $field_key, $field_def, $type, $edit_product, $repopulate );

			$row = array(
				'name'        => self::get_product_field_post_name( $field_key, $field_def ),
				'type'        => $type,
				'label'       => __( $field_def['label'], DDB_TEXT_DOMAIN ),
				'description' => isset( $field_def['description'] ) && $field_def['description'] !== ''
					? __( $field_def['description'], DDB_TEXT_DOMAIN )
					: '',
			);
			$row['value'] = self::format_field_value_for_display( $type, $raw );

			if ( isset( $field_def['required'] ) ) {
				$row['required'] = $field_def['required'];
			}
			foreach ( array( 'class', 'maxlength', 'cols', 'rows', 'min', 'options', 'autocomplete' ) as $opt ) {
				if ( isset( $field_def[ $opt ] ) ) {
					$row[ $opt ] = $field_def[ $opt ];
				}
			}

			$out[] = $row;
		}
		return $out;
	}

	/**
	 * Look up one product field definition by key across all form sections.
	 *
	 * @param string $field_key Product field key from self::$fields.
	 * @return array<string, mixed>|null
	 */
	public static function get_product_field_definition( $field_key ) {
		$field_key = (string) $field_key;
		if ( '' === $field_key ) {
			return null;
		}

		foreach ( self::$fields as $section_fields ) {
			if ( ! is_array( $section_fields ) || ! isset( $section_fields[ $field_key ] ) || ! is_array( $section_fields[ $field_key ] ) ) {
				continue;
			}

			return $section_fields[ $field_key ];
		}

		return null;
	}

	/**
	 * Build a field set for arbitrary product field keys while reusing the standard value/format logic.
	 *
	 * @param array<int, string|array<string, mixed>> $field_refs     Field keys or arrays with a required `key`.
	 * @param \WP_Post|null                           $edit_product   Product when editing.
	 * @param bool                                    $repopulate     Whether to prefer $_POST.
	 * @return array<int, array<string, mixed>>
	 */
	protected static function build_field_set_from_refs( array $field_refs, $edit_product, $repopulate ) {
		$out = array();

		foreach ( $field_refs as $field_ref ) {
			$field_key  = '';
			$overrides  = array();
			$field_def  = null;

			if ( is_string( $field_ref ) ) {
				$field_key = $field_ref;
			} elseif ( is_array( $field_ref ) && ! empty( $field_ref['key'] ) && is_string( $field_ref['key'] ) ) {
				$field_key = $field_ref['key'];
				$overrides = $field_ref;
				unset( $overrides['key'] );
			}

			if ( '' === $field_key ) {
				continue;
			}

			$field_def = self::get_product_field_definition( $field_key );
			if ( ! is_array( $field_def ) ) {
				continue;
			}

			if ( ! empty( $overrides ) ) {
				$field_def = array_merge( $field_def, $overrides );
			}

			$type = isset( $field_def['type'] ) ? (string) $field_def['type'] : 'text';
			if ( ! in_array( $type, self::$supported_field_types, true ) ) {
				continue;
			}

			$raw = self::get_product_field_raw_value( $field_key, $field_def, $type, $edit_product, $repopulate );

			$row = array(
				'name'        => self::get_product_field_post_name( $field_key, $field_def ),
				'type'        => $type,
				'label'       => isset( $field_def['label'] ) ? __( (string) $field_def['label'], DDB_TEXT_DOMAIN ) : '',
				'description' => isset( $field_def['description'] ) && '' !== $field_def['description']
					? __( (string) $field_def['description'], DDB_TEXT_DOMAIN )
					: '',
				'value'       => self::format_field_value_for_display( $type, $raw ),
			);

			if ( isset( $field_def['required'] ) ) {
				$row['required'] = $field_def['required'];
			}

			foreach ( array( 'class', 'maxlength', 'cols', 'rows', 'min', 'step', 'options', 'autocomplete', 'style' ) as $opt ) {
				if ( isset( $field_def[ $opt ] ) ) {
					$row[ $opt ] = $field_def[ $opt ];
				}
			}

			$out[] = $row;
		}

		return $out;
	}

	/**
	 * Load terms for a product taxonomy (for developer product form checkboxes).
	 *
	 * @param string $taxonomy Taxonomy slug (e.g. product_cat, instrument_type).
	 * @return \WP_Term[] Empty array if invalid taxonomy or on error.
	 */
	public static function get_product_taxonomy_options( $taxonomy ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * POST input base name for a taxonomy field row (array of term IDs).
	 *
	 * @param string $taxonomy_key Key from self::$taxonomy_fields.
	 * @return string e.g. ddb_tax_product_categories
	 */
	public static function get_taxonomy_field_post_name( $taxonomy_key ) {
		return 'ddb_tax_' . str_replace( array( '-', ' ' ), '_', (string) $taxonomy_key );
	}

	/**
	 * Apply taxonomy checkbox selections from $_POST to a product.
	 *
	 * @param int $product_id Product post ID.
	 */
	public static function apply_product_taxonomy_fields_from_request( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return;
		}

		foreach ( self::$taxonomy_fields as $taxonomy_key => $field_def ) {
			if ( ! is_array( $field_def ) ) {
				continue;
			}
			$tax = isset( $field_def['taxonomy'] ) ? sanitize_key( (string) $field_def['taxonomy'] ) : '';
			if ( '' === $tax || ! taxonomy_exists( $tax ) ) {
				continue;
			}

			$post_key = self::get_taxonomy_field_post_name( $taxonomy_key );
			$ids      = array();

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in Ddb_Frontend::handle_developer_edit_product_submission.
			if ( isset( $_POST[ $post_key ] ) && is_array( $_POST[ $post_key ] ) ) {
				foreach ( $_POST[ $post_key ] as $tid ) {
					$ids[] = absint( $tid );
				}
				$ids = array_values( array_unique( array_filter( $ids ) ) );
			}

			$ids = self::append_extra_taxonomy_term_from_request( $ids, $taxonomy_key, $tax );
			wp_set_object_terms( $product_id, $ids, $tax, false );
		}
	}

	/**
	 * Append a free-text taxonomy term from the request when supported by the field.
	 *
	 * @param int[]  $ids          Selected term IDs.
	 * @param string $taxonomy_key Taxonomy field key.
	 * @param string $taxonomy     Taxonomy slug.
	 * @return int[]
	 */
	protected static function append_extra_taxonomy_term_from_request( array $ids, $taxonomy_key, $taxonomy ) {
		$extra_field_map = array(
			'instrument_categories' => 'extra_instrument_category',
			'supported_formats'     => 'extra_format_category',
		);

		if ( empty( $extra_field_map[ $taxonomy_key ] ) ) {
			return $ids;
		}

		$extra_field_key = $extra_field_map[ $taxonomy_key ];
		$term_name       = '';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in the calling submission handlers.
		if ( isset( $_POST[ 'ddb_pf_' . $extra_field_key ] ) ) {
			$term_name = sanitize_text_field( wp_unslash( $_POST[ 'ddb_pf_' . $extra_field_key ] ) );
		}

		if ( '' === $term_name ) {
			return $ids;
		}

		$term_id = self::get_or_create_taxonomy_term_id( $term_name, $taxonomy );
		if ( $term_id <= 0 ) {
			return $ids;
		}

		$ids[] = $term_id;

		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	/**
	 * Resolve a taxonomy term ID by name, creating the term when needed.
	 *
	 * @param string $term_name Term name from request.
	 * @param string $taxonomy  Taxonomy slug.
	 * @return int
	 */
	protected static function get_or_create_taxonomy_term_id( $term_name, $taxonomy ) {
		$term_name = trim( (string) $term_name );
		$taxonomy  = sanitize_key( (string) $taxonomy );

		if ( '' === $term_name || '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}

		$existing_term = get_term_by( 'name', $term_name, $taxonomy );
		if ( $existing_term instanceof WP_Term ) {
			return (int) $existing_term->term_id;
		}

		$term_exists = term_exists( $term_name, $taxonomy );
		if ( is_array( $term_exists ) && ! empty( $term_exists['term_id'] ) ) {
			return absint( $term_exists['term_id'] );
		}
		if ( is_numeric( $term_exists ) ) {
			return absint( $term_exists );
		}

		$inserted_term = wp_insert_term( $term_name, $taxonomy );
		if ( is_wp_error( $inserted_term ) ) {
			if ( 'term_exists' === $inserted_term->get_error_code() ) {
				return absint( $inserted_term->get_error_data() );
			}

			return 0;
		}

		return ! empty( $inserted_term['term_id'] ) ? absint( $inserted_term['term_id'] ) : 0;
	}

	/**
	 * Selected term IDs for one taxonomy field (from repopulated POST or current product terms).
	 *
	 * @param string        $taxonomy_key Taxonomy field key from self::$taxonomy_fields.
	 * @param string        $tax          Taxonomy slug.
	 * @param \WP_Post|null $curr_product Product being edited.
	 * @param bool          $repopulate   Prefer $_POST values after failed validation.
	 * @return int[]
	 */
	protected static function get_selected_taxonomy_term_ids( $taxonomy_key, $tax, $curr_product, $repopulate ) {
		$selected  = array();
		$post_base = self::get_taxonomy_field_post_name( $taxonomy_key );

		if ( $repopulate ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- same context as product form fields.
			if ( isset( $_POST[ $post_base ] ) && is_array( $_POST[ $post_base ] ) ) {
				foreach ( $_POST[ $post_base ] as $tid ) {
					$selected[] = absint( $tid );
				}
				return array_values( array_unique( array_filter( $selected ) ) );
			}
			return array();
		}

		if ( $curr_product instanceof WP_Post ) {
			$term_ids = wp_get_post_terms( $curr_product->ID, $tax, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $term_ids ) && is_array( $term_ids ) ) {
				return array_map( 'absint', $term_ids );
			}
		}

		return array();
	}

	/**
	 * Whether the product form has taxonomy columns to show (valid taxonomies exist).
	 *
	 * @return bool
	 */
	private static function has_product_form_taxonomy_columns() {
		foreach ( self::$taxonomy_fields as $taxonomy_field ) {
			if ( ! is_array( $taxonomy_field ) ) {
				continue;
			}
			$tax = isset( $taxonomy_field['taxonomy'] ) ? sanitize_key( (string) $taxonomy_field['taxonomy'] ) : '';
			if ( '' !== $tax && taxonomy_exists( $tax ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Echo taxonomy fields in one table row, with one column per taxonomy.
	 *
	 * @param \WP_Post|null $curr_product Product being edited.
	 * @param bool          $repopulate   Prefer $_POST after failed validation.
	 */
	public static function display_taxonomy_fields_as_columns( $curr_product, $repopulate ) {
		$columns = array();

		foreach ( self::$taxonomy_fields as $taxonomy_key => $taxonomy_field ) {
			if ( ! is_array( $taxonomy_field ) ) {
				continue;
			}
			$tax = isset( $taxonomy_field['taxonomy'] ) ? sanitize_key( (string) $taxonomy_field['taxonomy'] ) : '';
			if ( '' === $tax || ! taxonomy_exists( $tax ) ) {
				continue;
			}

			$options         = self::get_product_taxonomy_options( $tax );
			$selected        = self::get_selected_taxonomy_term_ids( $taxonomy_key, $tax, $curr_product, $repopulate );
			$selected_lookup = array_fill_keys( $selected, true );

			$field_for_label = array(
				'label'    => isset( $taxonomy_field['label'] ) ? __( (string) $taxonomy_field['label'], DDB_TEXT_DOMAIN ) : '',
				'required' => ! empty( $taxonomy_field['required'] ),
			);

			$columns[] = array(
				'taxonomy_key'     => (string) $taxonomy_key,
				'taxonomy_field'   => $taxonomy_field,
				'options'          => $options,
				'selected_lookup'  => $selected_lookup,
				'post_name'        => self::get_taxonomy_field_post_name( $taxonomy_key ) . '[]',
				'label_html'       => self::field_label_with_required_suffix( $field_for_label ),
				'label_plain'      => isset( $taxonomy_field['label'] ) ? __( (string) $taxonomy_field['label'], DDB_TEXT_DOMAIN ) : '',
			);
		}

		if ( empty( $columns ) ) {
			return;
		}
		?>
		<table class="ddb-report-form-table ddb-product-form-taxonomy-columns-table">
			<thead>
				<tr>
					<?php foreach ( $columns as $column ) : ?>
						<th class="ddb-product-form-taxonomy-columns-table__head" scope="col">
							<?php echo $column['label_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<tr class="ddb-product-form-taxonomy-columns-table__row">
					<?php foreach ( $columns as $column ) : ?>
						<?php $list_id = 'ddb-tax-' . preg_replace( '/[^a-z0-9_-]/i', '', (string) $column['taxonomy_key'] ); ?>
						<td class="ddb-product-form-taxonomy-columns-table__cell">
							<ul class="ddb-taxonomy-checkbox-list" role="group" aria-label="<?php echo esc_attr( (string) $column['label_plain'] ); ?>">
								<?php foreach ( $column['options'] as $term ) : ?>
									<?php
									if ( ! $term instanceof WP_Term ) {
										continue;
									}
									$tid   = (int) $term->term_id;
									$cb_id = $list_id . '-term-' . $tid;
									?>
									<li class="ddb-taxonomy-checkbox-list__item">
										<label class="ddb-taxonomy-checkbox-list__label" for="<?php echo esc_attr( $cb_id ); ?>">
											<input
												type="checkbox"
												name="<?php echo esc_attr( (string) $column['post_name'] ); ?>"
												id="<?php echo esc_attr( $cb_id ); ?>"
												value="<?php echo esc_attr( (string) $tid ); ?>"
												<?php checked( isset( $column['selected_lookup'][ $tid ] ) ); ?>
											/>
											<span class="ddb-taxonomy-checkbox-list__text"><?php echo esc_html( $term->name ); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</td>
					<?php endforeach; ?>
				</tr>
			</tbody>
		</table>
		<?php
	}


	public static function render_developer_product_licenses( $product_id = 0 ) {
		return self::render_developer_product_form( $product_id, 'license-keys' );
	}

	/**
	 * Return the requested product-form tab key when valid, otherwise the default.
	 *
	 * @param array<int, array<string, mixed>> $tabs Tab definitions.
	 * @param string                           $forced_active_tab_key Optional explicit tab key override.
	 * @return string
	 */
	private static function get_requested_product_form_tab_key( array $tabs, $forced_active_tab_key = '' ) {
		$available_tab_keys = array();
		foreach ( $tabs as $tab ) {
			if ( ! empty( $tab['key'] ) && is_string( $tab['key'] ) ) {
				$available_tab_keys[] = $tab['key'];
			}
		}

		if ( empty( $available_tab_keys ) ) {
			return '';
		}

		$requested_tab_key = '';

		if ( is_string( $forced_active_tab_key ) && '' !== $forced_active_tab_key ) {
			$requested_tab_key = sanitize_key( $forced_active_tab_key );
		}

		if ( '' === $requested_tab_key ) {
			$posted_tab_key = filter_input( INPUT_POST, 'ddb_active_product_tab', FILTER_DEFAULT );
			if ( is_string( $posted_tab_key ) && '' !== $posted_tab_key ) {
				$requested_tab_key = sanitize_key( wp_unslash( $posted_tab_key ) );
			}
		}

		if ( '' === $requested_tab_key ) {
			$query_tab_key = filter_input( INPUT_GET, 'ddb_tab', FILTER_DEFAULT );
			if ( is_string( $query_tab_key ) && '' !== $query_tab_key ) {
				$requested_tab_key = sanitize_key( wp_unslash( $query_tab_key ) );
			}
		}

		if ( in_array( $requested_tab_key, $available_tab_keys, true ) ) {
			return $requested_tab_key;
		}

		return (string) reset( $available_tab_keys );
	}

	/**
	 * Render the edit-only license keys tab content inside the product form.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $product_title Product title.
	 * @param string $product_status Product post status.
	 * @return void
	 */
	protected static function render_product_license_tab_content( $product_id, $product_title, $product_status ) {
		if ( 'draft' !== $product_status ) {
			echo '<p>' . esc_html__( 'You can only edit license keys for draft products.', DDB_TEXT_DOMAIN ) . '</p>';
			return;
		}

		wp_nonce_field( Ddb_Frontend::NONCE_ACTION_EDIT_LICENSE, 'ddb_edit_license_nonce' );

		$license_rows      = Ddb_License_Manager::get_licenses( $product_id, 'draft' );
		$license_keys_text = '';
		$codes             = array();

		foreach ( $license_rows as $license_row ) {
			if ( is_object( $license_row ) && isset( $license_row->licence_code ) && '' !== (string) $license_row->licence_code ) {
				$codes[] = (string) $license_row->licence_code;
			}
		}

		$license_keys_text = implode( "\n", $codes );

		echo '<p>' . esc_html__( 'Maximum length for a license key is ', DDB_TEXT_DOMAIN ) . Ddb_License_Manager::MAX_LICENSE_KEY_LENGTH . '</p>';
		?>
		<p class="ddb-licenses-form__keys">
			<label for="ddb_product_license_keys"><?php esc_html_e( 'Draft license keys (one per line)', DDB_TEXT_DOMAIN ); ?></label>
			<textarea id="ddb_product_license_keys" name="ddb_product_license_keys" rows="15" cols="60" class="large-text code"><?php echo esc_textarea( $license_keys_text ); ?></textarea>
		</p>
		<p><?php echo esc_html__( 'Draft license keys will be approved by manager and added to the product.', DDB_TEXT_DOMAIN ); ?></p>
		<?php

		$available_licenses = Ddb_License_Manager::get_licenses( $product_id, 'available' );

		if ( ! empty( $available_licenses ) ) :
			?>
			<h5><?php echo esc_html( __( 'Approved license keys', DDB_TEXT_DOMAIN ) ); ?>:</h5>
			<ol>
				<?php foreach ( $available_licenses as $license ) : ?>
					<li><?php echo esc_html( $license->licence_code ); ?></li>
				<?php endforeach; ?>
			</ol>
			<p><?php echo esc_html( __( 'Approved license keys are no longer editable. You can add more license keys to the product by entering them in the text area above.', DDB_TEXT_DOMAIN ) ); ?></p>
			<?php
		endif;
	}

	/**
	 * Create-product or update-product form, depending on the provided $product_id
     * 
	 *
	 * @param int    $product_id Product ID for edit mode.
	 * @param string $forced_active_tab_key Optional explicit tab key to activate on initial render.
	 * @return string HTML
	 */
	public static function render_developer_product_form( $product_id = 0, $forced_active_tab_key = '' ) {
		$out = Ddb_Frontend::notice_error_html( esc_html__( 'Not authorized', DDB_TEXT_DOMAIN ) );

		$user = wp_get_current_user();

		$developer_term = false;

		if ( $user && is_array( $user->roles ) && in_array( self::DEV_ROLE_NAME, $user->roles, true ) ) {
			$developer_term = self::find_developer_term_by_user_id( $user->ID );
		}

		if ( ! is_object( $developer_term ) || ! is_a( $developer_term, 'WP_Term' ) ) {
			return $out;
		}

		if ( ! post_type_exists( 'product' ) || ! taxonomy_exists( 'developer' ) ) {
			return '<div id="developer-dashboard" class="ddb-developer-products ddb-developer-create-product"><p>' . esc_html__( 'The product catalog is not available on this site.', DDB_TEXT_DOMAIN ) . '</p></div>';
		}

		$notice_html       = Ddb_Frontend::get_product_form_notice_html();
		$repopulate_values = Ddb_Frontend::should_repopulate_product_form_values();
		$tab_sections      = array();

		$edit_product_id = absint( $product_id );
		
		$is_edit_form = ( $edit_product_id > 0 );
		$curr_product = null;
		$preview_url  = '';

		if ( $is_edit_form ) {
			$candidate_post = get_post( $edit_product_id );
			if ( $candidate_post && 'product' === $candidate_post->post_type && has_term( (int) $developer_term->term_id, 'developer', $edit_product_id ) ) {
				$curr_product = $candidate_post;
				$preview_link = get_preview_post_link( $candidate_post );
				if ( is_string( $preview_link ) && '' !== $preview_link ) {
					$preview_url = $preview_link;
				} else {
					$permalink = get_permalink( $edit_product_id );
					if ( is_string( $permalink ) && '' !== $permalink ) {
						$preview_url = $permalink;
					}
				}
			} else {
				$is_edit_form = false;
			}
		}

		foreach ( self::$fields as $section_key => $section_fields ) {
			$section_field_set = self::build_section_field_set( $section_fields, $curr_product, $repopulate_values );
			if ( ! $section_field_set ) {
				continue;
			}
			$tab_sections[] = array(
				'key'       => (string) $section_key,
				'heading'   => self::get_product_form_section_heading( $section_key ),
				'field_set' => $section_field_set,
			);
		}

		$has_taxonomy_tab      = self::has_product_form_taxonomy_columns();
		$taxonomy_tab_key      = 'categories-classification';
		$taxonomy_tab_key_safe = preg_replace( '/[^a-z0-9_-]/i', '', $taxonomy_tab_key );
		$taxonomy_tab_id       = 'ddb-product-tab-' . $taxonomy_tab_key_safe;
		$taxonomy_panel_id     = 'ddb-product-panel-' . $taxonomy_tab_key_safe;
		$taxonomy_tab_heading  = __( 'Categories & classification', DDB_TEXT_DOMAIN );
		$license_tab_key       = 'license-keys';
		$license_tab_key_safe  = preg_replace( '/[^a-z0-9_-]/i', '', $license_tab_key );
		$license_tab_id        = 'ddb-product-tab-' . $license_tab_key_safe;
		$license_panel_id      = 'ddb-product-panel-' . $license_tab_key_safe;
		$license_tab_heading   = __( 'License keys', DDB_TEXT_DOMAIN );
		$show_license_tab      = $is_edit_form;
		$tabs                  = array();

		foreach ( $tab_sections as $tab_section ) {
			$tab_key = preg_replace( '/[^a-z0-9_-]/i', '', (string) $tab_section['key'] );
			$tabs[]  = array(
				'key'       => $tab_key,
				'id'        => 'ddb-product-tab-' . $tab_key,
				'panel_id'  => 'ddb-product-panel-' . $tab_key,
				'heading'   => (string) $tab_section['heading'],
				'type'      => 'fields',
				'field_set' => $tab_section['field_set'],
			);
		}

		if ( $has_taxonomy_tab ) {
			$tabs[] = array(
				'key'      => $taxonomy_tab_key,
				'id'       => $taxonomy_tab_id,
				'panel_id' => $taxonomy_panel_id,
				'heading'  => $taxonomy_tab_heading,
				'type'     => 'taxonomy',
			);
		}

		if ( $show_license_tab ) {
			$tabs[] = array(
				'key'      => $license_tab_key,
				'id'       => $license_tab_id,
				'panel_id' => $license_panel_id,
				'heading'  => $license_tab_heading,
				'type'     => 'licenses',
			);
		}

		$show_product_tabs = ! empty( $tabs );
		$active_tab_key    = self::get_requested_product_form_tab_key( $tabs, $forced_active_tab_key );

		ob_start();
		?>
		<div id="developer-dashboard" class="ddb-developer-products ddb-developer-create-product">
			<h2><?php echo esc_html( $is_edit_form ? __( 'Edit product', DDB_TEXT_DOMAIN ) : __( 'Create new product', DDB_TEXT_DOMAIN ) ); ?></h2>
			<?php
			// Notices are built in the handler with escaped output.
			echo $notice_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<form method="post" class="ddb-create-product-form" action="" enctype="multipart/form-data">
				<style>
					.ddb-product-form-tabs {
						display: flex;
						flex-wrap: wrap;
						gap: 8px;
						margin: 0 0 16px;
						padding: 0;
					}
					.ddb-product-form-tabs__btn {
						border: 1px solid #ccd0d4;
						background: #f6f7f7;
						color: #1d2327;
						padding: 8px 12px;
						cursor: pointer;
						border-radius: 4px;
						font-weight: 600;
					}
					.ddb-product-form-tabs__btn.is-active {
						background: #2271b1;
						border-color: #2271b1;
						color: #fff;
					}
					.ddb-product-form-tab-panel[hidden] {
						display: none;
					}
				</style>
				<?php wp_nonce_field( $is_edit_form ? Ddb_Frontend::NONCE_ACTION_EDIT_PRODUCT : Ddb_Frontend::NONCE_ACTION_CREATE_PRODUCT, 'ddb_create_product_nonce' ); ?>
				<?php if ( $is_edit_form ) : ?>
					<input type="hidden" name="product_id" value="<?php echo esc_attr( $edit_product_id ); ?>" />
				<?php endif; ?>
				<input type="hidden" name="ddb_active_product_tab" value="<?php echo esc_attr( $active_tab_key ); ?>" />
				<?php if ( $show_product_tabs ) : ?>
					<div class="ddb-product-form-tabs" role="tablist" aria-label="<?php echo esc_attr( __( 'Product form sections', DDB_TEXT_DOMAIN ) ); ?>">
						<?php foreach ( $tabs as $tab ) : ?>
							<?php $is_active_tab = ( $tab['key'] === $active_tab_key ); ?>
							<button
								type="button"
								class="ddb-product-form-tabs__btn<?php echo $is_active_tab ? ' is-active' : ''; ?>"
								id="<?php echo esc_attr( (string) $tab['id'] ); ?>"
								role="tab"
								aria-controls="<?php echo esc_attr( (string) $tab['panel_id'] ); ?>"
								aria-selected="<?php echo $is_active_tab ? 'true' : 'false'; ?>"
								data-ddb-tab-key="<?php echo esc_attr( (string) $tab['key'] ); ?>"
								data-ddb-tab-target="<?php echo esc_attr( (string) $tab['panel_id'] ); ?>"
							><?php echo esc_html( (string) $tab['heading'] ); ?></button>
						<?php endforeach; ?>
					</div>

					<?php foreach ( $tabs as $tab ) : ?>
						<?php $is_active_tab = ( $tab['key'] === $active_tab_key ); ?>
						<div
							class="ddb-product-form-tab-panel"
							id="<?php echo esc_attr( (string) $tab['panel_id'] ); ?>"
							role="tabpanel"
							aria-labelledby="<?php echo esc_attr( (string) $tab['id'] ); ?>"
							<?php echo $is_active_tab ? '' : ' hidden'; ?>
						>
							<h3 class="ddb-product-form__section-title"><?php echo esc_html( (string) $tab['heading'] ); ?></h3>
							<?php if ( 'fields' === $tab['type'] ) : ?>
								<table class="ddb-report-form-table">
									<tbody>
										<?php self::display_field_set( $tab['field_set'] ); ?>
									</tbody>
								</table>
							<?php elseif ( 'taxonomy' === $tab['type'] ) : ?>
								<?php self::display_taxonomy_fields_as_columns( $curr_product, $repopulate_values ); ?>
							<?php elseif ( 'licenses' === $tab['type'] ) : ?>
								<?php self::render_product_license_tab_content( $edit_product_id, $curr_product ? $curr_product->post_title : '', $curr_product ? $curr_product->post_status : '' ); ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
				<p class="submit">
					<p>
					<input type="submit" name="<?php echo esc_attr( self::BUTTON_SUMBIT ); ?>" class="button button-primary" value="<?php echo esc_attr( $is_edit_form ? Ddb_Frontend::ACTION_EDIT_DEVELOPER_PRODUCT : Ddb_Frontend::ACTION_CREATE_DEVELOPER_PRODUCT ); ?>" />
					</p>
					<?php if ( $is_edit_form && '' !== $preview_url ) : ?>
						<p>
						<a href="<?php echo esc_url( $preview_url ); ?>" class="button button-secondary ddb-product-form-preview-link" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview product', DDB_TEXT_DOMAIN ); ?></a>
						</p>
					<?php endif; ?>
					<?php if ( $is_edit_form && $curr_product && 'publish' !== $curr_product->post_status ) : ?>
						<p>
							<input
								type="submit"
								name="<?php echo esc_attr( self::BUTTON_SUMBIT ); ?>"
								class="button button-secondary ddb-product-form-delete"
								formnovalidate="formnovalidate"
								value="<?php echo esc_attr( Ddb_Frontend::ACTION_DELETE_DEVELOPER_PRODUCT ); ?>"
								onclick="return window.confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this product? This cannot be undone.', DDB_TEXT_DOMAIN ) ); ?>');"
							/>
						</p>
					<?php endif; ?>
				</p>
			</form>
			<script>
				(function() {
					var root = document.querySelector('#developer-dashboard .ddb-create-product-form');
					if (!root) {
						return;
					}
					var activeTabInput = root.querySelector('input[name="ddb_active_product_tab"]');
					var tabButtons = root.querySelectorAll('.ddb-product-form-tabs__btn');
					var tabPanels = root.querySelectorAll('.ddb-product-form-tab-panel');
					if (!tabButtons.length || !tabPanels.length) {
						return;
					}
					var activateTab = function(btn) {
						var targetId = btn.getAttribute('data-ddb-tab-target');
						var tabKey = btn.getAttribute('data-ddb-tab-key');
						for (var i = 0; i < tabButtons.length; i++) {
							var currentBtn = tabButtons[i];
							var isActive = currentBtn === btn;
							currentBtn.classList.toggle('is-active', isActive);
							currentBtn.setAttribute('aria-selected', isActive ? 'true' : 'false');
						}
						for (var j = 0; j < tabPanels.length; j++) {
							var panel = tabPanels[j];
							panel.hidden = panel.id !== targetId;
						}
						if (activeTabInput && tabKey) {
							activeTabInput.value = tabKey;
						}
					};
					for (var k = 0; k < tabButtons.length; k++) {
						tabButtons[k].addEventListener('click', function() {
							activateTab(this);
						});
					}
				})();
			</script>
			<p class="ddb-create-product__back"><a href="<?php echo esc_url( remove_query_arg( 'product_id', add_query_arg( 'section', 'products' ) ) ); ?>"><?php esc_html_e( '← Back to your products', DDB_TEXT_DOMAIN ); ?></a></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
