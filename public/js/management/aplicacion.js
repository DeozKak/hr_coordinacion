$(document).ready(function(){
    $(document).on('change', '#parametro', function(){

        let parametro = $(this)
        let value = parametro.val()
        let divFecha = $('.divFecha')
        let divTipoOrden = $('.divTipoOrden')

        if(value == '1'){
            divFecha.css('display', 'block')
            divTipoOrden.removeClass('col-md-6')
            parametro.parent().removeClass('col-md-6')
        }else{
            divFecha.css('display', 'none')
            divTipoOrden.addClass('col-md-6')
            parametro.parent().addClass('col-md-6')
        }

    })

    $(document).on('change', '#poblacion', function(){

        let poblacion = $(this)
        let value = poblacion.val()
        let divInspector = $('.divInspector')
        
        if(value == '2'){
            divInspector.css('display','none')
            poblacion.parent().addClass('col-md-12')
        }else{
            divInspector.css('display','block')
            poblacion.parent().removeClass('col-md-12')
        }

    })


    document.getElementById('generarAplicacion').addEventListener('click', function(){
        console.log('hola mundo')
    })
})