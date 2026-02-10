<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model {

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
	public function allWorkerData()
	{
        // $result = [
        //     'a','b'
        // ];
		// return $result;

        // $query = $this->db->get('workers');
        // return $query->result_array();

        $sql = "SELECT * FROM workers";
        
        // Execute the query
        $query = $this->db->query($sql);
        
        // Return the results as an associative array
        return $query->result_array();
	}

    public function singleWorkerData($wrkId)
    {
        // $sql = "SELECT * FROM workers where id = '$wrkId'";
        // // Execute the query
        // $query = $this->db->query($sql);
        // // Return the results as an associative array
        // return $query->result_array();

		// This replaces the entire block of raw SQL
		return $this->db->get_where('workers', array('id' => $wrkId))
        	->result_array();
    }

	public function manageWorkerAttendance($workerId)
    {
		$this->db->where('worker_id', $workerId);
		$query = $this->db->get('attendance');
        return $query->result_array();
    }
    

    
}
