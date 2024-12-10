document.addEventListener('DOMContentLoaded', function () {

    $('#semanas').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "order": [[1, 'desc']] //  Ordena por la segunda columna (índice 1) en orden ascendente ('asc')
    });


    /* const div_table = document.getElementById('table');

    let hot = new Handsontable(div_table, {
        data: [],
        rowHeaders: true,
        colHeaders: true,
        columns: [
            { data: 'id', readOnly: true },
            { data: 'fecha' },
            { data: 'hora' },
            { data: 'observaciones' },
            { data: 'tecnico' },
            { data: 'celular' },
        ],
        dropdownMenu: true,
        filters: true,
        licenseKey: 'non-commercial-and-evaluation',
        contextMenu: true,
        manualRowMove: true,
        manualColumnMove: true,
        manualRowResize: true,
        manualColumnResize: true,
        rowHeaders: true,
        colHeaders: true,
    }); */

});