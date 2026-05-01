<?php


/* Original table created by License-Manager plugin:
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_product_licences(
    licence_id integer auto_increment primary key,
    product_id integer not null,
    licence_code varchar(100),
    licence_status varchar(100),
    activation_date datetime,
    creation_date datetime
)

*/
class Ddb_License_Manager {

	/** @var int Max allowed license key length. */
	public const MAX_LICENSE_KEY_LENGTH = 70;

	/**
	 * WooCommerce License Manager table (see License-Manager plugin).
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wc_product_licences';
	}

	/**
	 * All licence rows with given status  for a product
	 *
	 * @param int $product_id WooCommerce product ID.
     * @param string $status License status.
	 * @return array<int, object>
	 */
	public static function get_licenses( $product_id, $status = 'available' ) {
		global $wpdb;

		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return array();
		}

		$table = self::table_name();
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE product_id = %d AND licence_status = %s ORDER BY creation_date ASC",
			$product_id,
			$status
		);

		$rows = $wpdb->get_results( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Licence rows for a product matching any of the given statuses.
	 *
	 * @param int      $product_id WooCommerce product ID.
	 * @param string[] $statuses   licence_status values (e.g. available, draft).
	 * @return array<int, object>
	 */
	public static function get_licenses_for_statuses( $product_id, array $statuses ) {
		global $wpdb;

		$product_id = (int) $product_id;
		$statuses   = array_values(
			array_filter(
				array_map(
					static function ( $s ) {
						return is_string( $s ) ? sanitize_text_field( $s ) : '';
					},
					$statuses
				)
			)
		);

		if ( $product_id <= 0 || empty( $statuses ) ) {
			return array();
		}

		$table        = self::table_name();
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
		$sql          = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE product_id = %d AND licence_status IN ({$placeholders}) ORDER BY creation_date ASC",
			array_merge( array( $product_id ), $statuses )
		);

		$rows = $wpdb->get_results( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count licence rows for a product and status (default: available pool).
	 *
	 * @param int    $product_id WooCommerce product ID.
	 * @param string $status     licence_status value.
	 * @return int
	 */
	public static function count_licenses( $product_id, $status = 'available' ) {
		global $wpdb;

		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return 0;
		}

		$table = self::table_name();
		$sql   = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND licence_status = %s",
			$product_id,
			$status
		);

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Insert a new license row with status "draft".
	 *
	 * @param int         $product_id   WooCommerce product ID.
	 * @param string|null $licence_code Optional code (max 100 chars); alphanumeric password if omitted.
	 * @return int|false New licence_id, or false on failure.
	 */
	public static function create_draft_license( $product_id, $license_code ) {
		global $wpdb;

		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return false;
		}

		if ( null === $license_code || '' === $license_code ) { return false; }


		$inserted = $wpdb->insert(
			self::table_name(),
			array(
				'product_id'     => $product_id,
				'licence_code'   => $license_code,
				'licence_status' => 'draft',
				'creation_date'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/** @var int Max licence_id values per DELETE ... IN (...) statement. */
	private const SYNC_DELETE_CHUNK = 500;

	/** @var int Max rows per multi-row INSERT. */
	private const SYNC_INSERT_CHUNK = 400;

	/**
	 * Replace the draft licence pool for a product from newline-separated text.
	 * Does not modify rows with other statuses (e.g. available).
	 *
	 * Uses one read of the pool, in-memory diff, batched DELETE/INSERT, and a transaction (non-empty input).
	 *
	 * @param int         $product_id         WooCommerce product ID.
	 * @param string      $raw_text           Unslashed textarea contents.
	 * @param int|null    $developer_user_id  Optional developer user ID for ownership check.
	 * @return true|\WP_Error
	 */
	public static function sync_draft_licenses_from_text( $product_id, $raw_text, $developer_user_id = null ) {
		global $wpdb;


		$result = 0;

		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return new WP_Error( 'ddb_invalid_product', __( 'Invalid product.', DDB_TEXT_DOMAIN ) );
		}

		if ( null !== $developer_user_id && ! Ddb_Frontend::is_product_from_developer( $product_id, $developer_user_id ) ) {
			return new WP_Error( 'ddb_forbidden_product', __( 'You are not allowed to edit licenses for this product.', DDB_TEXT_DOMAIN ) );
		}

		$new_codes = self::parse_unique_license_lines( $raw_text );
		$table     = self::table_name();

		if ( empty( $new_codes ) ) {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE product_id = %d AND licence_status IN ('draft')",
					$product_id
				)
			);
			if ( false === $deleted ) {
				return new WP_Error( 'ddb_license_sync', __( 'Could not update license keys.', DDB_TEXT_DOMAIN ) );
			}
			return true;
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT licence_id, licence_code, licence_status FROM {$table} WHERE product_id = %d AND licence_status IN ('draft','available') ORDER BY licence_id ASC",
				$product_id
			),
			OBJECT
		);
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$draft_by_code   = array();
		$available_codes = array();
		foreach ( $rows as $row ) {
			if ( ! isset( $row->licence_code ) || ! isset( $row->licence_status ) ) {
				continue;
			}
			$code   = (string) $row->licence_code;
			$status = (string) $row->licence_status;
			if ( 'available' === $status ) {
				$available_codes[ $code ] = true;
				continue;
			}
			if ( 'draft' !== $status ) {
				continue;
			}
			if ( ! isset( $draft_by_code[ $code ] ) ) {
				$draft_by_code[ $code ] = array();
			}
			$draft_by_code[ $code ][] = (int) $row->licence_id;
		}

		$new_flip        = array_flip( $new_codes );
		$ids_to_delete   = array();
		$codes_to_insert = array();

		foreach ( $draft_by_code as $code => $ids ) {
			sort( $ids, SORT_NUMERIC );
			if ( isset( $available_codes[ $code ] ) ) {
				foreach ( $ids as $id ) {
					$ids_to_delete[] = $id;
				}
				continue;
			}
			if ( ! isset( $new_flip[ $code ] ) ) {
				foreach ( $ids as $id ) {
					$ids_to_delete[] = $id;
				}
				continue;
			}
			if ( count( $ids ) > 1 ) {
				array_shift( $ids );
				foreach ( $ids as $id ) {
					$ids_to_delete[] = $id;
				}
			}
		}

		foreach ( $new_codes as $code ) {
			if ( isset( $available_codes[ $code ] ) ) {
				$result++;
				continue;
			}

			if ( strlen( $code ) < 4 || strlen( $code ) > self::MAX_LICENSE_KEY_LENGTH ) {
				$result++;
				continue;
			}
			
			if ( ! isset( $draft_by_code[ $code ] ) || empty( $draft_by_code[ $code ] ) ) {
				$codes_to_insert[] = $code;
			}
		}

		$wpdb->query( 'START TRANSACTION' );

		if ( ! self::delete_draft_licence_rows_by_ids_batched( $wpdb, $table, $ids_to_delete ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'ddb_license_sync', __( 'Could not update license keys.', DDB_TEXT_DOMAIN ) );
		}

		if ( ! self::insert_draft_licences_batched( $wpdb, $table, $product_id, $codes_to_insert ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'ddb_license_sync', __( 'Could not save new license keys.', DDB_TEXT_DOMAIN ) );
		}

		$wpdb->query( 'COMMIT' );

		return $result;
	}

	/**
	 * @param string $raw_text Unslashed textarea contents.
	 * @return string[] Unique non-empty codes in first-seen order (max 100 chars).
	 */
	private static function parse_unique_license_lines( $raw_text ) {
		$raw_text = is_string( $raw_text ) ? $raw_text : '';
		$lines    = preg_split( '/\r\n|\r|\n/', $raw_text );
		$out      = array();
		$seen     = array();

		foreach ( $lines as $line ) {
			$line = substr( trim( (string) $line ), 0, 100 );
			if ( '' === $line ) {
				continue;
			}
			if ( isset( $seen[ $line ] ) ) {
				continue;
			}
			$seen[ $line ] = true;
			$out[]         = $line;
		}

		return $out;
	}

	/**
	 * @param \wpdb $wpdb WordPress DB object.
	 * @param string $table Full table name.
	 * @param int[]  $ids   licence_id values to remove.
	 * @return bool
	 */
	private static function delete_draft_licence_rows_by_ids_batched( $wpdb, $table, array $ids ) {
		if ( empty( $ids ) ) {
			return true;
		}

		$ids = array_unique( array_map( 'intval', $ids ) );
		sort( $ids, SORT_NUMERIC );

		foreach ( array_chunk( $ids, self::SYNC_DELETE_CHUNK ) as $chunk ) {
			$placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
			$sql          = $wpdb->prepare( "DELETE FROM {$table} WHERE licence_status = 'draft' AND licence_id IN ({$placeholders})", ...$chunk );
			if ( false === $wpdb->query( $sql ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param \wpdb    $wpdb WordPress DB object.
	 * @param string   $table Full table name.
	 * @param int      $product_id Product ID.
	 * @param string[] $codes Licence codes to insert as draft.
	 * @return bool
	 */
	private static function insert_draft_licences_batched( $wpdb, $table, $product_id, array $codes ) {
		if ( empty( $codes ) ) {
			return true;
		}

		$product_id = (int) $product_id;
		$now        = current_time( 'mysql' );

		foreach ( array_chunk( $codes, self::SYNC_INSERT_CHUNK ) as $chunk ) {
			$value_placeholders = array();
			$prepare_args       = array();
			foreach ( $chunk as $code ) {
				$value_placeholders[] = '(%d,%s,%s,%s)';
				$prepare_args[]     = $product_id;
				$prepare_args[]     = $code;
				$prepare_args[]     = 'draft';
				$prepare_args[]     = $now;
			}

			$sql = "INSERT INTO {$table} (product_id, licence_code, licence_status, creation_date) VALUES " . implode( ',', $value_placeholders );
			if ( false === $wpdb->query( $wpdb->prepare( $sql, ...$prepare_args ) ) ) {
				return false;
			}
		}

		return true;
	}
}
