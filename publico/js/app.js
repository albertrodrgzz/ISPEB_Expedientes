/**
 * JavaScript Principal
 * Sistema de Gestión de Expedientes Digitales - ISPEB
 */

document.addEventListener('DOMContentLoaded', function () {
    // Búsqueda en tiempo real
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

    // NOTA: El código del menú hamburguesa ahora está centralizado en sidebar.php
    // No duplicar aquí para evitar conflictos
});
