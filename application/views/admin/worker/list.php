<!DOCTYPE html>
<html>
<head>
    <title>Worker List</title>
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

    <h1>Hello worker list...</h1>
    
    <a href='<?php echo base_url("worker/add"); ?>' class="btn-add">Add Worker</a>

    <div id="result">
        <p>Loading data...</p>
    </div>

    <script>
        const url = '<?php echo base_url("api/workerlist"); ?>';

        async function fetchData() {
            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const result = await response.json();
                

                data = result.data;

                
                // Create Table Header with our 6 fields
                let tableHTML = `
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach((obj,ind)=>{
                    tableHTML += `<tr>
                                    <td>${obj.id}</td>
                                    <td>${obj.name}</td>
                                    <td>${obj.age}</td>
                                    <td>${obj.phone}</td>
                                    <td>${obj.gender}</td>
                                    <td>${obj.address}</td>
                                    <td>
                                        <a href='<?php echo base_url("worker/manage/"); ?>${obj.id}' target="_blank">Manage</a>
                                        <a href="" target="_blank">Edit</a>
                                        <a href="" target="_blank">Delete</a>
                                    </td>
                                </tr>`;  
                })

                tableHTML += `</tbody>
                            </table>`;             

                
                document.getElementById('result').innerHTML = tableHTML;

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = '<p style="color:red;">Error fetching data.</p>';
            }
        }

        fetchData();
    </script>
</body>
</html>