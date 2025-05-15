document.addEventListener('DOMContentLoaded', function() {

    $(document).on('click', '#btn-asignacion', function() {
        $('#responsables-modal-body').html('<span class="spinner-border spinner-border-sm"></span> Cargando...');
        $('#ResponsablesModal').modal('show');
        let url = 'zonas/responsables-form';
        $.get(url, function(resp) {
            //$('#ResponsableModalLabel').html('Responsables del Subgrupo: ' + resp.subgrupo.subgrupo);
            $('#responsables-modal-body').html(resp.html); // Renderizas el HTML igual
        });

    });



})
