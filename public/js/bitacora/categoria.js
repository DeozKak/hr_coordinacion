let hot;
document.addEventListener('DOMContentLoaded', function() {

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const tabla = document.getElementById('table');
    if(nuevoArray.length === 0){
        const mensaje = document.getElementById('message');
        mensaje.classList.add('show');
        return;
    }

    hot = new Handsontable(tabla, {
        colHeaders: [
            'id','CC OPERARIO','MUNICIPIO','FECHA','ACTA','TIPO TRABAJO',
            'CONTRATO','ORDEN','ORDEN EXTERNA','CATEGORIA','RESULTADO'
        ],
        data: nuevoArray,
        height: '350px',
        licenseKey: 'non-commercial-and-evaluation',
        columns: [
            {data: 'id', readOnly: true},
            {data: 'CC_OPERARIO', readOnly: true},
            {data: 'MUNICIPIO', readOnly: true},
            {data: 'FECHA', readOnly: true},
            {data: 'No_ACTA', readOnly: true},
            {data: 'TIPO_TRABAJO', readOnly: true},
            {data: 'CONTRATO', readOnly: true},
            {data: 'ORDEN_TRABAJO', readOnly: true},
            {data: 'ORDEN_EXT', readOnly: true},
            {
                data: 'CATEGORIA',
                type: 'dropdown',
                source: ['','RESIDENCIAL', 'COMERCIAL']
            },
            {data: 'RESULTADO_CIERRE', readOnly: true}
        ],
        hiddenColumns: {
            columns: [0],
            indicators: false
        },
        afterChange: function(changes, source){
            // Solo atendemos cambios hechos por el usuario (no por loadData)
            if(source === 'edit' && changes){
                changes.forEach(function(change){
                    const [row, prop, oldValue, newValue] = change;
                    // Solo nos interesa la columna CATEGORIA
                    if(prop === 'CATEGORIA' && oldValue !== newValue){
                        const rowData = hot.getSourceDataAtRow(row); // Usar getSourceDataAtRow para acceder a id aunque esté oculta
                        const id = rowData['id'];
                        if(newValue === ''){return;}
                        EnviarCambios(id, newValue, row);
                    }
                });
            }
        }
    })
});

function EnviarCambios(id, categoria, row){
    const csrfToken = document.getElementById('token').value;
    const url = document.getElementById('url').value;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            categoria: categoria
        })
    })
        .then(response => response.json())
        .then(data => {
            console.log(data);
            if(data.success){
                // Bloquear la celda para que no se vuelva a editar
              //  hot.setCellMeta(row, 9, 'readOnly', true); // 9 es el índice de la columna CATEGORIA
                hot.render(); // Refresca la tabla para aplicar el readOnly
            //    Swal.fire('¡Éxito!', 'Categoría actualizada con éxito.', 'success');
            }else{
                // Si falló, borrar valor y mostrar mensaje
                hot.setDataAtCell(row, 9, ''); // Borra la categoría seleccionada
                Swal.fire('Error', data.message || 'No se pudo actualizar la categoría.', 'error');
            }
        })
        .catch(() => {
            // Si el AJAX falla, también borramos el valor y mostramos error
            hot.setDataAtCell(row, 9, '');
            Swal.fire('Error', 'Error en la comunicación con el servidor.', 'error');
        });
}
