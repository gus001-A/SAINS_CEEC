/**
 * Rutas de la app que respetan la carpeta donde está instalada.
 *
 * Una ruta fija como "/dashboard" apunta a la raíz del dominio y falla cuando el
 * sistema vive en una subcarpeta (p. ej. http://servidor/ceec). La directiva
 * @routes declara `const Ziggy = {...}` con la URL raíz real de la petición
 * (url('/')); es una variable global, pero no queda en `window`.
 */
function urlRaiz() {
    // eslint-disable-next-line no-undef
    const ziggy = typeof Ziggy !== 'undefined' ? Ziggy : globalThis.Ziggy;

    return (ziggy?.url ?? '').replace(/\/+$/, '');
}

/** Carpeta base de la app ("" en la raíz del dominio, "/ceec" en subcarpeta). */
function carpetaBase() {
    const raiz = urlRaiz();
    if (!raiz) {
        return '';
    }
    try {
        return new URL(raiz).pathname.replace(/\/+$/, '');
    } catch {
        return '';
    }
}

/** "/dashboard" → "http://servidor/ceec/dashboard" (o "/dashboard" en la raíz). */
export function urlApp(ruta) {
    return `${urlRaiz()}/${String(ruta).replace(/^\/+/, '')}`;
}

/** Ruta de la página actual sin la carpeta base: "/ceec/admin/clases" → "/admin/clases". */
export function rutaSinBase(url) {
    const base = carpetaBase();
    const ruta = String(url ?? '');

    return base && ruta.startsWith(base) ? ruta.slice(base.length) || '/' : ruta;
}

/** true si la página actual ($page.url) está dentro de la sección indicada. */
export function enSeccion(url, seccion) {
    return rutaSinBase(url).startsWith(seccion);
}
