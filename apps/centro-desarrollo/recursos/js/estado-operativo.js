(() => {
    'use strict';

    const nodoDatos = document.getElementById('estado-operativo-datos');
    const modal = document.getElementById('modal-estado-operativo');
    const titulo = document.getElementById('estado-operativo-titulo');
    const tipo = document.getElementById('estado-operativo-tipo');
    const cuerpo = document.getElementById('estado-operativo-cuerpo');
    const fecha = document.getElementById('estado-operativo-fecha');

    if (!nodoDatos || !modal || !titulo || !tipo || !cuerpo || !fecha) {
        return;
    }

    let estado;

    try {
        estado = JSON.parse(nodoDatos.textContent);
    } catch (error) {
        console.error('No se pudo interpretar el estado operativo.', error);
        return;
    }

    const escapar = (valor) => String(valor ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const formatearBytes = (bytes) => {
        const cantidad = Number(bytes || 0);

        if (cantidad < 1024) {
            return `${cantidad} B`;
        }

        if (cantidad < 1024 ** 2) {
            return `${(cantidad / 1024).toFixed(1)} KB`;
        }

        if (cantidad < 1024 ** 3) {
            return `${(cantidad / 1024 ** 2).toFixed(1)} MB`;
        }

        return `${(cantidad / 1024 ** 3).toFixed(2)} GB`;
    };

    const formatearFecha = (valor) => {
        if (!valor) {
            return 'Sin fecha';
        }

        const objeto = new Date(valor);

        if (Number.isNaN(objeto.getTime())) {
            return escapar(valor);
        }

        return objeto.toLocaleString('es-AR');
    };

    const fila = (nombre, valor) => `
        <div class="estado-operativo-fila">
            <span>${escapar(nombre)}</span>
            <strong>${escapar(valor)}</strong>
        </div>
    `;

    const obtenerProyecto = (carpeta) =>
        (estado.proyectos || []).find(
            (proyecto) => proyecto.carpeta === carpeta
        );

    const obtenerBackup = (carpeta) =>
        (estado.backups?.proyectos || []).find(
            (proyecto) => proyecto.carpeta === carpeta
        );

    const mostrarGit = (proyecto) => {
        const git = proyecto.git || {};
        const commit = git.ultimo_commit;

        let html = `
            <div class="estado-operativo-resumen">
                ${fila('Rama', git.rama || 'Desconocida')}
                ${fila('Estado', git.estado || 'Desconocido')}
                ${fila('Cambios pendientes', git.cambios || 0)}
            </div>
        `;

        if (commit) {
            html += `
                <section class="estado-operativo-bloque">
                    <h3>Último commit</h3>
                    ${fila('Hash', commit.hash)}
                    ${fila('Autor', commit.autor)}
                    ${fila('Fecha', formatearFecha(commit.fecha))}
                    <p class="estado-operativo-mensaje">
                        ${escapar(commit.mensaje)}
                    </p>
                </section>
            `;
        }

        return html;
    };

    const mostrarLogs = (proyecto) => {
        const logs = proyecto.logs || [];

        if (!logs.length) {
            return '<p class="estado-operativo-vacio">No hay logs detectados.</p>';
        }

        return `
            <div class="estado-operativo-lista">
                ${logs.map((log) => `
                    <article class="estado-operativo-item">
                        <strong>${escapar(log.ruta)}</strong>
                        <span>
                            ${formatearBytes(log.tamano)}
                            · ${formatearFecha(log.modificacion)}
                        </span>
                    </article>
                `).join('')}
            </div>
        `;
    };

    const mostrarDocker = (proyecto) => {
        const nombres = proyecto.contenedores || [];
        const todos = estado.docker?.contenedores || [];
        const contenedores = todos.filter(
            (contenedor) => nombres.includes(contenedor.nombre)
        );

        if (!contenedores.length) {
            return `
                <p class="estado-operativo-vacio">
                    No hay contenedores relacionados.
                </p>
            `;
        }

        return `
            <div class="estado-operativo-lista">
                ${contenedores.map((contenedor) => `
                    <article class="estado-operativo-item">
                        <div class="estado-operativo-item-cabecera">
                            <strong>${escapar(contenedor.nombre)}</strong>
                            <span class="estado-operativo-badge">
                                ${escapar(contenedor.estado)}
                            </span>
                        </div>
                        <span>${escapar(contenedor.imagen)}</span>
                        <small>
                            Salud: ${escapar(contenedor.salud)}
                            · Reinicios: ${escapar(contenedor.reinicios)}
                        </small>
                    </article>
                `).join('')}
            </div>
        `;
    };

    const mostrarBackup = (carpeta) => {
        const backup = obtenerBackup(carpeta);

        if (!backup) {
            return `
                <p class="estado-operativo-vacio">
                    Todavía no existe un backup individual para este proyecto.
                </p>
            `;
        }

        const correcto = backup.estado === 'correcto'
            && backup.checksum === 'correcto';

        return `
            <div class="estado-operativo-resumen">
                ${fila('Estado', correcto ? 'Correcto' : 'Revisar')}
                ${fila('Última copia', formatearFecha(backup.fecha))}
                ${fila('Tamaño', formatearBytes(backup.tamano_bytes))}
                ${fila('Checksum', backup.checksum || 'Sin verificar')}
            </div>

            <section class="estado-operativo-bloque">
                <h3>Archivo generado</h3>

                ${fila('Nombre', backup.archivo || 'Sin información')}

                <p class="estado-operativo-ruta">
                    ${escapar(backup.ruta || 'Ruta no disponible')}
                </p>
            </section>

            <section class="estado-operativo-bloque">
                <h3>Política del respaldo</h3>

                ${fila(
                    'Retención',
                    estado.backups?.retencion_dias
                        ? `${estado.backups.retencion_dias} días`
                        : 'Sin información'
                )}

                ${fila(
                    'Duración de la ejecución',
                    estado.backups?.duracion_segundos !== null
                        ? `${estado.backups.duracion_segundos} segundos`
                        : 'Sin información'
                )}
            </section>
        `;
    };

    document.querySelectorAll('[data-panel-operativo]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const carpeta = boton.dataset.proyecto;
            const seccion = boton.dataset.panelOperativo;
            const nombre = boton.dataset.nombre || carpeta;
            const proyecto = obtenerProyecto(carpeta);

            if (seccion !== 'backup' && !proyecto) {
                return;
            }

            titulo.textContent = nombre;
            tipo.textContent = seccion.toUpperCase();

            if (seccion === 'git') {
                cuerpo.innerHTML = mostrarGit(proyecto);
            } else if (seccion === 'logs') {
                cuerpo.innerHTML = mostrarLogs(proyecto);
            } else if (seccion === 'docker') {
                cuerpo.innerHTML = mostrarDocker(proyecto);
            } else {
                cuerpo.innerHTML = mostrarBackup(carpeta);
            }

            const fechaEstado = seccion === 'backup'
                ? estado.backups?.fecha_final
                : estado.generado;

            fecha.textContent = fechaEstado
                ? `Actualizado: ${formatearFecha(fechaEstado)}`
                : 'Actualización pendiente';

            if (typeof modal.showModal === 'function') {
                modal.showModal();
            } else {
                modal.setAttribute('open', '');
            }
        });
    });

    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) {
            modal.close();
        }
    });
})();
