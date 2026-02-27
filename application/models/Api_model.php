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
		// 1. Ensure calendar is up to date
		$this->generateCalendar();

		// 2. Get worker info
		$worker = $this->db->select('name, created_at')->get_where('workers', ['id' => $workerId])->row();

		if (!$worker) {
			return [];
		}

		$today = date('Y-m-d');

		// 3. Find all dates that SHOULD have attendance based on worker_history
		$this->db->select('c.id, c.calendar_date');
		$this->db->from('calendar c');
		$this->db->join('worker_history wh', "c.calendar_date >= wh.work_start_date 
			AND (wh.work_end_date = '0000-00-00 00:00:00' OR wh.work_end_date >= c.calendar_date)", 'inner');
		$this->db->where('wh.worker_id', $workerId);
		$this->db->where('wh.isDeleted', '0');
		$this->db->where('c.calendar_date <=', $today);
		$this->db->group_by('c.id'); 

		$allDates = $this->db->get()->result();

		foreach ($allDates as $date) {
			// Check if record exists AT ALL (ignore isDeleted here)
			// This prevents "Duplicate Entry" errors during sync
			$existsAtAll = $this->db->get_where('attendance', [
				'worker_id' => $workerId, 
				'attendance_date' => $date->id
			])->num_rows();

			if ($existsAtAll == 0) {
				$this->db->insert('attendance', [
					'worker_id' => $workerId,
					'attendance_date' => $date->id,
					'worker_attendance' => 1, 
					'customer_side_attendance' => 0,
					'isDeleted' => '0',
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s')
				]);
			}
		}

		// 4. Fetch the data to return to the UI
		$this->db->select('
			a.id,
			a.worker_id,
			w.name,
			c.id as calendar_id,
			c.calendar_date as attendance_date,
			a.worker_attendance,
			a.customer_side_attendance,
			a.isDeleted
		', FALSE);

		$this->db->from('calendar c');
		$this->db->join('attendance a', 'a.attendance_date = c.id', 'inner');
		$this->db->join('workers w', 'w.id = a.worker_id', 'inner');

		$this->db->where('a.worker_id', $workerId);
		$this->db->where('a.isDeleted', '0'); // Show only active records
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
			$data['updated_at'] = date('Y-m-d H:i:s');
			$this->db->where('id', $exists->id);
			return $this->db->update('attendance', $data);
		} else {
			$data['created_at'] = date('Y-m-d H:i:s');
			$data['updated_at'] = date('Y-m-d H:i:s');
			$data['isDeleted'] = '0';  // Set default isDeleted to 0
			return $this->db->insert('attendance', $data);
		}
	}


	

	// generate calendar
	public function generateCalendar()
	{
		date_default_timezone_set('Asia/Kolkata');
		$startDate = new DateTime('2026-01-01');
		$endDate   = new DateTime(); 
		
		while ($startDate <= $endDate) {
			$currentDateString = $startDate->format('Y-m-d');
			$exist = $this->db->get_where('calendar', ['calendar_date' => $currentDateString])->num_rows();

			if (!$exist) {
				$now = date('Y-m-d H:i:s');
				$this->db->insert('calendar', [
					'calendar_date' => $currentDateString,
					'day'           => $startDate->format('l'),
					'month'         => $startDate->format('F'),
					'year'          => $startDate->format('Y'),
					'created_at'    => $now,
					'updated_at'    => $now
				]);
			}
			$startDate->modify('+1 day');
		}

		$this->db->order_by('calendar_date', 'DESC');
		return $this->db->get('calendar')->result();
	}















	





	// ========================    Worker History    ===========================

    
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
	// public function addWorkerHistory($worker_id) {

	// 	// Fetch worker info including name and created_at
	// 	$this->db->select('name, created_at');
	// 	$this->db->where('id', $worker_id);
	// 	$worker = $this->db->get('workers')->row();

	// 	if (!$worker) return false;

	// 	$name = $worker->name;
	// 	$worker_created_date = date('Y-m-d', strtotime($worker->created_at));

	// 	// 2. Get date input
	// 	$work_start_date = $this->input->post('work_start_date');
	// 	$work_end_date = $this->input->post('work_end_date');

	// 	// We cannot have worker history row, start date before worker was created
	// 	if ($work_start_date < $worker_created_date) {
	// 		return ['Error' => 'Worker History cannot be before worker creation date ' . $worker_created_date . ''];
	// 	}

	// 	// 4. Validation: No future dates
	// 	$today = date('Y-m-d');
	// 	if ($work_start_date > $today) return false;
	// 	if (!empty($work_end_date) && $work_end_date > $today && $work_end_date != '0000-00-00 00:00:00') {
	// 		return false;
	// 	}

	// 	$now = date('Y-m-d H:i:s');
	// 	$default_date = "0000-00-00 00:00:00";

	// 	$insertData = [
	// 		'worker_id'         => $worker_id,
	// 		'name'              => $name,
	// 		'work_start_date'   => $work_start_date,
	// 		'work_end_date'     => empty($work_end_date) ? $default_date : $work_end_date,
	// 		'isDeleted'         => "0",
	// 		'createdAt'         => $now,
	// 		'updatedAt'         => $now
	// 	];

	// 	$this->db->insert('worker_history', $insertData);
	// 	$insertData['id'] = $this->db->insert_id();

	// 	$this->syncAttendanceWithWorkerHistory($worker_id);
	// 	return $insertData;
	// }
	public function addWorkerHistory($worker_id) {
    // Debug logging
    log_message('error', '=== addWorkerHistory START ===');
    log_message('error', 'Worker ID: ' . $worker_id);
    
    // Fetch worker info
    $this->db->select('name, created_at');
    $this->db->where('id', $worker_id);
    $worker = $this->db->get('workers')->row();

    if (!$worker) {
        log_message('error', 'Worker not found: ' . $worker_id);
        return ['Error' => 'Worker not found'];
    }

    $name = $worker->name;
    $worker_created_date = date('Y-m-d', strtotime($worker->created_at));
    log_message('error', 'Worker created date: ' . $worker_created_date);

    // Get date input
    $work_start_date = $this->input->post('work_start_date');
    $work_end_date = $this->input->post('work_end_date');
    
    log_message('error', 'Raw POST - start: ' . $work_start_date . ' | end: ' . $work_end_date);

    // Validate date formats
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $work_start_date)) {
        log_message('error', 'Invalid start date format: ' . $work_start_date);
        return ['Error' => 'Invalid start date format. Use YYYY-MM-DD'];
    }
    
    if (!empty($work_end_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $work_end_date)) {
        log_message('error', 'Invalid end date format: ' . $work_end_date);
        return ['Error' => 'Invalid end date format. Use YYYY-MM-DD'];
    }

    // Validation: Start date cannot be before worker creation
    if ($work_start_date < $worker_created_date) {
        log_message('error', 'Start date before worker creation: ' . $work_start_date . ' < ' . $worker_created_date);
        return ['Error' => 'Worker History cannot be before worker creation date ' . $worker_created_date];
    }

    // Validation: No future dates
    $today = date('Y-m-d');
    if ($work_start_date > $today) {
        log_message('error', 'Start date is in future: ' . $work_start_date);
        return ['Error' => 'Start date cannot be in the future'];
    }
    
    if (!empty($work_end_date) && $work_end_date > $today) {
        log_message('error', 'End date is in future: ' . $work_end_date);
        return ['Error' => 'End date cannot be in the future'];
    }

    // Check for date overlap
    $hasOverlap = $this->checkDateOverlap($worker_id, $work_start_date, $work_end_date);
    if ($hasOverlap) {
        log_message('error', 'Date overlap detected');
        return ['Error' => 'Date range overlaps with existing history'];
    }

    $now = date('Y-m-d H:i:s');
    $default_date = "0000-00-00 00:00:00";

    $insertData = [
        'worker_id'       => $worker_id,
        'name'            => $name,
        'work_start_date' => $work_start_date,
        'work_end_date'   => empty($work_end_date) ? $default_date : $work_end_date,
        'isDeleted'       => "0",
        'createdAt'       => $now,
        'updatedAt'       => $now
    ];
    
    log_message('error', 'Insert data: ' . json_encode($insertData));

    if (!$this->db->insert('worker_history', $insertData)) {
        log_message('error', 'DB Insert failed: ' . $this->db->error()['message']);
        return ['Error' => 'Database error: ' . $this->db->error()['message']];
    }
    
    $insert_id = $this->db->insert_id();
    log_message('error', 'Insert ID: ' . $insert_id);
    $insertData['id'] = $insert_id;

    $this->syncAttendanceWithWorkerHistory($worker_id);
    log_message('error', '=== addWorkerHistory END ===');
    
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

		$this->db->where('id', $id)->update('worker_history', $data);

		// get worker id to sync
		$worker_id = $this->getWorkerIdFromHistory($id);
		if ($worker_id) {
			$this->syncAttendanceWithWorkerHistory($worker_id);
		}
		return true;

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
		$new_start_dt = strtotime(date('Y-m-d', strtotime($new_start)));

		// Handle open end date
		if (empty($new_end) || $new_end == '0000-00-00 00:00:00') {
			$new_end_dt = strtotime('9999-12-31');
		} else {
			$new_end_dt = strtotime(date('Y-m-d', strtotime($new_end)));
		}

		// loop through all records that are present in table of single worker
		foreach($existing as $record) {

			$existing_start = strtotime(date('Y-m-d', strtotime($record['work_start_date'])));
			$existing_end = $record['work_end_date'];

			if (empty($record['work_end_date']) || $record['work_end_date'] == '0000-00-00 00:00:00') {
				$existing_end = strtotime('9999-12-31');
			} else {
				$existing_end = strtotime(date('Y-m-d', strtotime($record['work_end_date'])));
			}

			if ($new_start_dt <= $existing_end && $new_end_dt >= $existing_start) {
				return true; // Overlap confirmed
			}
		}
		return false;
	}


	

	// Soft delete worker history
	public function deleteWorkerHistory($id)
	{
		$worker_id = $this->getWorkerIdFromHistory($id);

		// Soft delete the worker history record
		if ($worker_id) {
			$this->db->where('id', $id)->update('worker_history', [
				'isDeleted'			=> '1',
				'updatedAt'			=> date('Y-m-d H:i:s')
			]);

			// automatically mark attendance outside remaining history as isDeleted = 1
			$this->syncAttendanceWithWorkerHistory($worker_id);
			return true;
		}
		return false;
	}




	// SYNC Attendance with the worker hoistory table according to specific worker id
	public function syncAttendanceWithWorkerHistory($worker_id) {

		// 1. mark as delete for this specific worker
		$this->db->where('worker_id', $worker_id)->update('attendance',
		[
			'isDeleted'		=> '1',
			'updated_at'	=> date('Y-m-d H:i:s')
		]);

		$history = $this->db->where([
			'worker_id'		=> $worker_id,
			'isDeleted'		=> '0'
		])->get('worker_history')->result_array();

		foreach($history as $range) {
			$start = $range['work_start_date'];
			$end = ($range['work_end_date'] == '0000-00-00 00:00:00') ? date('Y-m-d') : $range['work_end_date'];

			// 2. select calendar_date for this specific range
			$this->db->select('id');
			$this->db->where("calendar_date BETWEEN '$start' AND '$end' ");
			$calendar_dates = $this->db->get('calendar')->result_array();
			$calendar_ids = array_column($calendar_dates, 'id');

			if(!empty($calendar_ids)) {
				// restore attendance record within date range
				$this->db->where('worker_id', $worker_id);
				$this->db->where_in('attendance_date', $calendar_ids);
				$this->db->update('attendance', [
					'isDeleted'			=> '0',
					'updated_at'		=> date('Y-m-d H:i:s')
				]);
			}
		}
		// 3. generate for new date that are missing earlier in the date range
		$this->manageWorkerAttendance($worker_id);
	}


    
}

// 1. check for all worker_history date range and if the attendance is outside of those date range delete them, 
// 2. when I edit the worker_history then the those deleted range should recover from 1 to 0, 
// 3. create date that are new entry