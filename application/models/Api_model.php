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
		$this->db->order_by('id', 'DESC');
        return $this->db->get('workers')->result_array();
	}

	public function workersPaginationServerSide($start, $length, $search, $order_col, $order_dir)
	{
		// total record
		$totalRecords = $this->db->count_all('workers');

		// 2. Start building query for filtered data
		$this->db->from('workers');

		// Search Logic
		if (!empty($search)) {
			$this->db->group_start();
			$this->db->like('id', $search);
			$this->db->or_like('name', $search);
			$this->db->or_like('phone', $search);
			$this->db->or_like('address', $search);
			$this->db->group_end();
		}

		// Get count of filtered results before applying limit
		$totalFiltered = $this->db->count_all_results('', FALSE);

		// 3. Sorting and Pagination
		$this->db->order_by($order_col, $order_dir);
		$this->db->limit($length, $start);
		
		$query = $this->db->get();

		return [
			'totalRecords' => $totalRecords,
			'totalFiltered' => $totalFiltered,
			'data'          => $query->result_array()
		];
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

    // AUTO-SYNC: Only get dates where a "worker_history" record exists, this prevents creating attendance for gaps (like Feb 9-11 in your example)
    $this->db->select('c.id, c.calendar_date, c.is_weekend');
    $this->db->from('calendar c');

    // Join calendar with worker_history based on the date falling within start-end range
    $this->db->join('worker_history wh', "c.calendar_date >= wh.work_start_date 
        AND (wh.work_end_date = '0000-00-00 00:00:00' OR wh.work_end_date >= c.calendar_date)", 'inner');

    $this->db->where('wh.worker_id', $workerId);
    $this->db->where('wh.isDeleted', '0');
    $this->db->where('c.calendar_date >=', $joiningDate);
    $this->db->where('c.calendar_date <=', $today);

    // Group by ID so if a worker has two history records for one day, it doesn't create duplicate attendance rows.
    $this->db->group_by('c.id'); 

    $allDates = $this->db->get()->result();

    foreach ($allDates as $date) {
        $check = $this->db->get_where('attendance', [
            'worker_id' => $workerId, 
            'attendance_date' => $date->id
        ])->num_rows();

        if ($check == 0) {
            
            $defaultWorker = ($date->is_weekend == 1) ? 4 : 1; // Holiday if weekend, else Present
            $defaultCust   = ($date->is_weekend == 1) ? 4 : 0; // Holiday if weekend, else N/A

            $this->db->insert('attendance', [
                'worker_id' => $workerId,
                'attendance_date' => $date->id,
                'worker_attendance' => $defaultWorker,
                'customer_side_attendance' => $defaultCust,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    // fetch the data – now filtered by worker_history periods
    $this->db->select('
        a.id,
        a.worker_id,
        w.name,
        c.id as calendar_id,
        c.calendar_date as attendance_date,
        c.is_weekend,
        a.worker_attendance,
        a.customer_side_attendance
    ', FALSE);

    $this->db->from('calendar c');
    $this->db->join('attendance a', 'a.attendance_date = c.id', 'inner');
    $this->db->join('workers w', 'w.id = a.worker_id', 'inner');

    $this->db->where('a.worker_id', $workerId);
    $this->db->where('c.calendar_date >= ', $joiningDate);
    $this->db->where('c.calendar_date <= ', $today);

    // only include dates that belong to an active work period ---
    $this->db->where("EXISTS (
        SELECT 1 
        FROM worker_history wh
        WHERE wh.worker_id = a.worker_id
          AND wh.isDeleted = '0'
          AND c.calendar_date >= wh.work_start_date
          AND (wh.work_end_date = '0000-00-00 00:00:00' OR wh.work_end_date >= c.calendar_date)
    )", NULL, FALSE);

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
		// Set to India Time
		date_default_timezone_set('Asia/Kolkata');

		$startDate = new DateTime('2026-01-01');
		$endDate   = new DateTime(); // Today
		
		// We use <= to ensure it includes today's date
		while ($startDate <= $endDate) {
			$currentDateString = $startDate->format('Y-m-d');
			
			// Check if this specific date in the loop exists
			$exist = $this->db->get_where('calendar', ['calendar_date' => $currentDateString])->num_rows();

			if (!$exist) {
				$dayOfWeek = $startDate->format('w'); // 0 for Sunday
				$now = date('Y-m-d H:i:s');

				$insertDate = [
					'calendar_date' => $currentDateString,
					'day'           => $startDate->format('l'),
					'month'         => $startDate->format('F'),
					'year'          => $startDate->format('Y'),
					'is_weekend'    => ($dayOfWeek == 0) ? 1 : 0, // Only Sunday is 1
					'created_at'    => $now,
					'updated_at'    => $now
				];

				$this->db->insert('calendar', $insertDate);
			}

			// Move to the next day in the loop
			$startDate->modify('+1 day');
		}

		// Fetch the data in descending order to return to your API
		$this->db->order_by('calendar_date', 'DESC');
		$query = $this->db->get('calendar'); 
		
		return $query->result();
	}















	





	// ========================    Worker History    ===========================


	// public function workerHistory($worker_id) {
	// 	$exist = $this->db->get_where('worker_history', ["worker_id" => $worker_id])->result_array();
	// 	return $exist;
	// }
    
	public function workerHistory($worker_id) {

		$this->db->select('
		wh.id,
		wh.worker_id,
		w.name as name,
		wh.work_start_date,
		wh.work_end_date,
		wh.isDeleted,
		wh.createdAt,
		wh.updatedAt, 
		');
		$this->db->from('worker_history as wh');
		$this->db->join('workers as w', 'w.id = wh.worker_id', 'inner');
		$this->db->where('wh.worker_id', $worker_id);
		// only show not deleted column
		$this->db->where('wh.isDeleted', '0');
		
		$exist = $this->db->get()->result_array();
		
		return $exist;
	}


	// Adding Worker History Table
	public function addWorkerHistory($worker_id) {

		// fetch worker_id and name from workers table to insert here
		$this->db->select('name');
		$this->db->where('id', $worker_id);
		$worker = $this->db->get('workers')->row();

		$name = ($worker) ? $worker->name : '';

		// get date input sent from Ajax
		$work_start_date = $this->input->post('work_start_date');
		$work_end_date = $this->input->post('work_end_date');

		$now = date('Y-m-d H:i:s');
		$default_date = "0000-00-00 00:00:00";

		$insertData = [
			'worker_id'			=> $worker_id,
			'name'				=> $name,
			'work_start_date'	=> $work_start_date,
			'work_end_date'		=> empty($work_end_date) ? $default_date : $work_end_date,
			'isDeleted'			=> "0",
			'createdAt'			=> $now,
			'updatedAt'			=> $now
		];

		$this->db->insert('worker_history', $insertData);

		$insertData['id'] = $this->db->insert_id();
		return $insertData;
	}


	// edit worker history
	public function editWorkerHistory($id, $work_start_date, $work_end_date)
	{
		$data = [
			'work_start_date'	=> $work_start_date,
			'work_end_date'		=> $work_end_date,
			'updatedAt'       	=> date('Y-m-d H:i:s')
		];

		return $this->db->where('id', $id)->update('worker_history', $data);
	}


	// get last worker history row
	public function getWorkerLastHistory($worker_id)
	{
		return $this->db
			->where('worker_id', $worker_id)
			->where('isDeleted', '0')
			->order_by('id', 'DESC')
			->limit(1)
			->get('worker_history')
			->row_array();
	}


	// Get worker_id from history record
	public function getWorkerIdFromHistory($id)
	{
		$record = $this->db->select('worker_id')
						->where('id', $id)
						->get('worker_history')
						->row();
		
		return ($record) ? $record->worker_id : null;
	}


	// check for overlap date
	public function checkDateOverlap($worker_id, $new_start, $new_end, $exclude_id = null)
	{
		$this->db->where('worker_id', $worker_id);
		$this->db->where('isDeleted', '0');

		// exclude current record when editing
		if($exclude_id) {
			$this->db->where('id != ', $exclude_id );
		}

		$existing = $this->db->get('worker_history')->result_array();

		// very far date of future
		$new_end = (empty($new_end) || $new_end == '0000-00-00 00:00:00') ? '9999-12-31' : $new_end;

		// loop through all records that are present in table of single worker
		foreach($existing as $record) {

			$existing_start = $record['work_start_date'];
			$existing_end = $record['work_end_date'];

			if ($existing_end == '0000-00-00 00:00:00' || empty($existing_end)) {
				$existing_end = '9999-12-31';
			}

			 if($new_start <= $existing_end && $new_end >= $existing_start) {
				return true; // Overlap found
			}
		}
		return false;
	}


	// Soft delete worker history
	public function deleteWorkerHistory($id)
	{
		 $data = [
			'isDeleted' => '1',
			'updatedAt' => date('Y-m-d H:i:s')
    	];
		return $this->db->where('id', $id)->update('worker_history', $data);
	}

    
}
