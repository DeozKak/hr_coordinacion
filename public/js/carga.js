document.addEventListener("DOMContentLoaded", function() {
    const loader = document.querySelector('.loader');
    loader.style.display = 'block'; // Mostrar la animación de carga al inicio

    window.addEventListener("load", function() {
        setTimeout(function() {
            loader.style.display = 'none'; // Ocultar la animación de carga después de 3 segundos
        }, 3000); // 3000 milisegundos = 3 segundos
    });
});