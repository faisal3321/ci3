<?php

use chriskacerguis\RestServer\RestController;

defined('BASEPATH') OR exit('No direct script access allowed');


class Api extends RestController {
	
    function __construct(){
        parent::__construct();
        $this->load->model('Api_model', 'api');
		$this->load->library('RateLimiter', NULL, 'rl'); // in library alias is third parameter

		$identifier = $this->input->ip_address();

		if (!$this->rl->allowRequest($identifier)) {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Too many requests. Please try again later.'
			], 429);
			exit;
		}
    }

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/userguide3/general/urls.html
	 */


	 // Adding worker
	public function createWorker_post()
	{
		// using the current date and time in Y-m-d H:i:s format
    	$now = date('Y-m-d H:i:s');

		$insertData = [
			// collect data from form
			'name' 			=> $this->post('name'),
			'age' 			=> $this->post('age'),
			'gender' 		=> $this->post('gender'),
			'phone' 		=> $this->post('phone'),
			'address' 		=> $this->post('address'),
			'created_at'	=> $now,
			'updated_at'	=> $now
		];

		// basic validation 
		if (empty($insertData['name']) || empty($insertData['age']) || empty($insertData['gender']) || empty($insertData['phone'])) {
			$this->set_response([
				'status' => FALSE,
				'message'=> "Please enter the required fields",
			], 400);

			return ;
		};

		// calling model here
    	$result = $this->api->insertWorker($insertData);

		if ($result) {
			$this->set_response([
				'status'	=> TRUE,
				'message'	=> 'Worker Added Successfully!'
			], 201);
		} else {
			$this->set_response([
				'status'	=> FALSE,
				'message'	=> 'Failed to add worker'
			], 500);
		}
	}



	// update worker 
	public function updateWorker_post()
	{
		$id = $this->post('id');
		$now = date('Y-m-d H:i:s');

		$updateData = [
			'name'			=> $this->post('name'),
			'age'			=> $this->post('age'),
			'gender'		=> $this->post('gender'),
			'phone'			=> $this->post('phone'),
			'address'		=> $this->post('address'),
			// 'created_at'	=> $now, // we do not need created_at when updating
			'updated_at'	=> $now
		];

		// simple validation
		if (empty($updateData['name']) || empty($updateData['age']) || empty($updateData['gender']) || empty($updateData['phone'])) {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Please fill the requires field'
			], 400);
			return;
		}

		// calling model
		$res = $this->api->updateWorker($id, $updateData);

		if ($res) {
			$this->set_response([
				'status'		=> TRUE,
				'message'		=> 'Worker Updated Successfully!'
			], 200);
		} else {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Worker Updation Failed'
			], 500);
		}
	}



	// Update worker
	public function updateWorker_get()
	{
		$id = $this->get('id');

        $worker = $this->api->singleWorkerData($id);

		if ($worker) {
			$this->set_response([
				'status'		=> TRUE,
				'data'			=> $worker[0]
			], 200);
		} else {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Something went wrong, Id not found'
			], 404);
		}
	}



	// Delete worker
	public function deleteWorker_delete($id = null)
	{
		if ($id === null) {
			$id = $this->get('id');
		}

		if (!$id) {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Invalid id'
			], 400);
			return;
		}

		// Check if worker exists
		$worker = $this->api->singleWorkerData($id);
		
		if (!$worker) {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Worker not found'
			], 404);
			return;
		}

		// calling db model 
		$result = $this->api->deleteWorker($id);

		if($result) {
			$this->set_response([
				'status' => TRUE,
				'message' => 'Worker Deleted Successfully!'
			], 200);
		} else {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Cannot Delete Worker, Something Went Wrong!'
			], 500);
		}
	}



	// Show Worker List
	public function workerlist_get()
	{
		// throttle
		$ip = $this->input->ip_address();
    	$this->rl->throttle($ip);


        $res = $this->api->allWorkerData();
        $data = [
            'message' => 'OK',
            'status'    => TRUE,
            'data' => $res
        ];
        $this->set_response($data, 200);
	}



	// server side pagination to find worker list
	public function workerlist1_post()
	{
		// throttle
		$ip = $this->input->ip_address();
    	$this->rl->throttle($ip);

		// Capture DataTables inputs
		$draw   = $this->input->post('draw');
		$start  = $this->input->post('start');
		$length = $this->input->post('length');
		$search = $this->input->post('search')['value'];
		
		// Get Order details (which column to sort by)
		$order_column_index = $this->input->post('order')[0]['column'];
		$order_dir = $this->input->post('order')[0]['dir'];
		
		// Map column index to actual DB column name
		$columns = array('id', 'name', 'age', 'phone', 'gender', 'address');
		$order_col = $columns[$order_column_index];

		// Get processed data from Model
		$result = $this->api->workersPaginationServerSide($start, $length, $search, $order_col, $order_dir);

		// response
		$response = [
			"draw"            => intval($draw),
			"recordsTotal"    => $result['totalRecords'],
			"recordsFiltered" => $result['totalFiltered'],
			"data"            => $result['data']
		];

		$this->set_response($response, 200);
	}



	// Single Worker
    public function singleWorkerInfo_get()
	{       

        $res = $this->api->singleWorkerData($_GET['workerId']);
		
        $data = [
            'message' => 'OK',
            'status'    => TRUE,
            'data' => $res
        ];
        $this->set_response($data, 200);
	}



	// Attendance of Worker
	public function manageattendance_get()
	{       
		$wId = $this->get('workerId'); 
		$start = $this->get('startDate');
		$end = $this->get('endDate');

    	$res = $this->api->manageWorkerAttendance($wId, $start, $end);

		$this->set_response([
			'status'		=> TRUE,
			'data'			=> $res
		], 200);
	}



	// save attendance
	public function submitAttendance_post()
	{
		$worker_id = $this->post('worker_id');
		$date = $this->post('attendance_date');

		if (!$worker_id || !$date) {
			return $this->set_response([
				'status' => FALSE, 
				'message' => 'Missing Data'
			], 400);
		}

		$data = [
			'worker_id'                => $worker_id,
			'attendance_date'          => $date,
			'worker_attendance'        => $this->post('worker_attendance'),
			'customer_side_attendance' => $this->post('customer_side_attendance'),
			'punch_in'                 => '08:00:00',
			'punch_out'                => '20:00:00',
			'updated_at'               => date('Y-m-d H:i:s')
		];

		$result = $this->api->saveAttendance($data);

		if ($result) {
			$this->set_response(['status' => TRUE, 'message' => 'Attendance Saved!'], 200);
		} else {
			$this->set_response(['status' => FALSE, 'message' => 'Database Error'], 500);
		}
	}


	
	// Generate Calendar date
	public function runDailyCalendar_get() 
	{
		$date = $this->api->generateCalendar();

		if ($date) {
			$this->set_response([
				'status'		=> TRUE,
				'message'		=> 'Date generated in calendar'
			], 200);
		} else {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Date already exists or failed'
			], 409);
		}
	}
















	// ===========================     Worker History     ===============================

	
	// get worker history data
	public function workerHistory_get()
	{
		// Get worker_id from the request 
		$worker_id = $this->get('worker_id')? $this->get('worker_id') : $this->uri->segment(3);
		
		// Check if worker_id is provided
		if(!$worker_id) {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Worker ID is required'
			], 400);
			return;
		}
		
		$res = $this->api->workerHistory($worker_id);

		if($res) {
			$this->set_response([
				'status' => TRUE,
				'data' => $res 
			], 200);
		} else {
			$this->set_response([
				'status' => FALSE,
				'message' => 'No records found'
			], 404);
		}
	}



	// Add worker History into table 
	public function addWorkerHistory_post()
	{
		$worker_id = $this->post('worker_id')? $this->post('worker_id') : $this->uri->segment(3);

		$start = $this->post('work_start_date');
    	$end = $this->post('work_end_date');
		
		// Check if worker_id is provided
		if(!$worker_id) {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Worker ID is required' . $worker_id
			], 400);
			return;
		}

		// Check if start date is provided
		if(empty($start)) {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Work start date is required'
			], 400);
			return;
		}

		// validation end date should not be before start date 
		if(!empty($end)) {
			if ($start > $end) {
				$this->set_response([
					'status'		=> FALSE,
					'message'		=> 'End date should not be before Start date'
				], 400);
				return;
			}
		}

		// check for date overlapping with existing records
		$hasOverlapp = $this->api->checkDateOverlap($worker_id, $start, $end);
		if($hasOverlapp) {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Dates overlapped from existing dates. Please change the dates'
			], 400);
			return;
		}

		$res = $this->api->addWorkerHistory($worker_id);

		if($res) {
			$this->set_response([
				'status'		=> TRUE,
				'message'		=> 'worker history added successfully',
				'data'			=> $res
			], 200);
		} else {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Something went wrong'
			], 400);
		}
	}



	// Edit worker hisory
	public function editWorkerHistory_post($id = null, $start = null, $end = null)
	{
		$id = $id ? $id : $this->post('id');
		$start = $start ? $start : $this->post('work_start_date');
		$end = $end ? $end : $this->post('work_end_date');

		// Validate required fields (end date is optional)
		if (!$id || !$start) {
			$this->set_response([
				'status'  => FALSE,
				'message' => 'Missing: id, work_start_date'
			], 400);
			return;
		}

		// If end date is empty, treat as open (default value)
		if (empty($end)) {
			$end = '0000-00-00 00:00:00';
		}

		// Only validate start > end if end is a real date (not the default)
		if ($end != '0000-00-00 00:00:00' && $start > $end) {
			$this->set_response([
				'status'  => FALSE,
				'message' => 'End date should not be before Start date'
			], 400);
			return;
		}

		// Get worker_id for this record using model method
		$worker_id = $this->api->getWorkerIdFromHistory($id);
		if (!$worker_id) {
			$this->set_response([
				'status'  => FALSE,
				'message' => 'Record not found'
			], 404);
			return;
		}

		$hasOverlapp = $this->api->checkDateOverlap($worker_id, $start, $end, $id);
		if ($hasOverlapp) {
			$this->set_response([
				'status'  => FALSE,
				'message' => 'Dates overlapped from existing dates. Please change the dates'
			], 400);
			return;
		}

		$res = $this->api->editWorkerHistory($id, $start, $end);

		if ($res) {
			$this->set_response([
				'status'  => TRUE,
				'message' => 'worker history updated successfully',
				'data'    => $res
			], 200);
		} else {
			$this->set_response([
				'status'  => FALSE,
				'message' => 'updation failed ! Something went wrong'
			], 400);
		}
	}



	// check open record for the last row
	public function checkOpenWorkerHistory_post()
	{
		$worker_id = $this->post('worker_id') ? $this->post('worker_id') : $this->uri->segment(3);
		
		if(!$worker_id) {
			$this->set_response([
				'status' => FALSE,
				'message' => 'Worker ID is required'
			], 400);
			return;
		}

		$last = $this->api->getWorkerLastHistory($worker_id);

		if($last && $last['work_end_date'] == '0000-00-00 00:00:00') {
			$this->set_response([
				'status' => TRUE,
				'open' => TRUE,
				'message' => 'Please close previous work history first!',
				'data' => $last
			], 200);
		} else {
			$this->set_response([
				'status' => TRUE,
				'open' => FALSE
			], 200);
		}
	}



	// soft delete worker history
	public function deleteWorkerHistory_delete($id = null)
	{
		if( $id == null) {
			$this->get('id');
		}

		if(!$id) {
			$this->set_response([
				'status'			=> FALSE,
				'message'			=> 'Invalid ID'
			], 400);
		}

		$res = $this->api->deleteWorkerHistory($id);
		
		if($res) {
			$this->set_response([
				'status'		=> TRUE,
				'message'		=> 'Worker history row deleted successfully.'
			], 200);
		} else {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Something went wrong !'
			], 400);
		}
	}
	
}
