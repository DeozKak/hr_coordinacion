document.addEventListener('DOMContentLoaded', () => {

    const detalles = document.querySelector('#detalles');

    const hot = new Handsontable(detalles, {
        readOnly: true,
        manualColumnMove: false,
        rowHeaders: true,
        colHeaders: true,
        height: '550px',
        data: [['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15'],
        ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15'],
        ],
        autoWrapRow: true,
        autoWrapCol: true,
        licenseKey: 'non-commercial-and-evaluation' // for non-commercial use only
    });
});