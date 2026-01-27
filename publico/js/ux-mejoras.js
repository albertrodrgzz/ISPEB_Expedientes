/**
 * UX Mejoras - Sistema ISPEB v3.0
 * Feedback visual para formularios y prevención de doble clic
 * 
 * @author Sistema ISPEB
 * @version 3.0
 */

(function() {
    'use strict';
    
    /**
     * Inicializar mejoras de UX cuando el DOM esté listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        inicializarFormularios();
        console.log('✅ UX Mejoras: Sistema de feedback de formularios activado');
    });
    
    /**
     * Detectar y mejorar todos los formularios del sistema
     */
    function inicializarFormularios() {
        const formularios = document.querySelectorAll('form');
        let formulariosInicializados = 0;
        
        formularios.forEach(form => {
            // Ignorar formularios de búsqueda, filtros o marcados explícitamente
            if (form.classList.contains('no-loading') || 
                form.method.toLowerCase() === 'get' ||
                form.hasAttribute('data-no-loading')) {
                return;
            }
            
            form.addEventListener('submit', function(e) {
                const submitButton = encontrarBotonSubmit(form);
                
                if (submitButton) {
                    // Prevenir doble envío
                    if (submitButton.disabled) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Aplicar estado de carga
                    aplicarEstadoCarga(submitButton);
                }
            });
            
            formulariosInicializados++;
        });
        
        console.log(`📋 ${formulariosInicializados} formularios con feedback automático`);
    }
    
    /**
     * Encontrar el botón de submit en un formulario
     * @param {HTMLFormElement} form - Formulario
     * @returns {HTMLButtonElement|null}
     */
    function encontrarBotonSubmit(form) {
        // Buscar botón type="submit"
        let submitButton = form.querySelector('button[type="submit"]');
        
        // Si no hay, buscar cualquier botón sin type (por defecto es submit)
        if (!submitButton) {
            const botones = form.querySelectorAll('button:not([type="button"]):not([type="reset"])');
            if (botones.length > 0) {
                submitButton = botones[botones.length - 1]; // Último botón
            }
        }
        
        // Si aún no hay, buscar input type="submit"
        if (!submitButton) {
            submitButton = form.querySelector('input[type="submit"]');
        }
        
        return submitButton;
    }
    
    /**
     * Aplicar estado de carga al botón
     * @param {HTMLElement} button - Botón a modificar
     */
    function aplicarEstadoCarga(button) {
        // Guardar texto original y ancho
        button.dataset.originalText = button.innerHTML;
        button.dataset.originalWidth = button.offsetWidth + 'px';
        
        // Deshabilitar botón
        button.disabled = true;
        button.style.minWidth = button.dataset.originalWidth;
        button.style.cursor = 'not-allowed';
        button.style.opacity = '0.7';
        button.style.pointerEvents = 'none';
        
        // Cambiar contenido con spinner
        button.innerHTML = `
            <svg class="spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite; display: inline-block; vertical-align: middle; margin-right: 8px;">
                <circle cx="12" cy="12" r="10" opacity="0.25"></circle>
                <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path>
            </svg>
            <span>Procesando...</span>
        `;
        
        // Agregar animación de spinner si no existe
        if (!document.getElementById('spinner-keyframes')) {
            const style = document.createElement('style');
            style.id = 'spinner-keyframes';
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    /**
     * Restaurar estado original del botón (útil para validaciones AJAX)
     * @param {HTMLElement} button - Botón a restaurar
     */
    window.restaurarBoton = function(button) {
        if (button && button.dataset.originalText) {
            button.disabled = false;
            button.style.cursor = '';
            button.style.opacity = '';
            button.style.pointerEvents = '';
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
            delete button.dataset.originalWidth;
        }
    };
    
    /**
     * Mostrar mensaje de éxito temporal (toast)
     * @param {string} mensaje - Mensaje a mostrar
     * @param {number} duracion - Duración en ms (default: 3000)
     */
    window.mostrarMensajeExito = function(mensaje, duracion = 3000) {
        const toast = document.createElement('div');
        toast.className = 'toast toast-success';
        toast.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span>${mensaje}</span>
        `;
        
        // Agregar estilos si no existen
        if (!document.getElementById('toast-styles')) {
            agregarEstilosToast();
        }
        
        document.body.appendChild(toast);
        
        // Remover después de la duración
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duracion);
    };
    
    /**
     * Mostrar mensaje de error temporal (toast)
     * @param {string} mensaje - Mensaje a mostrar
     * @param {number} duracion - Duración en ms (default: 4000)
     */
    window.mostrarMensajeError = function(mensaje, duracion = 4000) {
        const toast = document.createElement('div');
        toast.className = 'toast toast-error';
        toast.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <span>${mensaje}</span>
        `;
        
        // Agregar estilos si no existen
        if (!document.getElementById('toast-styles')) {
            agregarEstilosToast();
        }
        
        document.body.appendChild(toast);
        
        // Remover después de la duración
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duracion);
    };
    
    /**
     * Agregar estilos para los toasts
     */
    function agregarEstilosToast() {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                top: 24px;
                right: 24px;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 12px 32px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 500;
                z-index: 10000;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            }
            .toast-success {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
            }
            .toast-error {
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white;
            }
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Validar formulario con feedback visual
     * Útil para validaciones personalizadas antes del envío
     * 
     * @param {HTMLFormElement} form - Formulario a validar
     * @param {Function} validacionCallback - Función que retorna true si es válido
     * @returns {boolean}
     */
    window.validarFormularioConFeedback = function(form, validacionCallback) {
        const submitButton = encontrarBotonSubmit(form);
        
        if (!validacionCallback()) {
            // Si la validación falla, restaurar el botón
            if (submitButton) {
                restaurarBoton(submitButton);
            }
            return false;
        }
        
        return true;
    };
    
})();
