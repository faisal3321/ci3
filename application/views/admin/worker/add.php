<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Worker</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        input, select, textarea { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; resize: vertical; }
        button { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 10px; }
        button:disabled { background-color: #6c757d; cursor: not-allowed; }
        button:hover:not(:disabled) { background-color: #218838; }
    </style>
</head>
<body>

    <div class="form-container">
        <h1>Add Worker</h1>
        <form id="addWorkerForm">
            <input type="text" name="name" id="name" placeholder="Full Name" required>
            <input type="number" name="age" id="age" placeholder="Age" required>
            <select name="gender" id="gender">
                <option value="" disabled selected>Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="others">Others</option>
            </select>
            <input type="text" name="phone" id="phone" placeholder="Phone Number" required>
            <textarea name="address" id="address" placeholder="Residential Address"></textarea>

            <button type="submit" id="submitBtn">Save Worker</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            const editId = `<?php echo isset($workerId) ? $workerId : '' ; ?>`;

            // Prefill data using AJAX if editing
            if (editId) {
                document.title = 'Edit Worker';
                $('h1').text('Edit Worker');
                $('#submitBtn').text('Update Worker');

                $.ajax({
                    url: `<?php echo base_url('api/updateWorker'); ?>`,
                    type: 'GET',
                    data: { id: editId },
                    dataType: 'json',
                    success: function(result) {
                        if(result.status) {
                            const d = Array.isArray(result.data) ? result.data[0] : result.data;
                            $('#name').val(d.name);
                            $('#age').val(d.age);
                            $('#gender').val(d.gender);
                            $('#phone').val(d.phone);
                            $('#address').val(d.address);
                        }
                    },
                    error: function() {
                        console.error("Could not fetch worker data.");
                    }
                });
            }

            // Submit Form using AJAX
            $('#addWorkerForm').on('submit', function(e) {
                e.preventDefault();

                const btn = $('#submitBtn');
                let targetUrl = editId ? '<?php echo base_url("api/updateWorker"); ?>' : '<?php echo base_url("api/createWorker"); ?>';
                
                // Create FormData object
                let formData = new FormData(this);
                if (editId) {
                    formData.append('id', editId);
                }

                // UI feedback: Disable button to prevent double-submit
                btn.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: targetUrl,
                    type: 'POST',
                    data: formData,
                    processData: false, // Required for FormData
                    contentType: false, // Required for FormData
                    dataType: 'json',
                    success: function(result) {
                        alert(result.message);
                        if (result.status) {
                            window.location.href = '<?php echo base_url("worker"); ?>';
                        } else {
                            btn.prop('disabled', false).text(editId ? 'Update Worker' : 'Save Worker');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        alert("Could not connect to the server.");
                        btn.prop('disabled', false).text(editId ? 'Update Worker' : 'Save Worker');
                    }
                });
            });
        });
    </script>
</body>
</html>