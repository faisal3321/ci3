<?php

use chriskacerguis\RestServer\RestController;
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends RestController {

    function __construct(){
        parent::__construct();
        $this->load->model('Api_model', 'api');
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
	public function deleteWorker_get()
	{
		$id = $this->get('id');

		if (!$id) {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Invalid id'
			], 400);
		}

		// calling db model 
		$result = $this->api->deleteWorker($id);

		if($result) {
			$this->set_response([
				'status'		=> TRUE,
				'message'		=> 'Worker Deleted Successful!'
			], 200);
		} else {
			$this->set_response([
				'status'		=> FALSE,
				'message'		=> 'Cannot Delete Worker, Something Went Wrong!'
			], 500);
		}
	}


	// Worker List
	public function workerlist_get()
	{
        $res = $this->api->allWorkerData();
        $data = [
            'message' => 'OK',
            'status'    => TRUE,
            'data' => $res
        ];
        $this->set_response($data, 200);
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

    	$res = $this->api->manageWorkerAttendance($wId);

        $data = [
            'message' => 'OK',
            'status'    => TRUE,
            'data' => $res
        ];

        $this->set_response($data, 200);
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

    
}
