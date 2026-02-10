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
		if (empty($insertData['name']) || empty($insertData['age']) || empty($insertData['gender']) || empty($insertData['address'])) {
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

    
}
