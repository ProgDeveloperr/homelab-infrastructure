'use strict';

const buscador = document.querySelector('#buscar-proyecto');
const botonesFiltro = [...document.querySelectorAll('[data-filtro]')];
const tarjetas = [...document.querySelectorAll('.tarjeta-proyecto')];
const contador = document.querySelector('#contador-resultados');
const sinResultados = document.querySelector('#sin-resultados');

let filtroActual = 'todos';

function normalizar(texto) {
    return texto
        .toLocaleLowerCase('es')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function actualizarProyectos() {
    const busqueda = normalizar(buscador?.value.trim() ?? '');
    let visibles = 0;

    tarjetas.forEach((tarjeta) => {
        const contenido = normalizar(
            `${tarjeta.dataset.nombre ?? ''} ${tarjeta.dataset.tecnologias ?? ''}`
        );

        const coincideBusqueda = contenido.includes(busqueda);

        const coincideFiltro =
            filtroActual === 'todos'
            || (filtroActual === 'git' && tarjeta.dataset.git === 'si')
            || (filtroActual === 'bd' && tarjeta.dataset.bd === 'si');

        const visible = coincideBusqueda && coincideFiltro;

        tarjeta.hidden = !visible;

        if (visible) {
            visibles++;
        }
    });

    if (contador) {
        contador.textContent =
            `${visibles} ${visibles === 1 ? 'proyecto' : 'proyectos'}`;
    }

    if (sinResultados) {
        sinResultados.hidden = visibles !== 0;
    }
}

buscador?.addEventListener('input', actualizarProyectos);

botonesFiltro.forEach((boton) => {
    boton.addEventListener('click', () => {
        filtroActual = boton.dataset.filtro ?? 'todos';

        botonesFiltro.forEach((otroBoton) => {
            otroBoton.classList.toggle(
                'activo',
                otroBoton === boton
            );
        });

        actualizarProyectos();
    });
});

const modalDetalles = document.querySelector('#modal-detalles');
const cerrarModalDetalles = document.querySelector('#cerrar-modal');

const camposDetalle = {
    nombre: document.querySelector('#detalle-nombre'),
    descripcion: document.querySelector('#detalle-descripcion'),
    carpeta: document.querySelector('#detalle-carpeta'),
    archivos: document.querySelector('#detalle-archivos'),
    tamano: document.querySelector('#detalle-tamano'),
    tecnologias: document.querySelector('#detalle-tecnologias'),
    git: document.querySelector('#detalle-git'),
    baseDatos: document.querySelector('#detalle-base-datos'),
    fecha: document.querySelector('#detalle-fecha')
};

document.querySelectorAll('.boton-detalles').forEach((boton) => {
    boton.addEventListener('click', () => {
        camposDetalle.nombre.textContent = boton.dataset.nombre ?? '';
        camposDetalle.descripcion.textContent =
            boton.dataset.descripcion ?? '';
        camposDetalle.carpeta.textContent = boton.dataset.carpeta ?? '';
        camposDetalle.archivos.textContent = boton.dataset.archivos ?? '';
        camposDetalle.tamano.textContent = boton.dataset.tamano ?? '';
        camposDetalle.tecnologias.textContent =
            boton.dataset.tecnologias ?? '';
        camposDetalle.git.textContent = boton.dataset.git ?? '';
        camposDetalle.baseDatos.textContent =
            boton.dataset.baseDatos ?? '';
        camposDetalle.fecha.textContent = boton.dataset.fecha ?? '';

        if (
            modalDetalles
            && typeof modalDetalles.showModal === 'function'
            && !modalDetalles.open
        ) {
            modalDetalles.showModal();
        }
    });
});

cerrarModalDetalles?.addEventListener('click', () => {
    modalDetalles?.close();
});

const modalProyecto = document.querySelector('#modal-nuevo-proyecto');
const botonesAbrirProyecto = document.querySelectorAll(
    '[data-abrir-nuevo-proyecto]'
);
const cerrarModalProyecto = document.querySelector(
    '#cerrar-modal-proyecto'
);
const cancelarProyecto = document.querySelector('#cancelar-proyecto');
const formularioProyecto = document.querySelector(
    '#formulario-nuevo-proyecto'
);
const nombreProyecto = document.querySelector('#nombre-proyecto');
const descripcionProyecto = document.querySelector(
    '#descripcion-proyecto'
);
const contadorNombre = document.querySelector('#contador-nombre');
const contadorDescripcion = document.querySelector(
    '#contador-descripcion'
);
const vistaSlug = document.querySelector('#vista-slug');
const confirmarProyecto = document.querySelector('#confirmar-proyecto');

let elementoAnteriorAlModal = null;

function generarSlugVista(nombre) {
    const slug = normalizar(nombre)
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return slug || 'nuevo-proyecto';
}

function actualizarFormulario() {
    const nombre = nombreProyecto?.value ?? '';
    const descripcion = descripcionProyecto?.value ?? '';

    if (contadorNombre) {
        contadorNombre.textContent = String([...nombre].length);
    }

    if (contadorDescripcion) {
        contadorDescripcion.textContent =
            String([...descripcion].length);
    }

    if (vistaSlug) {
        vistaSlug.textContent = generarSlugVista(nombre);
    }
}

function actualizarPlantillas() {
    document.querySelectorAll('.tarjeta-plantilla').forEach(
        (tarjeta) => {
            const radio = tarjeta.querySelector(
                'input[type="radio"]'
            );

            tarjeta.classList.toggle(
                'seleccionada',
                Boolean(radio?.checked)
            );
        }
    );
}

function abrirModalProyecto() {
    if (
        !modalProyecto
        || typeof modalProyecto.showModal !== 'function'
        || modalProyecto.open
    ) {
        return;
    }

    elementoAnteriorAlModal = document.activeElement;
    modalProyecto.showModal();
    actualizarFormulario();
    actualizarPlantillas();

    window.setTimeout(() => {
        nombreProyecto?.focus();
    }, 80);
}

function cerrarProyecto() {
    if (!modalProyecto?.open) {
        return;
    }

    modalProyecto.close();

    if (elementoAnteriorAlModal instanceof HTMLElement) {
        elementoAnteriorAlModal.focus();
    }
}

botonesAbrirProyecto.forEach((boton) => {
    boton.addEventListener('click', abrirModalProyecto);
});

cerrarModalProyecto?.addEventListener('click', cerrarProyecto);
cancelarProyecto?.addEventListener('click', cerrarProyecto);

nombreProyecto?.addEventListener('input', actualizarFormulario);
descripcionProyecto?.addEventListener('input', actualizarFormulario);

document.querySelectorAll(
    '.tarjeta-plantilla input[type="radio"]'
).forEach((radio) => {
    radio.addEventListener('change', actualizarPlantillas);
});

formularioProyecto?.addEventListener('submit', () => {
    if (!confirmarProyecto) {
        return;
    }

    confirmarProyecto.disabled = true;
    confirmarProyecto.classList.add('procesando');
    confirmarProyecto.textContent = 'Creando proyecto…';
});

document.querySelectorAll('.modal').forEach((dialogo) => {
    dialogo.addEventListener('click', (evento) => {
        if (evento.target === dialogo) {
            dialogo.close();
        }
    });
});

document.querySelectorAll('.mensaje-cerrar').forEach((boton) => {
    boton.addEventListener('click', () => {
        boton.closest('.mensaje-panel')?.remove();
    });
});

actualizarFormulario();
actualizarPlantillas();

const mensajeConFormulario = document.querySelector(
    '.mensaje-panel[data-abrir-formulario="si"]'
);

if (mensajeConFormulario) {
    abrirModalProyecto();
}
