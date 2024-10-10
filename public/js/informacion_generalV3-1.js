

document.addEventListener('DOMContentLoaded', () => {
    $('#cortes, #municipios, #sedes, #zonas, #devolucion').DataTable({
        paging: false,
        scrollCollapse: true,
        scrollY: '230px',
        lengthChange: false,
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "info": "Mostrando la página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });

/*     $('#municipios').DataTable({
        paging: false,
        scrollCollapse: true,
        scrollY: '230px',
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "info": "Mostrando la página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });

    $('#sedes').DataTable({
        scrollCollapse: true,
        scrollY: '230px',
        paging: false,
        searching: false,
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "info": "Mostrando la página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });
    $('#zonas').DataTable({
        scrollCollapse: true,
        scrollY: '230px',
        paging: false,
        searching: false,
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "info": "Mostrando la página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });

    $('#devolucion').DataTable({
        paging: false,
        scrollCollapse: true,
        scrollY: '230px',
        lengthChange: false,
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "info": "Mostrando la página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });
 */    const btnCrearSede = document.getElementById('btnCrearSede');
    const btnCrearCorte = document.getElementById('btnCrearCorte');
    const btnCrearMunicipio = document.getElementById('btnCrearMunicipio');
    const btnCrearCausal = document.getElementById('btnCrearCausal');


    btnCrearCorte.addEventListener('click', () => {
        CrearCorte();
    });

    btnCrearMunicipio.addEventListener('click', () => {
        CrearMunicipio();
    });

    btnCrearSede.addEventListener('click', () => {
        CrearSede();
    });

    btnCrearCausal.addEventListener('click', () => {
        CrearCausal();
    });
    //--------------------------------------------------------------------------------------------
    $('#cortes').on('click', '.btn-success[data-corte-id]', function () {
        const corteId = $(this).data('corte-id');
        editarCorte(corteId);

    });

    $('#cortes').on('click', '.btn-primary[data-corte-id]', function () {
        const corteId = $(this).data('corte-id');
        detallesCorte(corteId);

    });

    $('#cortes').on('click', '.btn-secondary[data-corte-id]', function () {
        const corteId = $(this).data('corte-id');
        Graficos(corteId);

    });

    $('#municipios').on('click', '.btn-success[data-municipio-id]', function () {
        const municipioId = $(this).data('municipio-id');
        editarMunicipio(municipioId);
    });

    $('#devolucion').on('click', '.btn-success[data-causal-id]', function () {
        const causalId = $(this).data('causal-id');
        editarCausal(causalId);
    });
});


function CrearCausal() {
    const campos = document.querySelectorAll('#CausalModal input');
    campos.forEach(campo => campo.value = '');
    $('#crearCausalModalLabel').text('Crear Causal');
    $('#crearCausal').text('Crear');
    $('#CausalModal').modal('show');
    /* Validaciones inputs cortes */

    ValidarFormularioCausales();

    const btnCrear = document.getElementById('crearCausal');

    btnCrear.addEventListener('click', validarFormulario);
    function validarFormulario() {
      
        // Obtener todos los campos del formulario
        const camposFormulario = document.querySelectorAll('#CausalModal input');

        // Validar si todos los campos están llenos
        const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
        if (todosLosCamposLlenos) {
            EnviarServidorStore("Causal");
            camposFormulario.forEach(campo => campo.value = '');
        } else {
            // Si algún campo está vacío, mostrar un mensaje de error
            alert('Por favor, complete todos los campos.');
        }
    }

}

function editarCausal(id) {

    $.ajax({
        url: 'cortes_producction/' + id + '/editCausal', // Ruta para obtener los datos del corte
        method: 'GET',
        success: function (response) {
            $('#crearCausalModalLabel').text('Guardar Cambios');
            $('#crearCausal').text('Guardar Cambios');
            $('#nombreCausal').val(response[0].nom_causal);
            $('#CausalModal').modal('show');
        }, error: function (xhr, status, error) {
            console.error("Error al obtener los datos del corte.");
        }
    });
    ValidarFormularioCausales();
    const btnCrear = document.getElementById('crearCausal');
    btnCrear.addEventListener('click', validarFormulario);
    function validarFormulario() {
        const camposFormulario = document.querySelectorAll('#CausalModal input');
        const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
        if (todosLosCamposLlenos) {
            EnviarServidorUpdate("Causal", id);
            camposFormulario.forEach(campo => campo.value = '');
        } else {
            alert('Por favor, complete todos los campos.');
        }
    }

}

function CrearCorte() {
    const campos = document.querySelectorAll('#CorteModal input');
    campos.forEach(campo => campo.value = '');
    $('#crearCorteModalLabel').text('Crear Corte');
    $('#crear').text('Crear');
    $('#CorteModal').modal('show');
    /* Validaciones inputs cortes */

    ValidarFormularioCortes();

    const btnCrear = document.getElementById('crear');
    btnCrear.addEventListener('click', validarFormulario);
    function validarFormulario() {
        // Obtener todos los campos del formulario
        const camposFormulario = document.querySelectorAll('#CorteModal input');

        // Validar si todos los campos están llenos
        const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
        if (todosLosCamposLlenos) {
            EnviarServidorStore("Corte");
/*             camposFormulario.forEach(campo => campo.value = '');
 */        } else {
            // Si algún campo está vacío, mostrar un mensaje de error
            alert('Por favor, complete todos los campos.');
        }
    }
}

function editarCorte(id) {

    $.ajax({
        url: 'cortes_producction/' + id + '/editCorte', // Ruta para obtener los datos del corte
        method: 'GET',
        success: function (response) {
            $('#crearCorteModalLabel').text('Editar Corte');
            $('#crear').text('Guardar Cambios');
            $('#nombre').val(response[0].nombre);
            $('#fecha_inicio').val(response[0].fecha_inicio);
            $('#fecha_fin').val(response[0].fecha_fin);
            $('#meta').val(response[0].meta);
            $('#dobles').val(response[0].dobles);
            $('#CorteModal').modal('show');
        }, error: function (xhr, status, error) {
            console.error("Error al obtener los datos del corte.");
        }
    });
    ValidarFormularioCortes();
    const btnCrear = document.getElementById('crear');
    btnCrear.addEventListener('click', validarFormulario);
    function validarFormulario() {
        const camposFormulario = document.querySelectorAll('#CorteModal input');
        const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
       console.log(todosLosCamposLlenos);
        if (todosLosCamposLlenos) {
            EnviarServidorUpdate("Corte", id);
           /*  camposFormulario.forEach(campo => campo.value = ''); */
        } else {
            alert('Por favor, complete todos los campos.');
        }
    }
}

function CrearMunicipio() {
    const campos = document.querySelectorAll('#MunicipioModal input , #MunicipioModal select');
    campos.forEach(campo => campo.value = '');
    $('#crearSedeModalLabel').text('ingresar Municipio');
    $('#crearMunicipio').text('Crear');
    $('#MunicipioModal').modal('show');

    ValidarFormularioMunicipios();

    const btnCrear = document.getElementById('crearMunicipio');

    btnCrear.addEventListener('click', validarFormulario);

    function validarFormulario() {
        const camposFormulario = document.querySelectorAll('#MunicipioModal input,#MunicipioModal select');
        const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
        if (todosLosCamposLlenos) {
            EnviarServidorStore("Municipio");
            camposFormulario.forEach(campo => campo.value = '');
        } else {
            alert('Por favor, complete todos los campos.');
        }
    }

}

function editarMunicipio(id) {
    $('#crearSedeModalLabel').text('Editar Municipio');
    $('#crearMunicipio').text('Guardar Cambios');
    $.ajax({
        url: 'cortes_producction/' + id + '/editMunicipio', // Ruta para obtener los datos del municipio
        method: 'GET',
        success: function (response) {
            $('#crearMunicipioModalLabel').text('Editar Municipio');
            $('#crear').text('Guardar Cambios');
            $('#nombreMunicipio').val(response[0].nombre);
            $('#sede').val(response[0].id_sede);
            $('#zona').val(response[0].id_zona);
            $('#MunicipioModal').modal('show');
        }, error: function (xhr, status, error) {
            console.error("Error al obtener los datos del municipio.");
        }
    });
    ValidarFormularioMunicipios();
    const btnCrear = document.getElementById('crearMunicipio');
    btnCrear.addEventListener('click', validarFormulario);
    function validarFormulario() {
        const camposFormulario = document.querySelectorAll('#MunicipioModal input,#MunicipioModal select');
        const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
        if (todosLosCamposLlenos) {
            EnviarServidorUpdate("Municipio", id);
            camposFormulario.forEach(campo => campo.value = '');
        } else {
            alert('Por favor, complete todos los campos.');
        }
    }


}

function CrearSede() {
    $('#SedeModal').modal('show');
    const inputNombre = document.getElementById('nombreSede');

    inputNombre.addEventListener('input', function () {

        if (this.value.length > 20) {
            this.value = this.value.slice(0, 20);
        }
    });

    const btnCrear = document.getElementById('crearSede');
    btnCrear.addEventListener('click', validarFormulario);

    function validarFormulario() {
        const camposFormulario = document.querySelectorAll('#SedeModal input');
        const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
        if (todosLosCamposLlenos) {
            EnviarServidorStore("Sede");
            camposFormulario.forEach(campo => campo.value = '');
        } else {
            alert('Por favor, complete todos los campos.');
        }
    }
}

function ValidarFormularioCortes() {
    const inputMeta = document.querySelectorAll('#meta');
    const inputDobles = document.querySelectorAll('#dobles');
    inputDobles.forEach((input) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2);
        })
    })
    inputMeta.forEach((input) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3);
        });

        const inputNombre = document.getElementById('nombre');
        inputNombre.addEventListener('input', function () {
            if (this.value.length > 30) {
                this.value = this.value.slice(0, 30);
            }
        });
    });

    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');

    // Encontrar la fecha de fin máxima entre los rangos y la fecha actual
    let fechaMinima = new Date(); // Comenzar con la fecha actual
    rangosFechasExistentes.forEach(rango => {
        const rangoFin = new Date(rango.fecha_fin);
        if (rangoFin > fechaMinima) {
            fechaMinima = rangoFin; // Actualizar si se encuentra una fecha mayor
        }
    });

    // Sumar un día para garantizar que la nueva fecha sea posterior al último corte
    fechaMinima.setDate(fechaMinima.getDate() + 2);

    // Formatear la fecha mínima
    const diaMin = ("0" + fechaMinima.getDate()).slice(-2);
    const mesMin = ("0" + (fechaMinima.getMonth() + 1)).slice(-2);
    const fechaMinimaFormateada = fechaMinima.getFullYear() + "-" + mesMin + "-" + diaMin;

  

    // Validar fechas al cambiar
    fechaInicioInput.addEventListener('input', validarFechas);
    fechaFinInput.addEventListener('input', validarFechas);

    function validarFechas() {

        const fechaInicioSeleccionada = new Date(fechaInicioInput.value);
        const fechaFinSeleccionada = new Date(fechaFinInput.value);

        // Validar que fecha inicio no sea mayor a fecha fin
        if (fechaInicioSeleccionada > fechaFinSeleccionada) {
            alert('Fecha fin debe ser posterior a fecha inicio.');
            fechaFinInput.value = ''; // Limpiar fecha fin si es inválido
        } else {
            fechaFinInput.setCustomValidity(''); // Limpiar mensaje de error si es válido
        }
    }
}

function ValidarFormularioMunicipios() {
    const inputNombre = document.getElementById('nombreMunicipio');
    inputNombre.addEventListener('input', function () {
        if (this.value.length > 30) {
            this.value = this.value.slice(0, 100);
        }
    });
}

function ValidarFormularioCausales() {
    const inputNombre = document.getElementById('nombreCausal');
    inputNombre.addEventListener('input', function () {
        if (this.value.length > 30) {
            this.value = this.value.slice(0, 30);
        }
    });

}

function EnviarServidorStore(nombreTabla) {
    const datosFormulario = {};
    const token = document.querySelector('#token').value;
    $(`#${nombreTabla}Modal input, #${nombreTabla}Modal select`).each(function () {
        datosFormulario[this.name] = $(this).val();
    });

    $.ajax({
        type: 'POST',
        url: `cortes_produccion/store/${nombreTabla}`,
        data: {
            datos: datosFormulario,
            _token: token
        },
        success: function (response) {
            if(response.errors){
                alert(response.errors);
                return;
            }
            const camposFormulario = document.querySelectorAll(`#${nombreTabla}Modal input`);
            camposFormulario.forEach(campo => campo.value = '');
            $('#' + nombreTabla + 'Modal').modal('hide');
            window.location.reload();
        },
        error: function (xhr, status, error) {
            console.log(xhr.responseText);
            Swal.fire({
                type: 'error',
                title: 'Error al guardar',
                text: 'Por favor, inténtelo de nuevo.',
            });
        }
    });
}

function EnviarServidorUpdate(nombreTabla, id) {
    const datosFormulario = {};
    const token = document.querySelector('#token').value;
    $(`#${nombreTabla}Modal input, #${nombreTabla}Modal select`).each(function () {
        datosFormulario[this.name] = $(this).val();
    });

    $.ajax({
        type: 'PUT',
        url: `cortes_produccion/${id}/update${nombreTabla}`,
        data: {
            datos: datosFormulario,
            _token: token
        },
        success: function (response) {
            if(response.errors){
                alert(response.errors);
                return;
            }
            const camposFormulario = document.querySelectorAll(`#${nombreTabla}Modal input`);
            camposFormulario.forEach(campo => campo.value = '');
            $('#' + nombreTabla + 'Modal').modal('hide');
            window.location.reload();
        },
        error: function (xhr, status, error) {
            console.log(xhr.responseText);
            Swal.fire({
                type: 'error',
                title: 'Error al guardar',
                text: 'Por favor, inténtelo de nuevo.',
            });
        }
    });
}

function detallesCorte(id) {
    window.location.href = `produccion/detalles_corte/${id}`;
}

function Graficos(id) {
    window.location.href = `produccion?id=${id}`;
}