<head>
    <title>Attendance Log</title>
    <style>
        body {font-family: sans-serif; padding: 20px;}

        .header-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse }
        th, td {padding: 12px 8px; text-align: left; border: 1px solid #ddd;}
        th {background: #f2f2f2f2}
    </style>
</head>
<body>

    <div class="header-container">
        <h1>Attendance Log</h1>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Worker ID</th>
                <th>Name</th>
                <th>Attendance Date</th>
                <th>Worker Side Attendance</th>
                <th>Customer Side Attendance</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody id="attendance-log-body">
            <tr>
                <td colspan="7" style="text-align:center;">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        $(document).ready(function() {

            const workerId = "<?php echo $workerId; ?>"; 

            function loadAttendance() {
                $.ajax({
                    url: "<?php echo base_url('api/manageattendance'); ?>",
                    type: 'GET',
                    data: { workerId: workerId },
                    success: function(response) {

                        if (!response.status || !response.data.length) {
                            $('#attendance-log-body').html(
                                '<tr><td colspan="7" style="text-align:center;">No Records Found</td></tr>'
                            );
                            return;
                        }

                        let rows = '';

                        response.data.forEach(item => {

                            const workerSelected = (val) => item.worker_attendance == val ? 'selected' : '';
                            const customerSelected = (val) => item.customer_side_attendance == val ? 'selected' : '';

                            const rowStyle = item.is_weekend == 1 ? 
                                'style="background-color: #fff5f5;"' : '';

                            const dateLabel = item.is_weekend == 1 ? 
                                `<b>${item.attendance_date} (Sunday)</b>` : 
                                item.attendance_date;

                            rows += `
                                <tr data-calendar-id="${item.calendar_id}" ${rowStyle}>
                                    <td>${item.id ? item.id : '<span style="color:gray;font-style:italic;">Auto</span>'}</td>
                                    <td>${item.worker_id}</td>
                                    <td>${item.name}</td>
                                    <td>${dateLabel}</td>

                                    <td>
                                        <select class="worker-att" style="${getSelectStyle(item.worker_attendance)}">
                                            <option value="0" ${workerSelected(0)}>N/A</option>
                                            <option value="1" ${workerSelected(1)}>Present</option>
                                            <option value="2" ${workerSelected(2)}>Absent</option>
                                            <option value="3" ${workerSelected(3)}>Half Day</option>
                                            <option value="4" ${workerSelected(4)}>Holiday</option>
                                        </select>
                                    </td>

                                    <td>
                                        <select class="customer-att" style="${getSelectStyle(item.customer_side_attendance)}">
                                            <option value="0" ${customerSelected(0)}>N/A</option>
                                            <option value="1" ${customerSelected(1)}>Present</option>
                                            <option value="2" ${customerSelected(2)}>Absent</option>
                                            <option value="3" ${customerSelected(3)}>Half Day</option>
                                            <option value="4" ${customerSelected(4)}>Holiday</option>
                                        </select>
                                    </td>

                                    <td>
                                        <button class="save-btn" 
                                            style="background:#4CAF50;color:white;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;">
                                            Save
                                        </button>
                                        <span class="sync-mark" style="margin-left:8px;display:none;">✔</span>
                                    </td>
                                </tr>
                            `;
                        });

                        $('#attendance-log-body').html(rows);
                    },
                    error: function(err) {
                        console.log("Load Error:", err);
                    }
                });
            }

            // Returns a CSS style string with background color based on attendance value
            function getSelectStyle(val) {
                switch (Number(val)) {
                    case 0: return "background-color: #cac5c5; font-weight: bold;";      // N/A (gray)
                    case 1: return "background-color: #47b862; font-weight: bold;";      // Present (green)
                    case 2: return "background-color: #f53142; font-weight: bold;";      // Absent (red)
                    case 3: return "background-color: #e4b722; font-weight: bold;";      // Half Day (yellow)
                    case 4: return "background-color: #1056a0; font-weight: bold;";      // Holiday (blue)
                    default: return "";
                }
            }

            loadAttendance();

            // Save Attendance
            $(document).on('click', '.save-btn', function() {

                const row = $(this).closest('tr');
                const submitBtn = $(this);
                const syncMark = row.find('.sync-mark');

                const payload = {
                    worker_id: workerId,
                    attendance_date: row.data('calendar-id'),
                    worker_attendance: row.find('.worker-att').val(),
                    customer_side_attendance: row.find('.customer-att').val()
                };

                submitBtn.text('Saving...').prop('disabled', true);

                $.ajax({
                    url: "<?php echo base_url('api/submitAttendance'); ?>",
                    type: 'POST',
                    data: payload,
                    success: function(response) {

                        submitBtn.text('Save').prop('disabled', false);

                        if (response.status) {
                            syncMark.show().fadeOut(2000);

                            // Update both dropdown colors after successful save
                            row.find('.worker-att')
                                .attr('style', getSelectStyle(payload.worker_attendance));
                            row.find('.customer-att')
                                .attr('style', getSelectStyle(payload.customer_side_attendance));
                        } else {
                            alert("Failed to save attendance.");
                        }
                    },
                    error: function(err) {
                        console.log("Save Error:", err);
                        submitBtn.text('Save').prop('disabled', false);
                        alert("Server Error");
                    }
                });
            });

        });
    </script>

</body>