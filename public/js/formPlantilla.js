// Esperamos a que el documento esté completamente cargado
document.addEventListener('DOMContentLoaded', function () {
    // Obtenemos el botón "Agregar"
    const agregarButton = document.getElementById('agregar');
    const nombre_usuario = document.getElementById('nombre_usuario');
    const contratoInput = document.getElementById('contrato');
    const celularInput = document.getElementById('celular');
    const orden_trabajo = document.getElementById('orden_trabajo');
    const fechaInput = document.getElementById('fecha');
    const usuario_programado = document.getElementById('usuario_programado');
    const fechaAgendamientoInput = document.getElementById('fecha_agendamiento');
    usuario_programado.value = user.name;



    // Obtener la fecha actual en formato YYYY-MM-DD
    const hoy = new Date();
    const year = hoy.getFullYear();
    const month = (hoy.getMonth() + 1).toString().padStart(2, '0');
    const day = hoy.getDate().toString().padStart(2, '0');
    const fechaFormateada = `${year}-${month}-${day}`; // Formato correcto para input type="date"

    // Asignar la fecha actual al input
    fechaInput.value = fechaFormateada;
    hoy.setDate(hoy.getDate() + 1);
    const year2 = hoy.getFullYear();
    const month2 = (hoy.getMonth() + 1).toString().padStart(2, '0');
    const day2 = hoy.getDate().toString().padStart(2, '0');
    const fechaFormateada2 = `${year2}-${month2}-${day2}`;
    // Asignar la fecha de mañana como valor mínimo del input
    fechaAgendamientoInput.min = fechaFormateada2;

    nombre_usuario.addEventListener('input', function () {
        this.value = this.value.replace(/[^a-zA-Z ]/g, '').slice(0, 50);
    });
    contratoInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15);
    });

    celularInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9 ]/g, '').slice(0, 21);
    });

    orden_trabajo.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18);
    });

    // Adjuntamos un event listener al clic del botón
    agregarButton.addEventListener('click', function () {
        // Creamos un objeto para almacenar los datos del formulario
        const formData = {};

        // Capturamos los valores de los campos de entrada de texto
        formData.contrato = document.getElementById('contrato').value;
        formData.celular = document.getElementById('celular').value;
        formData.nombre_usuario = document.getElementById('nombre_usuario').value;
        formData.orden_trabajo = document.getElementById('orden_trabajo').value;
        formData.direccion = document.getElementById('direccion').value;
        formData.barrio = document.getElementById('barrio').value;
        formData.observaciones = document.getElementById('observaciones').value;
        formData.usuario_programado = document.getElementById('usuario_programado').value;

        // Capturamos el valor del select para el tipo de trabajo
        formData.tipo_trabajo = document.getElementById('tipo_trabajo').value;

        // Capturamos el valor de la fecha
        formData.fecha = document.getElementById('fecha').value;

        // Capturamos el valor del select para el municipio 
        formData.municipio = document.getElementById('municipio-select').value;

        // Capturamos el estado (activo o suspendido)
        formData.estado = document.querySelector('input[name="estado"]:checked').value;

        // Capturamos el valor del select para la categoría
        formData.categoria = document.getElementById('categoria').value;

        // Capturamos el valor de la fecha de agendamiento
        formData.fecha_agendamiento = document.getElementById('fecha_agendamiento').value;

        // Capturamos el valor del select para el inspector
        formData.inspector = document.getElementById('tecnico').value;

        // Capturamos las horas de inicio y fin
        formData.hora_inicio = document.getElementById('hora_inicio').value;
        formData.hora_fin = document.getElementById('hora_fin').value;

        // Validación de campos obligatorios
        const camposObligatorios = ['contrato', 'celular', 'nombre_usuario', 'direccion','barrio', 'tipo_trabajo', 'fecha','municipio-select','categoria','fecha_agendamiento','observaciones','tecnico','hora_inicio','hora_fin'];

        let hayCamposVacios = false; // Variable para controlar si hay campos vacíos

        for (const campo of camposObligatorios) {
            const elementoCampo = document.getElementById(campo); // Obtenemos el elemento del campo
           
            if (!formData[campo]) {
                elementoCampo.classList.add('campo-invalido'); // Añadimos la clase 'error' para resaltar en rojo
                hayCamposVacios = true;
            } else {
                elementoCampo.classList.remove('campo-invalido'); // Removemos la clase si el campo se llena posteriormente
            }
        }

        if (hayCamposVacios) {
            alert('Por favor, complete todos los campos obligatorios.');
            return; // Detenemos el envío del formulario
        }

        // Si todos los campos están completos, puedes continuar con el envío del formulario
        console.log(formData);
    });
});