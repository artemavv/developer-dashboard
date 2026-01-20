<?php

/**
 * This class manages cron-scheduled mass generation of developer reports.
 * 
 * Mass generation is triggered by clicking the button in admin area.
 * 
 * @see Ddb_Plugin::render_generation_schedule_form(), Ddb_Plugin::do_action()
 */
class Ddb_Cron_Generator extends Ddb_Core {

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
		$is_restarted = $this->cron_params['restarted_at'] ?? false;
    
		/*
		 * processing is performed in one of two modes:
		 * a) $is_restarted == false: we need to eventually process all scheduled developers (one per each execution)
		 * b) $is_restarted == true: we need to process only the stuck developers (one per each execution)
		 */
			 
		/* 
		 * this function may take a lot of time and result in timeout.
		 * 
		 * therefore we need to update developer status for the second time 
		 * in the case is processing finished successfully.
		 *
		 * if there was a timeout or some other error, developer will be stuck in "processing" status
		 */
		$all_developers_processed = $this->process_developer_list( $is_restarted );
		
		// all done, stop the scheduled cron processing
		if ( $all_developers_processed ) {
			$this->stop_processing();
		}
		
		self::wc_log("cron execution finished.");
	
	}
	
	/**
	 * Finds a single schedule developer in the developer list
	 * and generates report for that developer.
	 * 
	 * @param bool true if need to process only the stuck developers
	 * @return bool true if no scheduled developers left to process
	 */
	public function process_developer_list( $stuck_only = false ) {
		
		$found_unprocessed_developer = false;
		
		$batch_number = $this->cron_params['started_at'];
		
		foreach ( $this->cron_results['devs'] as $key => $developer ) {
		
			$scheduled   = ( ! $stuck_only ) && ( $developer->status == 'scheduled' );
			$stuck       = (   $stuck_only ) && ( $developer->status == 'processing' );
			
			if ( $scheduled || $stuck ) {
				$found_unprocessed_developer = true;
				
				// update status for this developer ( to avoid double processing in the next cron execution ) 
				
				if ( $stuck ) {
					$this->cron_results['devs'][$key]->status = 'restarted';
				}
				else {
					$this->cron_results['devs'][$key]->status = 'processing';
				}
				
				update_option( self::OPTION_NAME__RESULTS, $this->cron_results );

				
				/* 
				 * this function may take a lot of time and result in timeout.
				 * 
				 * therefore we need to update developer status for the second time 
				 * in the case is processing finished successfully.
				 *
				 * if there was a timeout or some other error, developer will be stuck in "processing" status
				 */
				$updated_developer = $this->process_developer( $developer );
				
				
				$this->cron_results['devs'][$key] = $updated_developer;
				
				
				// save in DB for the current batch
				update_option( self::OPTION_NAME__RESULTS, $this->cron_results );
				// save in DB for the future use (will be eventually used in payout generation)
				update_option( self::OPTION_NAME__RESULTS . '_' . $batch_number, $this->cron_results );
				
				if ( $updated_developer->status == 'completed' ) {
					break; // allow to process only one developer per singe cron execution
				}
			}
		}
		
		self::wc_log("process_developer_list -- END", array() );
			
		return ( ! $found_unprocessed_developer );
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
			$developer->summary    = $this->report_summary; // this is set by $this->prepare_report_for_single_developer()
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
	 * @param object $developer object { term_id, name, status }
	 * @return string XLS file name, or false if no data in the report
	 */
	protected function prepare_report_for_single_developer( object $developer_term  ) {
		
		
		$start_date = $this->cron_params['start_date'];
		$end_date = $this->cron_params['end_date'];
		
		
		$filename = $developer_term->name . '_report_from_' . $start_date . '_to_' . $end_date . '.xlsx';
		
 		self::wc_log( "prepare_report_for_single_developer -- start. ($start_date , $end_date)", (array) $developer_term );
		
		$settings = array( 
			'save_to_file'  => true,
			'folder_path'   => $this->cron_params['folder_path'],
			'save_path'			=> $this->cron_params['folder_path'] . '/' . $filename 
		);
		
		$processed_orders = Ddb_Report_Generator::generate_xlsx_report( $developer_term, $start_date, $end_date, $settings );

		if ( $processed_orders ) {
			$this->report_summary = Ddb_Report_Generator::get_last_report_summary();
			self::wc_log( 'prepare_report_for_single_developer -- end. Result: ' . $processed_orders, $this->report_summary );
			return $filename;
		}
		else {
			self::wc_log( 'prepare_report_for_single_developer -- end. No products. ' );
			return false;
		}

	}
	
	public static function get_saved_report_data( $timestamp ) {
		return get_option( self::OPTION_NAME__RESULTS . '_' . $timestamp, false );
	}
	
	/**
	 * Prepares the list of developers with their IDs, names, and 'scheduled' status
	 * 
	 * @return array
	 */
	public static function get_developers_list() {
		
		$list = array();
		
		$developers = get_terms( array( 'taxonomy' => 'developer', 'hide_empty' => false ) );
		
		if ( is_array( $developers) ) {
			foreach ( $developers as $developer ) {				
				$dev_object = new stdClass();
				$dev_object->term_id      = $developer->term_id;
				$dev_object->name         = $developer->name;
				$dev_object->status       = 'scheduled';
				
				$list[ $developer->term_id ] = $dev_object;
			}
		}
		
		return $list;
	}
	
	/**
	 * Prepares the data to start cron-scheduled report generation.
	 * 
	 * @param array $parameters must contain keys 'start_date' and 'end_date' in Y-m-d format
	 */
	public static function start_processing( array $parameters ) {

		self::wc_log( 'Started cron', $parameters );

		$timestamp = time();

		$parameters['folder_path'] = self::create_folder_for_reports( $parameters, $timestamp );
		$parameters['folder_url'] = self::create_url_for_reports( $parameters, $timestamp );

		$devs = self::get_developers_list();

		$results = array(
			'start_date' => $parameters['start_date'],
			'end_date'   => $parameters['end_date'],
			'devs'       => $devs,
			'reports'    => array()
		);

		if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {

			// custom scheduling interval 'two minutes' is added 
			$result_sch = wp_schedule_event( time(), 'two_minutes', self::HOOK_NAME );
			self::wc_log( 'wp_schedule_event Ddb_Cron_Generation ', array( 'result' => $result_sch ) );
			
		}

		$parameters['started_at'] = time();

		update_option( self::OPTION_NAME__PARAMETERS, $parameters );

		update_option( self::OPTION_NAME__RESULTS, $results );
	}

	public static function restart_processing() {
		self::wc_log( 'Restarted cron', array() );

		$parameters = get_option( self::OPTION_NAME__PARAMETERS );
		

		if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {

			// custom scheduling interval 'two minutes' is added 
			$result_sch = wp_schedule_event( time(), 'two_minutes', self::HOOK_NAME );
			self::wc_log( 'restart_processing wp_schedule_event Ddb_Cron_Generation ', array( 'result' => $result_sch ) );
			
		}

		$parameters['restarted_at'] = time();

		update_option( self::OPTION_NAME__PARAMETERS, $parameters );

	}

	public static function stop_processing() {

		$timestamp = wp_next_scheduled( self::HOOK_NAME );
		wp_unschedule_event( $timestamp, self::HOOK_NAME );
		wp_clear_scheduled_hook( self::HOOK_NAME );

		//self::make_total_for_mass_reports();
		self::wc_log( 'Stopped cron.' );
	}

	/*
	public static function make_total_for_mass_reports() {
		//$cron_results = get_option( self::OPTION_NAME__RESULTS );
		//$cron_params = get_option( self::OPTION_NAME__PARAMETERS );
	}
	*/
	
	
	public static function render_status() {
		
		$cron_is_running = wp_next_scheduled( self::HOOK_NAME );
		
		$cron_results    = get_option( self::OPTION_NAME__RESULTS );
		
		
		if ( ! is_array( $cron_results['devs'] ?? false )) { // no correct data
			return false;
		}
		
		$dev_list = '';
		$count_scheduled = 0;
		$count_processing = 0;
		$count_completed = 0;
		$count_empty = 0;

		foreach ( $cron_results['devs'] as $dev ) {

			$report = '';
			$total  = '';

			switch ( $dev->status ) {
				case 'scheduled':
					$status = '<em>scheduled</em>';
					$count_scheduled++;
					break;
				case 'processing':
					$status = '<em style="color:red">processing</em>';
					
					$count_processing++;
					break;
				case 'completed':
					$status = '<em style="color:green">completed</em>';
					$report = ' --- <em><a href="' . $dev->report_url . '" target="_blank">download report</a></em>';
					$total = 'profit: $' . $dev->summary['total_dev_profits'];
					$count_completed++;
					break;
				case 'empty':
					$status = '<em style="color:brown">empty</em>';
					$count_empty++;
					break;
				default:
					$status = '<strong style="color:maroon">unknown</strong>';
			}

			$dev_list .= '<div>' . $dev->name . ' : ' . $status . ' ' . $report . ' ' . $total . '</div>';
		}

		
		$out = "<h3>Current status:</h3>";
		
		
		if ( $cron_is_running ) {
			$cron_parameters = get_option( self::OPTION_NAME__PARAMETERS );
			
			$out .= '<h4>Generating reports for range from <strong>' . $cron_parameters['start_date'] . '</strong> to <strong>' . $cron_parameters['end_date'] . '</strong></h4>';
	
			$estimate = round( $count_scheduled * 1.1 );
			$status = "Time to complete: approximately $estimate minutes</p>";
		} else {
			$status = 'inactive';
		}
		
		$out .= '<p><strong>' . $status . '</strong></p>';
		
		$out .= '<p>Scheduled reports: <strong>' . $count_scheduled . '</strong></p>';
		$out .= '<p>Currently processing: <strong>' . $count_processing . '</strong></p>';
		$out .= '<p>Completed reports: <strong>' . $count_completed . '</strong></p>';
		$out .= '<p>Empty reports: <strong>' . $count_empty . '</strong></p>';
		$out .= $dev_list;

					
		return $out;
	}
}
