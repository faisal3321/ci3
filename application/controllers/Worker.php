<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Worker extends CI_Controller {

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
	public function index()
	{
		$this->load->view('admin/worker/list');
	}

	public function list2()
	{
		$this->load->view('admin/worker/list2');
	}

    public function add($id = NULL)
	{	
		$data['workerId'] = $id;
		$this->load->view('admin/worker/add', $data);
	}

    public function manage()
	{
        $wId = $this->uri->segment(3);
        
        $data = [
            'workerId' => $wId            
        ];
		$this->load->view('admin/attendance/manage',$data);
	}

	public function workerHistory()
	{
		$this->load->view('admin/worker/workerHistory');
	}

    
}
