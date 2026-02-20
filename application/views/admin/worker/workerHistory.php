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
            background: #28a745; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            font-weight: bold;
        }
        .btn-add:hover { background: #218838; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f2f2f2; }
    </style>
</head>

<body>

    <div class="header-container">
        <h1>Worker History Page</h1>
        <a href="#" class="btn-add" onclick="addWorkerHistory()">+ Add Worker History</a>
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
        });

        function loadWorkerHistory(worker_id) {
            $.ajax({
                url: '<?php echo base_url("api/workerHistory"); ?>',
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

        function addWorkerHistory() {
            alert('Opening Add History form for Worker ID: ' + worker_id);
        }
        
        function editWorkerHistory(id) {
            alert('Edit record ID: ' + id);
        }

        function deleteWorkerHistory(id) {
            alert('Delete record ID: ' + id);
        }

    </script>
</body>