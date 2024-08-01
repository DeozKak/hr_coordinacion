document.addEventListener('DOMContentLoaded', () => {

tabla = document.querySelector('#tabla_programacion');

headers = ['CONTRATO','TIPO DE OBRA','FECHA','CELULAR','NOMBRE USUARIO','ORDEN DE TRABAJO','DIRECCION','BARRIO','CIUDAD',
    'ACTIVA','SUSPENSION','CATEGORIA','FECHA AGENDAMIENTO','OBSERVACIONES','PORQUE SE PROGRAMO','TECNICO','HORA INICIO','HORA FINAL'
]
H_tabla = new Handsontable(tabla, {
    readOnly: true,
    colHeaders: headers,
    rowHeaders: true,
    filters: true,
    height: '300px',
    licenseKey: 'non-commercial-and-evaluation',
});


});