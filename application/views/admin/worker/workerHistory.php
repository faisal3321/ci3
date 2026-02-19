<head>
    <title>worker history</title>

    <style>
        body { font-family: sans-serif; padding: 20px; }
        .btn-add { padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>

<body>
    <h1>Worker History Page</h1>

    <table border="1">
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
                <td colspan="5">Loading...</td> <!-- Changed colspan to 5 -->
            </tr>
        </tbody>
    </table>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Extract worker_id from URL
            var pathArray = window.location.pathname.split('/');
            var worker_id = pathArray[pathArray.length - 1]; // Gets "30" from the URL
            
            loadWorkerHistory(worker_id);
        });

        function loadWorkerHistory(worker_id) {
            $.ajax({
                url: '<?php echo base_url("api/workerHistory"); ?>',
                type: 'GET',
                data: { worker_id: worker_id }, // Send worker_id from URL
                dataType: 'json',
                success: function(response) {
                    if(response.status) {
                        let data = response.data;
                        let html = '';
                        
                        if(Array.isArray(data)) {
                            data.forEach(function(item) {
                                html += `
                                    <tr id="row-${item.id}">
                                        <td>${item.id || '---'}</td>
                                        <td>${item.worker_id || '---'}</td>
                                        <td>${item.work_start_date || '---'}</td>
                                        <td>${item.work_end_date || '---'}</td>
                                        <td>
                                            <button onclick="editWorker(${item.id})">Edit</button>
                                        </td>
                                    </tr>
                                `;
                            });
                        } else {
                            html += `
                                <tr id="row-${data.id}">
                                    <td>${data.id || '---'}</td>
                                    <td>${data.worker_id || '---'}</td>
                                    <td>${data.work_start_date || '---'}</td>
                                    <td>${data.work_end_date || '---'}</td>
                                    <td>
                                        <button onclick="editWorker(${data.id})">Edit</button>
                                    </td>
                                </tr>
                            `;
                        }
                        
                        $('#worker-table-body').html(html);
                    } else {
                        $('#worker-table-body').html('<tr><td colspan="5">No data found</td></tr>');
                    }
                },
                error: function() {
                    $('#worker-table-body').html('<tr><td colspan="5">Error loading data</td></tr>');
                }
            });
        }
        
        function editWorker(id) {
            alert('Edit worker: ' + id); // Fixed alert
        }
    </script>
</body>