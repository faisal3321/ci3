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
        .btn-add { padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Worker Attendance Log</h1>

    <div id="result">
        <p>Loading data...</p>
    </div>
    

    <script>

        const wId = "<?php echo $workerId; ?>";

        async function fetchData(workerId) 
        {
            // Define the URL inside the function so workerId is available
            const url = `<?php echo base_url('api/manageattendance'); ?>?workerId=${workerId}`;

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {'Content-Type': 'application/json'}
                });

                
                if(!response.ok) throw new Error('Something went wrong');

                const result = await response.json();
                const data = result.data;

                if (!data || data.length === 0) {
                    document.getElementById('result').innerHTML = '<p>No attendance records found.</p>';
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach((obj) => {
                    tableHTML += `
                        <tr>
                            <td>${obj.id}</td>
                            <td>${obj.worker_id}</td>
                            <td>${obj.attendance_date}</td>
                            <td>${obj.worker_attendance}</td>
                            <td>${obj.customer_side_attendance}</td>
                        </tr>`;
                });

                tableHTML += `</tbody></table>`;
                document.getElementById('result').innerHTML = tableHTML;

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = '<p style="color:red;">Error fetching data.</p>';
            }
        }

        
        fetchData(wId);
    </script>
</body>
</html>


