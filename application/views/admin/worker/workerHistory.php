<head>
    <title>worker history</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        
        /* Flexbox wrapper to align Heading and Button */
        .header-container { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
        }

        .btn-add { 
            padding: 10px 20px; 
            background: #2f9c48; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            font-weight: bold;
        }
        .btn-add:hover { background: #0a691a; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f2f2f2; }

        /* Simple Modal Styling or popup thing */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 20px; width: 350px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>

<body>


    <div class="header-container">
        <h1>Worker History Page</h1>
        <a class="btn-add" onclick="openModal()">+ Add Worker History</a>
    </div>


    <!-- modal for date popup when add worker history -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Worker History</h3>
            <form id="addHistoryForm">
                <input type="hidden" id="edit_record_worker_history" value=""/>
                <div class="form-group">
                    <label> Work Start Date (Required)</label>
                    <input type="date" id="work_start_date" required/>
                </div>
                <div class="form-group">
                    <label> Work End Date (when work closed)</label>
                    <input type="date" id="work_end_date" />
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeModal()" style="padding: 10px; margin-right: 5px;">Cancel</button>
                    <button type="submit" class="btn-add" id="submitBtn">Save History</button>
                </div>
            </form>
        </div>
    </div>


    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Worker ID</th>
                <th>Name</th>
                <th>Work Start Date</th>
                <th>Work End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="worker-table-body">
            <tr>
                <td colspan="6" style="text-align:center;">Loading...</td>
            </tr>
        </tbody>
    </table>



    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>



    <script>
        let worker_id = "<?php echo $this->uri->segment(3); ?>";

        $(document).ready(function() {  

            if (worker_id) {
                loadWorkerHistory(worker_id);
            } else {
                $('#worker-table-body').html('<tr><td colspan="6">Invalid Worker ID</td></tr>');
            }

            // form submit event
            $('#addHistoryForm').on('submit', function(e) {
                e.preventDefault();

                let history_id = $('#edit_record_worker_history').val();

                if(history_id) {
                    updateWorkerHistory();
                } else {
                    saveWorkerHistory();
                }
            });
        });



        function loadWorkerHistory(worker_id) {
            $.ajax({
                url: '<?php echo base_url("api/workerHistory/"); ?>',
                type: 'GET',
                data: { worker_id: worker_id },
                dataType: 'json',
                success: function(response) {
                    if(response.status) {
                        let data = response.data;
                        let html = '';
                        
                        // Ensure we are dealing with an array
                        let items = Array.isArray(data) ? data : [data];

                        items.forEach(function(item) {
                            html += `
                                <tr id="row-${item.id}">
                                    <td>${item.id || '---'}</td>
                                    <td>${item.worker_id || '---'}</td>
                                    <td>${item.name || '---'}</td>
                                    <td>${item.work_start_date || '---'}</td>
                                    <td>${item.work_end_date || '---'}</td>
                                    <td>
                                        <button onclick="editWorkerHistory(${item.id})">Edit</button>
                                        <button onclick="deleteWorkerHistory(${item.id})">Delete</button>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        $('#worker-table-body').html(html);
                    } else {
                        $('#worker-table-body').html('<tr><td colspan="6" style="text-align:center;">No data found</td></tr>');
                    }
                },
                error: function() {
                    $('#worker-table-body').html('<tr><td colspan="6" style="text-align:center;">No records found...</td></tr>');
                }
            });
        }
        


        // add new record in worker history table 
        // suppose worker has work from 2 feb - 8 feb , then this will be saved in one row and let say worker has work again from 11 feb - 18 feb, then this will be saved in another row and it will continue lika this
        function saveWorkerHistory() {
            let start = $('#work_start_date').val();
            let end = $('#work_end_date').val();

            $.ajax({
                url: '<?php echo base_url('api/addWorkerHistory'); ?>',
                type: 'POST',
                data: {
                    worker_id: worker_id,
                    work_start_date: start,
                    work_end_date: end
                },
                dataType: 'json',

            success: function(response) {
                if(response.status) {
                    alert(response.message);
                    closeModal();
                    $('#addHistoryForm')[0].reset(); // clear form
                    loadWorkerHistory(worker_id); // refresh table
                } else {
                    alert('Error : ' +  response.message);
                }
            }
            })
        }



        // Modal Controls
        function closeModal() { 
            $('#historyModal').fadeOut(); 
        }
        

        
        function openModal() {
            $('#modalTitle').text('Add New Worker History');
            $('#submitBtn').text('Save History');

            $('#edit_record_worker_history').val('');  // refresh the ID
            $('#addHistoryForm')[0].reset();
            $('#historyModal').fadeIn();
        }



        // Edit worker history table 
        function editWorkerHistory(id) {
            $('#modalTitle').text('Edit Worker History');
            $('#submitBtn').text('Update History');
            $('#edit_record_worker_history').val(id); // set the id in hidden input

            // find dates from row 
            let row = $('#row-' + id);
            let startDate = row.find('td:eq(3)').text().trim();
            let endDate = row.find('td:eq(4)').text().trim();

            // Extract only the date part (YYYY-MM-DD) if it's a datetime string
            if (startDate && startDate.includes(' ')) {
                startDate = startDate.split(' ')[0]; // Gets only the date part
            }
            if (endDate && endDate.includes(' ') && endDate !== '---') {
                endDate = endDate.split(' ')[0]; // Gets only the date part
            }

            $('#work_start_date').val(startDate);
            $('#work_end_date').val(endDate === '---' ? '' : endDate);
            
            $('#historyModal').fadeIn(); // Open the modal
        }



        // Update worker history - 
        function updateWorkerHistory() {
            let history_id = $('#edit_record_worker_history').val();
            let start_date = $('#work_start_date').val();
            let end_date = $('#work_end_date').val();

            $.ajax({
                url: '<?php echo base_url("api/editWorkerHistory/"); ?>' + history_id + '/' + start_date + '/' + end_date,
                type: "POST",
                dataType: 'json',
                success: function(response) {
                    if(response.status) {
                        alert(response.message);
                        closeModal();
                        loadWorkerHistory(worker_id);
                    }
                }
            });
        }



        function deleteWorkerHistory(id) {
            if(confirm('Are you sure you want to delete this record ?')) {
                $.ajax({
                    url: '<?php echo base_url("api/deleteWorkerHistory/"); ?>' + id,  
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if(response.status) {
                            alert(response.message);
                            loadWorkerHistory(worker_id); // This will refresh and hide the deleted record
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error deleting record: ' + error);
                    }
                });
            }
        }



    </script>


</body>