<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; color: #333; }
        tr:hover { background-color: #f1f1f1; }
        /* Style for our new dropdowns */
        select { padding: 5px; border-radius: 4px; border: 1px solid #ccc; cursor: pointer; }
        .sync-status { font-size: 0.85em; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Worker Attendance Log</h1>

    <div style="margin-bottom: 20px; background: #f4f4f4; padding: 15px; border-radius: 8px;">
        <label>Start Date:</label>
        <input type="date" id="startDate">
        
        <label>End Date:</label>
        <input type="date" id="endDate">
        
        <button onclick="applyFilter()" style="padding: 8px 15px; cursor: pointer;">Apply Filter</button>
        <button onclick="fetchData(wId)" style="padding: 8px 15px;">Reset</button>
    </div>

    <div id="result">
        <p>Loading data...</p>
    </div>

    <script>
        const wId = "<?php echo $workerId; ?>";

        function applyFilter() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            fetchData(wId, start, end);
        }

        async function fetchData(workerId, start = '', end = '') {
            let url = `<?php echo base_url('api/manageattendance'); ?>?workerId=${workerId}`;
            if (start) url += `&startDate=${start}`;
            if (end) url += `&endDate=${end}`;

            try {
                document.getElementById('result').innerHTML = '<p>Loading data...</p>';
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {'Content-Type': 'application/json'}
                });

                if (!response.ok) throw new Error('Something went wrong');

                const result = await response.json();
                const data = result.data;

                if (!data || data.length === 0) {
                    document.getElementById('result').innerHTML = '<p>No attendance records found for this range.</p>';
                    return;
                }

                let tableHTML = `
                    <table>
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Worker Id</th>
                                <th>Date</th>
                                <th>Admin Side Attendance</th>
                                <th>Customer Side Attendance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach((obj) => {
                    const options = { 
                        0: 'N/A', 
                        1: 'Present', 
                        2: 'Absent', 
                        3: 'Half-Day', 
                        4: 'Holiday' 
                    };

                    // Helper function to build the select dropdown
                    const buildSelect = (currentVal, type) => {
                        let sel = `<select onchange="updateLive(${workerId}, '${obj.attendance_date}', this)" data-type="${type}">`;
                        for (const [val, label] of Object.entries(options)) {
                            sel += `<option value="${val}" ${currentVal == val ? 'selected' : ''}>${label}</option>`;
                        }
                        return sel + `</select>`;
                    };

                    tableHTML += `
                        <tr id="row-${obj.attendance_date}">
                            <td>${obj.id || '---'}</td>
                            <td>${wId}</td> 
                            <td>${obj.attendance_date}</td>
                            <td>${buildSelect(obj.worker_attendance, 'worker')}</td>
                            <td>${buildSelect(obj.customer_side_attendance, 'customer')}</td>
                            <td class="sync-indicator">
                                ${obj.id ? 
                                    '<span style="color: green;" class="sync-status">✔ Synced</span>' : 
                                    '<span style="color: gray;" class="sync-status">○ New</span>'
                                }
                            </td>
                        </tr>`;
                });

                tableHTML += `</tbody></table>`;
                document.getElementById('result').innerHTML = tableHTML;

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = '<p style="color:red;">Error fetching data.</p>';
            }
        }

        // Replacement for markAttendance - this handles the Live Update
        async function updateLive(workerId, date, element) {
            const row = document.getElementById(`row-${date}`);
            const indicator = row.querySelector('.sync-indicator');
            
            // Get current values of both dropdowns in this row
            const workerVal = row.querySelector('select[data-type="worker"]').value;
            const customerVal = row.querySelector('select[data-type="customer"]').value;

            indicator.innerHTML = '<span style="color: blue;">Saving...</span>';

            const formData = new URLSearchParams();
            formData.append('worker_id', workerId);
            formData.append('attendance_date', date);
            formData.append('worker_attendance', workerVal);
            formData.append('customer_side_attendance', customerVal);

            try {
                const response = await fetch('<?php echo base_url("api/submitAttendance"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.status) {
                    indicator.innerHTML = '<span style="color: green;" class="sync-status">✔ Saved</span>';
                } else {
                    indicator.innerHTML = '<span style="color: red;" class="sync-status">✘ Failed</span>';
                    console.error("Save failed:", result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                indicator.innerHTML = '<span style="color: red;" class="sync-status">✘ Error</span>';
            }
        }

        // Initial Load
        fetchData(wId);
    </script>
</body>
</html>