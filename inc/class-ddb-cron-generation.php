<?php

/**
 * This class manages cron-scheduled mass generation of developer reports.
 * 
 * Mass generation is triggered by clicking the button in admin area.
 * 
 * @see Ddb_Plugin::render_generation_schedule_form(), Ddb_Plugin::do_action()
 */
class Ddb_Cron_Generation extends Ddb_Core {

	public const OPTION_NAME__RESULTS      = 'ddb_cron_results';
	public const OPTION_NAME__PARAMETERS   = 'ddb_cron_parameters';
	public const HOOK_NAME                 = 'ddb_cron_hook';
	
	
	protected $cron_results = array();
	protected $cron_params  = array();
	private $report_summary = array();
	
	public function __construct() {

		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
		add_action( self::HOOK_NAME, array( $this, 'execute_scheduled_action' ) );
		
	}
	
	/**
	 * Adds our custom options for cron schedule
	 * 
	 * @hook cron_schedules
	 * @param array $schedules
	 * @return array
	 */
	public function add_cron_interval( $schedules ) { 
			$schedules['one_minute'] = array(
					'interval' => 60,
					'display'  => esc_html__( 'Every Minute' ), );
      $schedules['two_minutes'] = array(
					'interval' => 120,
					'display'  => esc_html__( 'Every 2 Minutes' ), );
      
			return $schedules;
	}
	
	
	public function execute_scheduled_action() {
		
		// schedule the next occurence of this repeated event
		wp_schedule_event( time(), 'one_minute', self::HOOK_NAME );
    
    
		self::wc_log("cron execution started.");
		
		$this->cron_results = get_option( self::OPTION_NAME__RESULTS );
		$this->cron_params = get_option( self::OPTION_NAME__PARAMETERS );
		
    $batch_number = $this->cron_params['started_at'];
		
		$is_restarted = $this->cron_params['restarted_at'] ?? false;
    
		$current_dev = false;
		
		if ( ! $is_restarted ) {
			$all_developers_processed = $this->process_developer_list();
		}
		else {
			
			$all_developers_processed = $this->process_developer_list( true );
			foreach ( $this->cron_results['devs'] as $key => $dev ) {
				if ( $dev['status'] == 'processing' ) {
					$all_developers_processed = false;
					$current_dev = $dev;

					$cron_results['devs'][$key]['status'] = 'restarted';
					update_option( 'aff_cron_results', $cron_results);
					self::log("Restarted report for developer " . $dev['id'] . " , " . $dev['name']);
					
					$filename = $this->prepareReportForSingleDeveloper( $current_dev, $cron_params, $cron_results );

					if ( $filename ) {
						$cron_results['devs'][$key]['status'] = 'completed';
						$cron_results['devs'][$key]['summary'] = $this->report_summary;
						$cron_results['devs'][$key]['report_url'] = $cron_params['folder_url'] . '/' . $filename;
						update_option( 'aff_cron_results', $cron_results);

						update_option( 'aff_cron_results_' . $batch_number, $cron_results);
						break;
					}
				}
			}
		}
		
		if ( $all_developers_processed ) {
			$this->stop_processing();
		}
		
		self::wc_log("cron execution finished.");
	
	}
	
	/**
	 * Finds single schedule developer in the developer list, 
	 * and generates report for that developer.
	 * 
	 * @return bool true no scheduled developers left to process
	 */
	public function process_developer_list( $stuck_only = false ) {
		
		$all_developers_processed = true;
		
		$batch_number = $this->cron_params['started_at'];
		
		foreach ( $this->cron_results['devs'] as $key => $developer ) {
		
			$scheduled   = ( ! $stuck_only ) && ( $developer->status == 'scheduled' );
			$stuck       = (   $stuck_only ) && ( $developer->status == 'processing' );
			
			if ( $scheduled || $stuck ) {
				$all_developers_processed = false;
				
				// update status for this developer ( to avoid double processing in the next cron execution ) 
				
				if ( $stuck ) {
					$this->cron_results['devs'][$key]->status = 'restarted';
				}
				else {
					$this->cron_results['devs'][$key]->status = 'processing';
				}
				
				update_option( self::OPTION_NAME__RESULTS, $this->cron_results );

				
				$updated_developer = $this->process_developer( $developer );
				$this->cron_results['devs'][$key] = $updated_developer;
				
				
				// save in DB for the current batch
				update_option( self::OPTION_NAME__RESULTS, $this->cron_results );
				// save in DB for the future use (will be eventually used in payout generation)
				update_option( self::OPTION_NAME__RESULTS . '_' . $batch_number, $this->cron_results );
				
				if ( $updated_developer['status'] == 'completed' ) {
					break; // allow to process only one developer per singe cron execution
				}
			}
		}
			
		return $all_developers_processed;
	}
	
	/**
	 * Generates report for the specified developer.
	 * Returns updated developer array ( with the results of report generation )
	 * 
	 * 
	 * @param object $developer { term_id, name, status }
	 * @return object
	 */
	public function process_developer( object $developer ) {
		
		self::wc_log("Working on reports for developer " . $developer->term_id . " , " . $developer->name);

		$filename = $this->prepare_report_for_single_developer( $developer );

		if ( $filename ) {
			// update status & summary for the developer in the current batch of reports
			$developer->status     = 'completed';
			$developer->summary    = $this->report_summary;
			$developer->report_url = $this->cron_params['folder_url'] . '/' . $filename;
		}
		else {
			// set 'empty' status for the developer in the current batch of reports
			$developer->status     = 'empty';
			$developer->report_url = false;
		}

		return $developer;
	}
	
	/**
	 * Generates report for the specified developer and saves it into XLS file.
	 * 
	 * @param array $developer array( id, name, status )
	 * @return string XLS file name, or false if no daya in the report
	 */
	protected function prepare_report_for_single_developer( object $developer_term, $start_date, $end_date  ) {
		
 		self::wc_log( 'prepare_report_for_single_developer -- start.', (array) $developer_term );
		
		$filename = Ddb_Report_Generator::generate_xlsx_report( $developer_term, $start_date, $end_date,  );

		if ( $filename ) {
			$this->report_summary = Ddb_Report_Generator::get_last_report_summary();
			self::wc_log( 'prepare_report_for_single_developer -- end. Result: ' . $filename, $this->report_summary );
			return $filename;
		}
		else {
			self::wc_log( 'prepare_report_for_single_developer -- end. No products. ' );
			return false;
		}

	}
	
	public static function process_stuck_developer ( $developer ) {
		
	}
	
	
	/**
	 * Prepares the list of developers with their IDs, names, and 'scheduled' status
	 * 
	 * @return array
	 */
	public function get_developers_list() {
		
		$list = array();
		
		$developers = get_terms( array( 'taxonomy' => 'developer', 'hide_empty' => false ) );
		
		if ( is_array( $developers) ) {
			foreach ( $developers as $developer ) {				
				$dev_object = new stdClass();
				$dev_object->id      = $developer->term_id;
				$dev_object->name    = $developer->name;
				$dev_object->status  = 'scheduled';
				
				$list[ $developer->term_id ] = $dev_object;
			}
		}
		
		return $list;
	}
	
	/**
	 * Sets the scene for cron-scheduled report generstion.
	 * @param array $parameters must contain keys 'start_date' and 'end_date' in Y-m-d format
	 */
	public static function start_processing( array $parameters ) {

		self::wc_log( 'Started cron', $parameters );

		$timestamp = time();

		$parameters['folder_path'] = self::create_folder_for_reports( $parameters, $timestamp );
		$parameters['folder_url'] = self::create_url_for_reports( $parameters, $timestamp );

		$devs = self::get_developers_list();

		$order_ids = self::get_order_ids( $parameters );

		$results = array(
			'start_date' => $parameters['start_date'],
			'end_date'   => $parameters['end_date'],
			'devs'       => $devs,
			'order_ids'  => $order_ids,
			'reports'    => array()
		);

		if ( ! wp_next_scheduled( 'aff_cron_hook' ) ) {

			// custom scheduling interval 'two minutes' is added 
			$result_sch = wp_schedule_event( time(), 'two_minutes', 'aff_cron_hook' );
			self::wc_log( 'wp_schedule_event -- aff_cron_hook', array( 'result' => $result_sch ) );
			
		}

		$parameters['started_at'] = time();

		update_option( 'aff_cron_parameters', $parameters );

		update_option( 'aff_cron_results', $results );
	}

	public static function restart_processing() {
		
	}

	public static function stop_processing() {

		$timestamp = wp_next_scheduled( 'aff_cron_hook' );
		wp_unschedule_event( $timestamp, 'aff_cron_hook' );
		wp_clear_scheduled_hook( 'aff_cron_hook' );

		self::make_total_for_mass_reports();
		self::wc_log( 'Stopped cron.' );
	}

	// todo
	public static function make_total_for_mass_reports() {
		
	}
}
