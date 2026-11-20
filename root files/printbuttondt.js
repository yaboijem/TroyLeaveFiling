$(function() {
    alert('sa');
// $("#oldrequestprint").DataTable({
//     "responsive": true, "lengthChange": true, "autoWidth": false,"pageLength": 10,
//     "buttons": ["copy", "csv", "excel", "pdf", "print"]
// }).buttons().container().appendTo('#oldrequestprint_wrapper .col-md-6:eq(0)');

    var table = $('#Userprint').DataTable({
        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="fas fa-copy"></i> Copy',
                titleAttr: 'Copy'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                titleAttr: 'Excel'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fas fa-file-csv"></i> CSV',
                titleAttr: 'CSV'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                titleAttr: 'PDF'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print "></i> Print',
                titleAttr: 'Print'
            }
        ],columnDefs: [
        { orderable: false, targets: -1 }
        ], "pageLength": 5,"responsive": true,"autoWidth": false
    });
    table.buttons().container().appendTo($('#userprintbar'));
});