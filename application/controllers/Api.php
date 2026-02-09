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

    
}
