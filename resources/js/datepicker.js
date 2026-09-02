/* ============================================================
   Calendario propio para los <input type="date">

   El desplegable del <select> se pudo redondear sacándolo del modo nativo
   (`appearance: base-select`). Para las fechas no existe nada equivalente:
   ningún navegador expone su calendario a CSS, ni siquiera con un
   pseudo-elemento, así que la única forma de que tenga la forma de la
   aplicación es dibujarlo nosotros.

   El <input> sigue siendo `type="date"`: conserva su valor en formato
   YYYY-MM-DD, su validación, su `min`/`max` y el tecleo directo. Lo único
   que se sustituye es el panel que se abre. Por eso todo va con un único
   escuchador delegado en `document` en vez de recorrer los campos al
   arrancar: funciona igual con los que aparecen después dentro de una
   ventana modal, sin observadores ni reinicios.
   ============================================================ */

export const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

/* Empieza en domingo, igual que el calendario nativo que sustituye. */
export const DIAS = ['DO', 'LU', 'MA', 'MI', 'JU', 'VI', 'SA'];

/* ------------------------------- Fechas --------------------------------- */

/* A texto YYYY-MM-DD. No se usa toISOString(): ese convierte a UTC y en
   Colombia (UTC-5) devuelve el día anterior para cualquier hora local. */
export function aIso(fecha) {
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${fecha.getFullYear()}-${mes}-${dia}`;
}

/* De YYYY-MM-DD a Date local. new Date('2026-09-01') se interpreta como UTC
   y retrocede un día al leerlo; con los tres números va a hora local. */
export function deIso(texto) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(texto ?? '').trim());
    if (!m) return null;
    const [, a, s, d] = m.map(Number);
    const fecha = new Date(a, s - 1, d);
    // Descarta lo imposible: '2026-02-31' se desbordaría a marzo.
    return fecha.getMonth() === s - 1 && fecha.getDate() === d ? fecha : null;
}

export function mismoDia(a, b) {
    return !!a && !!b && a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

/* Los 42 días (seis semanas fijas) que se ven al abrir un mes. La altura
   constante evita que el panel dé saltos al cambiar de mes. */
export function rejilla(anio, mes) {
    const primero = new Date(anio, mes, 1);
    const inicio = new Date(anio, mes, 1 - primero.getDay());
    return Array.from({ length: 42 }, (_, i) =>
        new Date(inicio.getFullYear(), inicio.getMonth(), inicio.getDate() + i));
}

/* Fuera del rango permitido por los atributos min/max del campo. */
export function fuera(fecha, min, max) {
    const iso = aIso(fecha);
    return (!!min && iso < min) || (!!max && iso > max);
}

/* Deja una fecha dentro del rango; sirve para decidir qué mes se abre. */
export function acotar(fecha, min, max) {
    const desde = deIso(min);
    const hasta = deIso(max);
    if (desde && fecha < desde) return desde;
    if (hasta && fecha > hasta) return hasta;
    return fecha;
}

/* ------------------------------ Interfaz -------------------------------- */

/* Solo los campos de las vistas migradas. Se excluye a propósito el editor
   de Handsontable, que es otro <input type="date"> pero vive dentro de la
   rejilla y depende del calendario nativo para sus límites. */
export function aplica(campo) {
    return !!campo
        && campo.classList.contains('tw-input')
        && !campo.classList.contains('handsontableInput')
        && !campo.disabled
        && !campo.readOnly;
}

export default function registrarCalendario() {
    let panel = null;
    let campo = null;          // el <input> al que está enganchado ahora
    let anio = 0;
    let mes = 0;

    const abierto = () => !!panel && panel.dataset.abierto === '1';

    function construir() {
        panel = document.createElement('div');
        panel.className = 'tw-cal';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Calendario');
        panel.hidden = true;
        panel.innerHTML = `
            <div class="tw-cal-cab">
                <button type="button" class="tw-cal-nav" data-salto="-12" aria-label="Año anterior">
                    <i class="fas fa-angles-left"></i></button>
                <button type="button" class="tw-cal-nav" data-salto="-1" aria-label="Mes anterior">
                    <i class="fas fa-angle-left"></i></button>
                <span class="tw-cal-titulo" data-titulo aria-live="polite"></span>
                <button type="button" class="tw-cal-nav" data-salto="1" aria-label="Mes siguiente">
                    <i class="fas fa-angle-right"></i></button>
                <button type="button" class="tw-cal-nav" data-salto="12" aria-label="Año siguiente">
                    <i class="fas fa-angles-right"></i></button>
            </div>
            <div class="tw-cal-dow">${DIAS.map(d => `<span>${d}</span>`).join('')}</div>
            <div class="tw-cal-rejilla" data-rejilla></div>
            <div class="tw-cal-pie">
                <button type="button" class="tw-cal-enlace" data-accion="borrar">Borrar</button>
                <button type="button" class="tw-cal-enlace" data-accion="hoy">Hoy</button>
            </div>`;
        document.body.appendChild(panel);

        /* Al pulsar dentro del panel no se le quita el foco al campo: así el
           navegador no cierra nada por su cuenta y el foco vuelve limpio. */
        panel.addEventListener('mousedown', (e) => e.preventDefault());
        panel.addEventListener('click', alPulsarPanel);
    }

    function alPulsarPanel(e) {
        const salto = e.target.closest('[data-salto]');
        if (salto) {
            const total = anio * 12 + mes + Number(salto.dataset.salto);
            anio = Math.floor(total / 12);
            mes = total - anio * 12;
            pintar();
            return;
        }

        const accion = e.target.closest('[data-accion]');
        if (accion) {
            if (accion.dataset.accion === 'borrar') return elegir('');
            const hoy = new Date();
            if (!fuera(hoy, campo.min, campo.max)) elegir(aIso(hoy));
            return;
        }

        const dia = e.target.closest('[data-iso]');
        if (dia && !dia.disabled) elegir(dia.dataset.iso);
    }

    function pintar() {
        panel.querySelector('[data-titulo]').textContent = `${MESES[mes]} de ${anio}`;

        const hoy = new Date();
        const elegido = deIso(campo.value);
        const min = campo.min;
        const max = campo.max;

        panel.querySelector('[data-rejilla]').innerHTML = rejilla(anio, mes).map((d) => {
            const clases = ['tw-cal-dia'];
            if (d.getMonth() !== mes) clases.push('es-otro-mes');
            if (mismoDia(d, hoy)) clases.push('es-hoy');
            if (mismoDia(d, elegido)) clases.push('es-elegido');
            const iso = aIso(d);
            return `<button type="button" class="${clases.join(' ')}" data-iso="${iso}"
                            ${fuera(d, min, max) ? 'disabled' : ''}
                            ${mismoDia(d, elegido) ? 'aria-current="date"' : ''}
                            aria-label="${d.getDate()} de ${MESES[d.getMonth()]} de ${d.getFullYear()}"
                    >${d.getDate()}</button>`;
        }).join('');

        // "Hoy" se apaga si el campo tiene un rango que lo deja fuera.
        panel.querySelector('[data-accion="hoy"]').disabled = fuera(hoy, min, max);
    }

    function posicionar() {
        const caja = campo.getBoundingClientRect();

        /* Un campo dentro de una ventana que se acaba de cerrar sigue en el
           DOM pero mide cero: el panel se quedaría flotando en una esquina. */
        if (caja.width === 0 && caja.height === 0) return cerrar();

        panel.hidden = false;
        const alto = panel.offsetHeight;
        const ancho = panel.offsetWidth;
        const hueco = 6;

        // Si no cabe debajo pero sí encima, se despliega hacia arriba.
        const abajo = caja.bottom + hueco + alto <= window.innerHeight;
        panel.style.top = `${abajo ? caja.bottom + hueco : Math.max(hueco, caja.top - hueco - alto)}px`;

        const izquierda = Math.min(caja.left, window.innerWidth - ancho - hueco);
        panel.style.left = `${Math.max(hueco, izquierda)}px`;
    }

    function abrir(nuevo) {
        if (!panel) construir();
        campo = nuevo;

        const referencia = deIso(campo.value)
            ?? acotar(new Date(), campo.min, campo.max);
        anio = referencia.getFullYear();
        mes = referencia.getMonth();

        panel.dataset.abierto = '1';
        pintar();
        posicionar();
        campo.focus({ preventScroll: true });
    }

    function cerrar() {
        if (!abierto()) return;
        panel.dataset.abierto = '0';
        panel.hidden = true;
        campo = null;
    }

    function elegir(iso) {
        const destino = campo;
        destino.value = iso;
        /* Los dos eventos: `input` es el que escucha x-model y `change` el que
           esperan los formularios y los @change de las vistas. */
        destino.dispatchEvent(new Event('input', { bubbles: true }));
        destino.dispatchEvent(new Event('change', { bubbles: true }));
        cerrar();
        destino.focus({ preventScroll: true });
    }

    /* Un solo escuchador en captura: abre, cambia de campo y cierra. Va en
       captura y corta el evento para que el navegador no llegue a desplegar
       su propio calendario. */
    document.addEventListener('mousedown', (e) => {
        if (panel && panel.contains(e.target)) return;

        const destino = e.target.closest?.('input[type="date"]');
        if (destino && aplica(destino)) {
            e.preventDefault();
            // Volver a pulsar el mismo campo lo cierra, como cualquier menú.
            if (abierto() && destino === campo) cerrar();
            else abrir(destino);
            return;
        }
        cerrar();
    }, true);

    /* Alt+↓ y F4 también abren el calendario del navegador. */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && abierto()) {
            const destino = campo;
            cerrar();
            destino?.focus({ preventScroll: true });
            return;
        }
        const esAtajo = (e.altKey && e.key === 'ArrowDown') || e.key === 'F4';
        if (!esAtajo) return;
        const destino = e.target?.closest?.('input[type="date"]');
        if (destino && aplica(destino)) {
            e.preventDefault();
            abrir(destino);
        }
    }, true);

    window.addEventListener('resize', () => { if (abierto()) posicionar(); });
    // En captura: los modales tienen su propio contenedor con scroll.
    window.addEventListener('scroll', () => { if (abierto()) posicionar(); }, true);
}
