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

        $query =  $this->db->get('workers');
		return $query->result_array();
	}



    public function singleWorkerData($wrkId)
    {
        // $sql = "SELECT * FROM workers where id = '$wrkId'";
        // // Execute the query
        // $query = $this->db->query($sql);
        // // Return the results as an associative array
        // return $query->result_array();
		
		return $this->db->get_where('workers', ['id' => $wrkId])->result_array();
    }



	// create worker or add worker
	public function insertWorker($data)
	{
		return $this->db->insert('workers', $data);
	}



	// delete worker
	public function deleteWorker($id)
	{
		return $this->db->where('id', $id)->delete('workers');
	}



	// update worker
	public function updateWorker($id, $data)
	{
		return $this->db->where('id', $id)->update('workers', $data);
	}



	public function manageWorkerAttendance($workerId, $startDate = NULL, $endDate = NULL)
	{
		// to make sure today's date is present in the calendar
		$this->generateCalendar();

		// get worker information to know when the joining date
		$worker = $this->db->select('name, created_at')->get_where('workers', ['id' => $workerId])->row();

		if (!$worker) {
			return [];
		}

		$workerName  = $worker->name;
		$joiningDate = date('Y-m-d', strtotime($worker->created_at));
		$today = date('Y-m-d');

		// auto-sync
		$this->db->select('calendar_date, is_weekend');
		$this->db->from('calendar');
		$this->db->where('calendar_date >=', $joiningDate);
		$this->db->where('calendar_date <=', $today);
		$allDates = $this->db->get()->result();

		foreach ($allDates as $date) {
			$check = $this->db->get_where('attendance', [
				'worker_id' => $workerId, 
				'attendance_date' => $date->calendar_date
			])->num_rows();

			if ($check == 0) {
				
				$defaultWorker = ($date->is_weekend == 1) ? 4 : 1; // Holiday if weekend, else Present
				$defaultCust   = ($date->is_weekend == 1) ? 4 : 0; // Holiday if weekend, else N/A

				$this->db->insert('attendance', [
					'worker_id' => $workerId,
					'attendance_date' => $date->calendar_date,
					'worker_attendance' => $defaultWorker,
					'customer_side_attendance' => $defaultCust,
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s')
				]);
			}
		}

		// fetch the data
		$this->db->select('
			a.id,
			a.worker_id,
			w.name,
			c.calendar_date as attendance_date,
			c.is_weekend,
			a.worker_attendance,
			a.customer_side_attendance
		', FALSE);

		$this->db->from('calendar c');
		$this->db->join('attendance a', 'a.attendance_date = c.calendar_date', 'inner');
		$this->db->join('workers w', 'w.id = a.worker_id', 'inner');

		$this->db->where('a.worker_id', $workerId);
		$this->db->where('c.calendar_date >= ', $joiningDate);
		$this->db->where('c.calendar_date <= ', $today);

		if($startDate) {
			$this->db->where('c.calendar_date >= ', $startDate);
		}
		if($endDate) {
			$this->db->where('c.calendar_date <= ', $endDate);
		}   

		$this->db->order_by('c.calendar_date', 'DESC');
		return $this->db->get()->result_array();

	}



	// Inserts the array into the 'attendance' table
	public function saveAttendance($data)
	{
		// Check if record exists for this worker on this date
		$exists = $this->db->get_where('attendance', [
			'worker_id' => $data['worker_id'],
			'attendance_date' => $data['attendance_date']
		])->row();

		if ($exists) {
			$this->db->where('id', $exists->id);
			return $this->db->update('attendance', $data);
		} else {
			$data['created_at'] = date('Y-m-d H:i:s');
			return $this->db->insert('attendance', $data);
		}
	}


	
	// generate calendar
	public function generateCalendar()
	{
		$now = date('Y-m-d H:i:s');
		$today = date('Y-m-d');
		$dayOfWeek = date('w', strtotime($today));

		$insertDate = [
			'calendar_date'		=> $today,
			'day'				=> date('l'),
			'month'				=> date('F'),
			'year'				=> date('Y'),
			'is_weekend'		=> ($dayOfWeek == 0) ? 1 : 0,
			'created_at'		=> $now,
			'updated_at'		=> $now
		];

		// check if date already exist to prevent duplication
		$exist = $this->db->get_where('calendar', ['calendar_date' => $today])->num_rows();

		if(!$exist) {
			$this->db->insert('calendar', $insertDate);
		}

		// fetch the data in descending order
		$this->db->order_by('id', 'DESC');
		$query = $this->db->get('calendar'); 
		
		return $query->result();
	}
    

    
}
