/**
 * Utilidades de SweetAlert2 para ISPEB
 * Reemplazo de alerts nativos con alertas bonitas
 */

// Configuración global de SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

/**
 * Mostrar mensaje de éxito
 */
function mostrarExito(mensaje, titulo = '¡Éxito!') {
    return Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensaje,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#00a8cc'
    });
}

/**
 * Mostrar mensaje de error
 */
function mostrarError(mensaje, titulo = 'Error') {
    return Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#00a8cc'
    });
}

/**
 * Mostrar mensaje de advertencia
 */
function mostrarAdvertencia(mensaje, titulo = 'Advertencia') {
    return Swal.fire({
        icon: 'warning',
        title: titulo,
        text: mensaje,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#00a8cc'
    });
}

/**
 * Mostrar mensaje de información
 */
function mostrarInfo(mensaje, titulo = 'Información') {
    return Swal.fire({
        icon: 'info',
        title: titulo,
        text: mensaje,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#00a8cc'
    });
}

/**
 * Confirmar acción
 */
function confirmarAccion(mensaje, titulo = '¿Está seguro?', textoConfirmar = 'Sí, continuar', textoCancelar = 'Cancelar') {
    return Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#00a8cc',
        cancelButtonColor: '#e53e3e',
        confirmButtonText: textoConfirmar,
        cancelButtonText: textoCancelar
    });
}

/**
 * Confirmar acción peligrosa
 */
function confirmarPeligro(mensaje, titulo = '¡Cuidado!', textoConfirmar = 'Sí, eliminar', textoCancelar = 'Cancelar') {
    return Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53e3e',
        cancelButtonColor: '#718096',
        confirmButtonText: textoConfirmar,
        cancelButtonText: textoCancelar,
        reverseButtons: true
    });
}

/**
 * Toast de éxito (notificación pequeña)
 */
function toastExito(mensaje) {
    Toast.fire({
        icon: 'success',
        title: mensaje
    });
}

/**
 * Toast de error
 */
function toastError(mensaje) {
    Toast.fire({
        icon: 'error',
        title: mensaje
    });
}

/**
 * Toast de información
 */
function toastInfo(mensaje) {
    Toast.fire({
        icon: 'info',
        title: mensaje
    });
}

/**
 * Mostrar cargando
 */
function mostrarCargando(mensaje = 'Procesando...') {
    Swal.fire({
        title: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Cerrar cargando
 */
function cerrarCargando() {
    Swal.close();
}

/**
 * Prompt personalizado
 */
function solicitarInput(titulo, placeholder = '', tipo = 'text') {
    return Swal.fire({
        title: titulo,
        input: tipo,
        inputPlaceholder: placeholder,
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#00a8cc',
        inputValidator: (value) => {
            if (!value) {
                return 'Este campo es requerido';
            }
        }
    });
}

/**
 * Reemplazar alert() nativo
 */
window.alert = function (mensaje) {
    // Detectar tipo de mensaje por emojis
    if (mensaje.includes('✅') || mensaje.toLowerCase().includes('éxito') || mensaje.toLowerCase().includes('exitosamente')) {
        const textoLimpio = mensaje.replace(/✅|❌|⚠️|ℹ️/g, '').trim();
        mostrarExito(textoLimpio);
    } else if (mensaje.includes('❌') || mensaje.toLowerCase().includes('error')) {
        const textoLimpio = mensaje.replace(/✅|❌|⚠️|ℹ️/g, '').trim();
        mostrarError(textoLimpio);
    } else if (mensaje.includes('⚠️') || mensaje.toLowerCase().includes('advertencia')) {
        const textoLimpio = mensaje.replace(/✅|❌|⚠️|ℹ️/g, '').trim();
        mostrarAdvertencia(textoLimpio);
    } else {
        mostrarInfo(mensaje);
    }
};

/**
 * Reemplazar confirm() nativo
 */
const confirmOriginal = window.confirm;
window.confirm = function (mensaje) {
    // Para compatibilidad con código síncrono, usar el confirm original
    // pero mostrar advertencia en consola
    console.warn('Usando confirm() nativo. Considere usar confirmarAccion() para mejor UX');
    return confirmOriginal(mensaje);
};
