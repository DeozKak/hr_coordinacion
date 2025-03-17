$(document).ready(function(){
    $(document).on('change', '#parametro', function(){

        let valorParametro = $(this).val()
        let divTipoOrden = $('.divTipoOrden')
        let divFecha = $('.divFecha')

        if(valorParametro == 2){
            divTipoOrden.hide()
            divFecha.hide()
        }else if(valorParametro == 1){
            divTipoOrden.show()
            divFecha.show()
        }else{
            divTipoOrden.show()
            divFecha.hide()
        }
    })
})