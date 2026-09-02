<script>
/* Utilidades compartidas por las tres vistas de actividad: el fragmento que se
   muestra en la celda y la ventana con el contenido completo.
   Antes esto lo montaba el servidor como HTML y lo inyectaba la tabla. */
window.visorJson = () => ({
    verMas: null,          // { clave, texto }
    LIMITE: 70,

    /* Texto plano de un valor, con los espacios colapsados como hacía el
       servidor, para poder medirlo y recortarlo. */
    aTexto(valor) {
        if (valor === null || valor === undefined) return '';
        const bruto = typeof valor === 'object' ? JSON.stringify(valor) : String(valor);
        return bruto.replace(/\s+/g, ' ');
    },

    fragmento(valor, limite = this.LIMITE) {
        const texto = this.aTexto(valor);
        return texto.length > limite ? texto.slice(0, limite) + '…' : texto;
    },

    esLargo(valor, limite = this.LIMITE) {
        return typeof valor === 'object' || this.aTexto(valor).length > limite;
    },

    /* El JSON se indenta para leerlo; un valor suelto se muestra tal cual. */
    completo(valor) {
        if (valor === null || valor === undefined) return '';
        return typeof valor === 'object' ? JSON.stringify(valor, null, 2) : String(valor);
    },

    abrirJson(clave, valor) {
        this.verMas = { clave, texto: this.completo(valor) };
    },

    /* Pares clave/valor de old_values y new_values, listos para recorrer. */
    pares(valores) {
        if (!valores || typeof valores !== 'object') return [];
        return Object.entries(valores).map(([clave, valor]) => ({ clave, valor }));
    },

    /* Solo los primeros campos: una fila con veinte hacía la tabla ilegible.
       El resto se consulta abriendo el objeto completo. */
    VISIBLES: 3,
    paresVisibles(valores) { return this.pares(valores).slice(0, this.VISIBLES); },
    paresOcultos(valores) { return Math.max(0, this.pares(valores).length - this.VISIBLES); },
});

/* Paginación en memoria, compartida por las tablas de actividad. */
window.paginador = (porPagina = 25) => ({
    pagina: 1,
    porPagina,

    totalPaginasDe(lista) { return Math.max(1, Math.ceil(lista.length / this.porPagina)); },

    /* La página se recorta al rango válido en vez de guardarse corregida: hacerlo
       dentro de un getter es escribir estado mientras se pinta. */
    paginaValidaDe(lista) { return Math.min(Math.max(1, this.pagina), this.totalPaginasDe(lista)); },

    recortar(lista) {
        const desde = (this.paginaValidaDe(lista) - 1) * this.porPagina;
        return lista.slice(desde, desde + this.porPagina);
    },
});
</script>
