
<div class="row" style="margin-bottom: 50px;">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <label for="buscarGrupo2">Grupo:</label>
                <select class="form-control" id="buscarGrupo2">
                    <option value="">Seleccione un grupo</option>
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo->id }}">{{ $grupo->grupo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="buscarGrupo2">Sub Grupo:</label>
                <select class="form-control" id="buscarSubGrupo2">
                    <option value="">Seleccione un sub grupo</option>
                    @foreach ($subgrupos as $subgrupo)
                        <option value="{{ $subgrupo->id }}">{{ $subgrupo->subgrupo }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
<form method="POST" action="{{ route('zonas.responsablesStore',['id_sub'=> ':id_sub','id_grup'=>':id_grup']) }}" id="form-guardar-inspectores">
    @csrf
    <div class="row" style="display: none;">
        <div class="col-md-5">
            <label for="buscar_disponibles">Buscar disponibles</label>
            <input type="text" class="form-control mb-2" id="buscar_disponibles" placeholder="Buscar inspector...">
            <label for="disponibles">Inspectores disponibles</label>
            <select multiple id="disponibles" class="form-control" size="10">
              {{--  @foreach($inspectores as $inspector)
                     @if(!in_array($inspector->id, $asignados))
                         <option value="{{ $inspector->id }}">
                             {{ $inspector->id }}. {{$inspector->apellidos}} {{ $inspector->nombres }}
                         </option>
                     @endif
                @endforeach--}}
            </select>
        </div>
        <div class="col-md-2 d-flex flex-column align-items-center justify-content-center">
            <button type="button" class="btn btn-sm btn-primary mb-2" id="asignar" style="width:70px;">&#62;&#62;
            </button>
            <button type="button" class="btn btn-sm btn-secondary" id="quitar" style="width:70px;">&#60;&#60;</button>
        </div>
        <div class="col-md-5">

            <label for="buscar_asignados">Buscar asignados</label>
            <input type="text" class="form-control mb-2" id="buscar_asignados" placeholder="Buscar inspector...">
            <label for="asignados">Inspectores asignados</label>
            <select name="inspectores[]" multiple id="asignados" class="form-control" size="10">
               {{-- @foreach($inspectores as $inspector)
                      @if(in_array($inspector->id, $asignados))
                          <option value="{{ $inspector->id }}">
                              {{ $inspector->id }}. {{$inspector->apellidos}} {{ $inspector->nombres }}
                          </option>
                      @endif
                @endforeach--}}
            </select>
        </div>

    </div>
    <div class="mt-3 text-center">
        <button type="submit" class="btn btn-primary"  id="btn-guardar" disabled>Guardar asignación</button>
    </div>
</form>

<script>




    SelectGrupo_a = new TomSelect("#buscarGrupo2", {maxItems: 1, create: false, placeholder: "Seleccione un grupo"});
    SelectSubGrupo_a = new TomSelect("#buscarSubGrupo2", {
        maxItems: 1,
        create: false,
        placeholder: "Seleccione un sub grupo"
    });

    SelectGrupo_a.on('change', mostrarOcultarRowYFuncion);
    SelectSubGrupo_a.on('change', mostrarOcultarRowYFuncion);
    // Filtro para disponibles
    document.getElementById('buscar_disponibles').addEventListener('input', function () {
        filtrarSelect('buscar_disponibles', 'disponibles');
    });
    // Filtro para asignados
    document.getElementById('buscar_asignados').addEventListener('input', function () {
        filtrarSelect('buscar_asignados', 'asignados');
    });

    function mostrarOcultarRowYFuncion() {
        const grupo = SelectGrupo_a.getValue();
        const subgrupo = SelectSubGrupo_a.getValue();
        const fila = document.querySelector('form#form-guardar-inspectores > .row[style]'); // selecciona tu row
        actualizar_selects2()
        const btn_guardar = document.querySelector('#btn-guardar');
        if (grupo && subgrupo) {
            fila.style.display = "";
            // Ejecuta aquí tu función personalizada
            btn_guardar.disabled = false;
            cargarInspectoresSegunGrupoYSubgrupo()
        } else {
            btn_guardar.disabled = true;
            fila.style.display = "none";
        }
    }


    function filtrarSelect(inputId, selectId) {
        let filtro = document.getElementById(inputId).value.toLowerCase();
        let options = document.getElementById(selectId).options;
        for (let i = 0; i < options.length; i++) {
            let texto = options[i].text.toLowerCase();
            options[i].style.display = texto.includes(filtro) ? "" : "none";
        }
    }

    // Asignar/Desasignar
    document.getElementById('asignar').addEventListener('click', function () {
        moverSeleccionados('disponibles', 'asignados');
    });
    document.getElementById('quitar').addEventListener('click', function () {
        moverSeleccionados('asignados', 'disponibles');
    });

    function moverSeleccionados(origenId, destinoId) {
        let origen = document.getElementById(origenId);
        let destino = document.getElementById(destinoId);
        let seleccion = Array.from(origen.selectedOptions);
        seleccion.forEach(opt => {
            // Al mover, restauramos display si estaba filtrado
            opt.style.display = "";
            destino.appendChild(opt);
        });
    }

    // Asegura que todos los asignados estén seleccionados antes de enviar
    document.getElementById('form-guardar-inspectores').addEventListener('submit', function () {
        let asignados = document.getElementById('asignados');
        for (let i = 0; i < asignados.options.length; i++) {
            asignados.options[i].selected = true;
        }
    });


    $('#form-guardar-inspectores').on('submit', function (e) {
        e.preventDefault(); // Evita recargar la página

        // Selecciona todos los inspectores asignados antes de enviar
        $('#asignados option').prop('selected', true);

        let form = $(this)[0];
        let formData = new FormData(form);
        let action = $(this).attr('action'); // Cambia el action al correcto si es necesario
        action = action.replace(':id_sub', SelectSubGrupo_a.getValue()).replace(':id_grup', SelectGrupo_a.getValue());
        $.ajax({
            url: action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Aquí va lo que quieras hacer al éxito, ejemplo:
                Swal.fire({
                    title: '¡Guardado!',
                    text: response.success,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Si usas modal, puedes cerrarlo aquí también
                $('#ResponsablesModal').modal('hide');
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                // Puedes poner aquí las validaciones del backend
                Swal.fire({
                    title: 'Error',
                    text: xhr.responseJSON.error,
                    icon: 'error'
                });
            }
        });
    });

    function actualizar_selects2() {

        const grupo = SelectGrupo_a.getValue();
        const subgrupo = SelectSubGrupo_a.getValue();

        $.ajax({
            url: 'zonas/selects',
            type: 'GET',
            data: {
                grupo: grupo,
                subgrupo: subgrupo,
            },
            success: function (response) {

                const grupos = extraerUnicos(response.data || [], 'tbl_grupo');
                const subgrupos = extraerUnicos(response.data || [], 'tbl_subgrupo');

                actualizarTomSelect(SelectGrupo_a, grupos, 'id', 'grupo');
                actualizarTomSelect(SelectSubGrupo_a, subgrupos, 'id', 'subgrupo');

            }, error: function (xhr, status) {
                console.log(xhr.responseText);
            }
        })

    }

    function cargarInspectoresSegunGrupoYSubgrupo() {
        const grupo     = SelectGrupo_a.getValue();
        const subgrupo  = SelectSubGrupo_a.getValue();
        // Si pasas id de detalle por alguna razón:  const detalle_id = ...;

        if (grupo && subgrupo) {
            $.ajax({
                url: 'zonas/inspectores-por-grupo',
                type: 'GET',
                data: {
                    grupo: grupo,
                    subgrupo: subgrupo,
                    // detalle: detalle_id // si necesitas mandarlo
                },
                success: function (response) {
                    reconstruirSelect('disponibles', response.disponibles);
                    reconstruirSelect('asignados', response.asignados);
                },
                error: function (xhr) {
                    // Maneja el error aquí si necesario
                    console.log(xhr.responseText);
                    Swal.fire({
                        title: 'Error',
                        text: 'Hubo un problema al cargar los inspectores.',
                        icon: 'error'
                    });
                }
            });
        }
    }

    function reconstruirSelect(selectId, inspectores) {
        let select = document.getElementById(selectId);
        select.innerHTML = '';
        inspectores.forEach(function(inspector) {
            let opt = document.createElement('option');
            opt.value = inspector.id;
            opt.text = inspector.id + '. ' + inspector.apellidos + ' ' + inspector.nombres;
            select.appendChild(opt);
        });
    }



</script>
