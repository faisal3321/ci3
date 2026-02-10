<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Worker</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f7f6; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .form-container { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
        }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        input, select, textarea { 
            width: 100%; 
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; /* Important for padding */
        }
        textarea { height: 80px; resize: vertical; }
        button { 
            width: 100%; 
            padding: 12px; 
            background-color: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold;
            margin-top: 10px;
        }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>

    <div class="form-container">
        <h1>Add Worker</h1>
        <form id="addWorkerForm">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="number" name="age" placeholder="Age" required>
            <select name="gender">
                <option value="" disabled selected>Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="others">Others</option>
            </select>
            <input type="number" name="phone" placeholder="Phone Number" required>
            <textarea name="address" placeholder="Residential Address"></textarea>

            <button type="submit">Save Worker</button>
        </form>
    </div>

    <script>
        document.getElementById('addWorkerForm').onsubmit = async (e) => {
            e.preventDefault();

            const url = '<?php echo base_url("api/createWorker"); ?>';

            const formData = new FormData(e.target);
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                alert(result.message);

                if (result.status) {
                    window.location.href = '<?php echo base_url("worker"); ?>';
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Could not connect to the server.");
            }
        };
    </script>
</body>
</html>