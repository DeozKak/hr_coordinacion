$(document).ready(function () {

$('table').DataTable( {
    scrollCollapse: true,
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
    
}});

const thfecha = $('th[data-dt-column="2"]');
const spanElement = thfecha.find('span.dt-column-title');

// Simular doble clic en el span
spanElement.trigger('click').trigger('click');

});