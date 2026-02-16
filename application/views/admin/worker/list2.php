<!DOCTYPE html>
<html>
<head>
    <title>Worker List</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">


    <style>
        body { font-family: sans-serif; padding: 20px; }
        .btn-add { padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 10px; }
    </style>
</head>
<body>

    <h1>Hello worker list 2</h1>

    <div id="table-error"></div>
    
    <a href="<?php echo base_url('worker/add'); ?>" class="btn-add">Add Worker</a>

    <table id="workerTable" class="display" style="width:100%">
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
        <tbody></tbody>
    </table>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <!-- Required for Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <!-- Required for PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- Js ApiError -->
    <script src="<?php echo base_url('assets/js/utils.js'); ?>"></script>


<script>
    const url = '<?php echo base_url("api/workerlist1"); ?>';

    $(document).ready(function() {

        $.fn.dataTable.ext.errMode = 'none';

        $('#workerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: url,
                type: 'POST',
                dataSrc: 'data', // api return data       
                error: (xhr) => ApiError.handle(xhr, 'Quota reached. Please refresh the page in a few moments.')        
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'age' },
                { data: 'phone' },
                { data: 'gender' },
                { data: 'address' },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        return `
                            <a href="<?php echo base_url('worker/manage/'); ?>${data}" target="_blank">📋</a><br>
                            <a href="<?php echo base_url('worker/add/'); ?>${data}">✏️</a><br>
                            <a href="javascript:void(0);" onclick="deleteWorker(${data})" style="color:red;">🗑️</a>
                        `;
                    }
                }
            ],

            // button for download or export data.
            dom: 'Bfrtip',

            buttons: [
                {
                    extend: 'csv',
                    text: 'Download CSV',
                    exportOptions: {
                        columns: [0,1,2,3,4,5] // exclude Action column
                    }
                },
                {
                    extend: 'excel',
                    text: 'Download Excel',
                    exportOptions: {
                        columns: [0,1,2,3,4,5]
                    }
                },
                {
                    extend: 'pdf',
                    text: 'Download PDF',
                    exportOptions: {
                        columns: [0,1,2,3,4,5]
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    exportOptions: {
                        columns: [0,1,2,3,4,5]
                    }
                }
            ],

            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            responsive: true
        });

    });

    async function deleteWorker(id) {
        if (!confirm('Are you sure you want to delete this worker?')) return;

        const deleteUrl = `<?php echo base_url('api/deleteWorker/'); ?>${id}`;

        try {
            const response = await fetch(deleteUrl, { method: 'DELETE' });
            const result = await response.json();

            if (result.status) {
                alert(result.message);
                
                // Reload table without refreshing page
                $('#workerTable').DataTable().ajax.reload();
            } else {
                alert(result.message);
            }

        } catch (error) {
            alert('Something went wrong');
        }
    }
</script>


</body>
</html>

































