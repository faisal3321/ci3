<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background-color: #f8f9fa; }
        
        /* Header and Filter Layout */
        .header-wrapper { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }
        h1 { color: #333; margin: 0; font-size: 1.5rem; }
        .worker-title { color: #007bff; }
        
        /* Table Styling */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { border: 1px solid #eee; padding: 12px; text-align: left; }
        th { background-color: #ffffff; color: #666; font-weight: 600; text-transform: uppercase; font-size: 0.85em; }
        tr:hover { background-color: #fcfcfc; }
        
        /* Form Elements */
        select { padding: 6px; border-radius: 4px; border: 1px solid #ddd; cursor: pointer; background: #fff; width: 100%; }
        .sync-status { font-size: 0.85em; font-weight: bold; }

        /* Filter Pop-up Styles (Positioned Right) */
        .filter-container { position: relative; }
        .filter-trigger-btn { 
            padding: 10px 18px; cursor: pointer; display: flex; align-items: center; gap: 10px; 
            background: #007bff; color: white; border: none; border-radius: 6px; font-weight: 500;
        }
        
        #dateFilterPopup { 
            display: none; position: absolute; top: 50px; right: 0; background: white; 
            padding: 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
            z-index: 1000; border: 1px solid #ddd; width: 280px; 
        }
        .filter-group { margin-bottom: 15px; }
        .filter-group label { display: block; font-size: 12px; color: #888; margin-bottom: 5px; }
        .filter-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        
        .filter-actions { display: flex; gap: 10px; }
        .btn-apply { flex: 2; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-reset { flex: 1; padding: 10px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="header-wrapper">
        <h1 id="pageTitle">Attendance Log: <span class="worker-title" id="displayWorkerName">Loading...</span></h1>

        <div class="filter-container">
            <button class="filter-trigger-btn" onclick="toggleFilter(event)">
                <span>📅</span> Filter by Date
            </button>

            <div id="dateFilterPopup">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" id="startDate">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" id="endDate">
                </div>
                <div class="filter-actions">
                    <button class="btn-apply" onclick="applyFilter()">Apply</button>
                    <button class="btn-reset" onclick="resetFilter()">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <div id="result">
        <p>Loading data...</p>
    </div>

    <script>
        const wId = "<?php echo $workerId; ?>";

        function toggleFilter(e) {
            e.stopPropagation();
            const popup = document.getElementById('dateFilterPopup');
            popup.style.display = (popup.style.display === 'block') ? 'none' : 'block';
        }

        function applyFilter() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            document.getElementById('dateFilterPopup').style.display = 'none';
            fetchData(wId, start, end);
        }

        function resetFilter() {
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('dateFilterPopup').style.display = 'none';
            fetchData(wId);
        }

        document.addEventListener('click', function(e) {
            const popup = document.getElementById('dateFilterPopup');
            if (popup && !popup.contains(e.target)) {
                popup.style.display = 'none';
            }
        });

        async function fetchData(workerId, start = '', end = '') {
            let url = `<?php echo base_url('api/manageattendance'); ?>?workerId=${workerId}`;
            if (start) url += `&startDate=${start}`;
            if (end) url += `&endDate=${end}`;

            try {
                const response = await fetch(url);
                const result = await response.json();
                const data = result.data;

                if (!data || data.length === 0) {
                    document.getElementById('result').innerHTML = '<p>No records found.</p>';
                    return;
                }

                const workerName = data[0].name || "Worker"; 
                document.getElementById('displayWorkerName').innerText = `${workerName} (id: ${wId})`;

                let tableHTML = `
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Worker (ID)</th>
                                <th>Attendance Date</th>
                                <th>Admin Side Attendance</th>
                                <th>Customer Side Attendance</th>
                                <th>Sync Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach((obj) => {
                    const options = { 0: 'N/A', 1: 'Present', 2: 'Absent', 3: 'Half-Day', 4: 'Holiday' };

                    const buildSelect = (currentVal, type, calId) => {
                        let sel = `<select onchange="updateLive(${wId}, ${obj.calendar_id}, this)" data-type="${type}">`;
                        for (const [val, label] of Object.entries(options)) {
                            sel += `<option value="${val}" ${currentVal == val ? 'selected' : ''}>${label}</option>`;
                        }
                        return sel + `</select>`;
                    };

                    tableHTML += `
                                <tr id="row-${obj.calendar_id}">
                                    <td>${obj.id || '---'}</td>
                                    <td>${workerName} <br> ( id: ${wId} )</td>
                                    <td><strong>${obj.attendance_date}</strong></td> 
                                    <td>${buildSelect(obj.worker_attendance, 'worker', obj.calendar_id)}</td>
                                    <td>${buildSelect(obj.customer_side_attendance, 'customer', obj.calendar_id)}</td>
                                    <td class="sync-indicator">
                                        <span style="color: #28a745;" class="sync-status">✔ Synced</span>
                                    </td>
                                </tr>`;
                });

                tableHTML += `</tbody></table>`;
                document.getElementById('result').innerHTML = tableHTML;

            } catch (error) {
                document.getElementById('result').innerHTML = '<p style="color:red;">Error fetching data.</p>';
            }
        }

        async function updateLive(workerId, calendarId, element) {
            const row = document.getElementById(`row-${calendarId}`);
            const indicator = row.querySelector('.sync-indicator');
            
            // Define values from the row
            const workerVal = row.querySelector('select[data-type="worker"]').value;
            const customerVal = row.querySelector('select[data-type="customer"]').value;

            indicator.innerHTML = '<span style="color: #007bff;">Saving...</span>';

            const formData = new URLSearchParams();
            formData.append('worker_id', workerId);
            formData.append('attendance_date', calendarId); // This is the ID for the DB
            formData.append('worker_attendance', workerVal);
            formData.append('customer_side_attendance', customerVal);

            try {
                const response = await fetch('<?php echo base_url("api/submitAttendance"); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                const result = await response.json();
                indicator.innerHTML = result.status ? 
                    '<span style="color: #28a745;" class="sync-status">✔ Saved</span>' : 
                    '<span style="color: #dc3545;" class="sync-status">✘ Failed</span>';
            } catch (error) {
                indicator.innerHTML = '<span style="color: #dc3545;" class="sync-status">✘ Error</span>';
            }
        }

        fetchData(wId);
    </script>
</body>
</html>