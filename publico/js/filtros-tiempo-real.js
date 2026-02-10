/**
 * Filtros en Tiempo Real para Tablas HTML
 * Sistema ISPEB - Gestión de Expedientes Digitales
 * 
 * Proporciona funcionalidad reutilizable para filtrar tablas dinámicamente
 * sin recargar la página, con soporte para búsqueda por texto y selectores.
 */

/**
 * Inicializa filtros en tiempo real para una tabla
 * 
 * @param {Object} config - Configuración del filtro
 * @param {string} config.tableId - ID de la tabla a filtrar
 * @param {string} config.searchInputId - ID del input de búsqueda de texto
 * @param {Array} config.selectFilters - Array de objetos con selectores {id, dataAttribute}
 * @param {string} config.rowSelector - Selector CSS para las filas (default: 'tbody tr')
 * @param {Function} config.onFilter - Callback opcional ejecutado después de filtrar
 * 
 * @example
 * initTableFilters({
 *     tableId: 'funcionariosTable',
 *     searchInputId: 'searchInput',
 *     selectFilters: [
 *         { id: 'filter-departamento', dataAttribute: 'departamento' },
 *         { id: 'filter-estado', dataAttribute: 'estado' }
 *     ],
 *     onFilter: (visibleCount) => console.log(`${visibleCount} registros visibles`)
 * });
 */
function initTableFilters(config) {
    const {
        tableId,
        searchInputId,
        selectFilters = [],
        rowSelector = 'tbody tr',
        onFilter = null
    } = config;

    // Validar configuración
    if (!tableId) {
        console.error('initTableFilters: tableId es requerido');
        return;
    }

    const table = document.getElementById(tableId);
    if (!table) {
        console.error(`initTableFilters: No se encontró tabla con ID "${tableId}"`);
        return;
    }

    const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
    const rows = table.querySelectorAll(rowSelector);

    if (rows.length === 0) {
        console.warn(`initTableFilters: No se encontraron filas con selector "${rowSelector}"`);
    }

    /**
     * Función principal de filtrado
     */
    function applyFilters() {
        // Obtener valor de búsqueda
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

        // Obtener valores de selectores
        const selectValues = {};
        selectFilters.forEach(filter => {
            const select = document.getElementById(filter.id);
            if (select) {
                selectValues[filter.dataAttribute] = select.value;
            }
        });

        // Contador de filas visibles
        let visibleCount = 0;

        // Filtrar cada fila
        rows.forEach(row => {
            let shouldShow = true;

            // Filtro de búsqueda por texto
            if (searchTerm && searchInput) {
                const rowText = row.textContent.toLowerCase();
                if (!rowText.includes(searchTerm)) {
                    shouldShow = false;
                }
            }

            // Filtros de selectores
            if (shouldShow && selectFilters.length > 0) {
                for (const filter of selectFilters) {
                    const filterValue = selectValues[filter.dataAttribute];
                    if (filterValue) {
                        const rowValue = row.dataset[filter.dataAttribute];
                        if (rowValue !== filterValue) {
                            shouldShow = false;
                            break;
                        }
                    }
                }
            }

            // Mostrar u ocultar fila
            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });

        // Ejecutar callback si existe
        if (onFilter && typeof onFilter === 'function') {
            onFilter(visibleCount, rows.length);
        }

        return visibleCount;
    }

    /**
     * Función de debounce para optimizar búsqueda en tiempo real
     */
    function debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Agregar event listeners
    if (searchInput) {
        // Usar debounce para búsqueda de texto (evitar demasiadas ejecuciones)
        searchInput.addEventListener('input', debounce(applyFilters, 250));
    }

    // Los selectores cambian inmediatamente (sin debounce)
    selectFilters.forEach(filter => {
        const select = document.getElementById(filter.id);
        if (select) {
            select.addEventListener('change', applyFilters);
        } else {
            console.warn(`initTableFilters: No se encontró selector con ID "${filter.id}"`);
        }
    });

    // Retornar objeto con métodos útiles
    return {
        applyFilters,
        clearFilters: function() {
            if (searchInput) searchInput.value = '';
            selectFilters.forEach(filter => {
                const select = document.getElementById(filter.id);
                if (select) select.value = '';
            });
            applyFilters();
        },
        getVisibleCount: function() {
            return Array.from(rows).filter(row => row.style.display !== 'none').length;
        },
        getTotalCount: function() {
            return rows.length;
        }
    };
}


/**
 * Versión simplificada para tablas básicas (solo búsqueda de texto)
 * 
 * @param {string} searchInputId - ID del input de búsqueda
 * @param {string} tableId - ID de la tabla
 * @param {string} rowSelector - Selector de filas (default: 'tbody tr')
 * 
 * @example
 * initSimpleTableSearch('searchBox', 'myTable');
 */
function initSimpleTableSearch(searchInputId, tableId, rowSelector = 'tbody tr') {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);

    if (!searchInput || !table) {
        console.error('initSimpleTableSearch: Elementos no encontrados', {
            searchInput: !!searchInput,
            table: !!table
        });
        return;
    }

    const rows = table.querySelectorAll(rowSelector);

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    return {
        clear: () => {
            searchInput.value = '';
            rows.forEach(row => row.style.display = '');
        }
    };
}


/**
 * Función helper para limpiar todos los filtros de una tabla
 * 
 * @param {string} formId - ID del formulario que contiene los filtros
 */
function clearAllFilters(formId) {
    const form = document.getElementById(formId);
    if (!form) {
        console.error(`clearAllFilters: No se encontró formulario con ID "${formId}"`);
        return;
    }

    // Limpiar todos los inputs y selects
    const inputs = form.querySelectorAll('input[type="text"], input[type="search"]');
    inputs.forEach(input => input.value = '');

    const selects = form.querySelectorAll('select');
    selects.forEach(select => select.value = '');

    // Disparar evento change para que se apliquen los filtros vacíos
    const event = new Event('change', { bubbles: true });
    form.dispatchEvent(event);
}


/**
 * Resaltar términos de búsqueda en la tabla (opcional)
 * 
 * @param {HTMLElement} row - Fila de la tabla
 * @param {string} searchTerm - Término a resaltar
 */
function highlightSearchTerm(row, searchTerm) {
    if (!searchTerm) return;

    const cells = row.querySelectorAll('td');
    const regex = new RegExp(`(${searchTerm})`, 'gi');

    cells.forEach(cell => {
        const originalText = cell.textContent;
        const highlightedText = originalText.replace(regex, '<mark>$1</mark>');
        if (originalText !== highlightedText) {
            cell.innerHTML = highlightedText;
        }
    });
}


/**
 * Contador de resultados visible para el usuario
 * 
 * @param {string} counterId - ID del elemento donde mostrar el contador
 * @param {number} visible - Número de filas visibles
 * @param {number} total - Número total de filas
 */
function updateResultCounter(counterId, visible, total) {
    const counter = document.getElementById(counterId);
    if (counter) {
        counter.textContent = `Mostrando ${visible} de ${total} registros`;
    }
}


// Exportar para uso global
if (typeof window !== 'undefined') {
    window.initTableFilters = initTableFilters;
    window.initSimpleTableSearch = initSimpleTableSearch;
    window.clearAllFilters = clearAllFilters;
    window.highlightSearchTerm = highlightSearchTerm;
    window.updateResultCounter = updateResultCounter;
}
