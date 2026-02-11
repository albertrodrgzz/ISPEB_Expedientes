/**
 * SIGEX v3.5 - Utilidades Globales
 * Funciones helper para gestión de URLs y AJAX
 */

/**
 * Obtiene la URL base de la aplicación desde el meta tag
 * @returns {string} URL base sin trailing slash
 */
function getBaseUrl() {
    const metaTag = document.querySelector('meta[name="app-url"]');
    if (!metaTag) {
        console.error('❌ Meta tag app-url no encontrado en el HTML');
        console.warn('💡 Asegúrate de agregar <meta name="app-url" content="<?= APP_URL ?>"> en el <head>');
        return '';
    }

    let url = metaTag.getAttribute('content');
    // Eliminar trailing slash si existe
    return url.replace(/\/$/, '');
}

/**
 * Wrapper para fetch API con URL base automática
 * Simplifica las llamadas AJAX usando rutas relativas
 * 
 * @param {string} endpoint - Endpoint relativo (ej: 'vistas/ajax/getData.php')
 * @param {object} options - Opciones de fetch (method, body, headers, etc.)
 * @returns {Promise<Response>} Promise del fetch
 * 
 * @example
 * // GET simple
 * fetchAPI('vistas/funcionarios/ajax/listar.php')
 *   .then(r => r.json())
 *   .then(data => console.log(data));
 * 
 * // POST con JSON
 * fetchAPI('vistas/ajax/save.php', {
 *   method: 'POST',
 *   body: JSON.stringify({ nombre: 'Juan' })
 * });
 * 
 * // POST con FormData
 * const formData = new FormData();
 * formData.append('file', fileInput.files[0]);
 * fetchAPI('vistas/ajax/upload.php', {
 *   method: 'POST',
 *   body: formData,
 *   headers: {} // No set Content-Type for FormData
 * });
 */
async function fetchAPI(endpoint, options = {}) {
    const baseUrl = getBaseUrl();

    if (!baseUrl) {
        throw new Error('No se pudo obtener la URL base de la aplicación');
    }

    // Limpiar endpoint (eliminar slash inicial si existe)
    endpoint = endpoint.replace(/^\//, '');

    // Construir URL completa
    const url = `${baseUrl}/${endpoint}`;

    // Configuración por defecto solo si no es FormData
    const defaultOptions = {};

    // Solo agregar Content-Type si no hay body o si no es FormData
    if (!options.body || !(options.body instanceof FormData)) {
        defaultOptions.headers = {
            'Content-Type': 'application/json'
        };
    }

    // Merge de opciones
    const finalOptions = { ...defaultOptions, ...options };

    // Si hay headers personalizados, hacer merge también
    if (options.headers && defaultOptions.headers) {
        finalOptions.headers = { ...defaultOptions.headers, ...options.headers };
    }

    try {
        const response = await fetch(url, finalOptions);
        return response;
    } catch (error) {
        console.error(`❌ Error en fetchAPI (${url}):`, error);
        throw error;
    }
}

/**
 * Helper para construir URLs completas desde rutas relativas
 * 
 * @param {string} path - Ruta relativa (ej: 'publico/img/logo.png')
 * @returns {string} URL completa
 * 
 * @example
 * const logoUrl = buildUrl('publico/img/logo.png');
 * // http://localhost/APP3/publico/img/logo.png
 * 
 * img.src = buildUrl('publico/avatars/user.jpg');
 */
function buildUrl(path) {
    const baseUrl = getBaseUrl();
    if (!baseUrl) {
        console.error('❌ No se pudo construir URL');
        return path;
    }
    path = path.replace(/^\//, '');
    return `${baseUrl}/${path}`;
}

/**
 * Helper para debugging - muestra la configuración actual
 */
function debugAppConfig() {
    console.group('🔧 SIGEX v3.5 - Configuración');
    console.log('📍 Base URL:', getBaseUrl());
    console.log('🌐 Location:', window.location.href);
    console.log('📄 Document URL:', document.URL);
    console.groupEnd();
}

// Exponer funciones globalmente
window.getBaseUrl = getBaseUrl;
window.fetchAPI = fetchAPI;
window.buildUrl = buildUrl;
window.debugAppConfig = debugAppConfig;

// Inicialización
document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ SIGEX v3.5 - Utilidades globales cargadas');
    console.log('📍 Base URL:', getBaseUrl());

    // Búsqueda en tiempo real (mantener funcionalidad existente)
    const buscarInput = document.getElementById('buscar');

    if (buscarInput) {
        buscarInput.addEventListener('input', function (e) {
            const termino = e.target.value.toLowerCase();
            const filas = document.querySelectorAll('#tabla-funcionarios tr');

            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(termino) ? '' : 'none';
            });
        });
    }

    // NOTA: El código del menú hamburguesa está centralizado en sidebar.php
    // No duplicar aquí para evitar conflictos
});
