'use strict';

const reloj = document.querySelector('[data-reloj]');
const botonRecargar = document.querySelector('[data-recargar]');

function actualizarReloj() {
    if (!reloj) {
        return;
    }

    reloj.textContent = new Intl.DateTimeFormat('es-AR', {
        timeZone: 'America/Argentina/Buenos_Aires',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).format(new Date());
}

botonRecargar?.addEventListener('click', () => {
    window.location.reload();
});

actualizarReloj();
window.setInterval(actualizarReloj, 1000);
