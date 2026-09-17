<section
    class="cmm-requests"
    aria-labelledby="cmm-requests-title"
>
    <div class="cmm-page-heading cmm-requests-heading">
        <div>
            <h2 id="cmm-requests-title">
                Solicitudes
            </h2>

            <p>
                Seguimiento de solicitudes y de su estado actual
                dentro del pipeline multimedia.
            </p>
        </div>

        <div
            id="cmm-requests-status"
            class="cmm-requests-status cmm-requests-status--loading"
            role="status"
            aria-live="polite"
        >
            <span
                class="cmm-requests-status-dot"
                aria-hidden="true"
            ></span>

            <span class="cmm-requests-status-copy">
                <strong id="cmm-requests-status-value">
                    —
                </strong>

                <span id="cmm-requests-status-text">
                    Consultando solicitudes…
                </span>
            </span>
        </div>
    </div>


    <section
        class="cmm-requests-summary"
        aria-label="Resumen de solicitudes"
    >
        <article class="cmm-requests-metric">
            <span>Total</span>
            <strong id="cmm-requests-total">—</strong>
            <small>Solicitudes conocidas</small>
        </article>

        <article class="cmm-requests-metric cmm-requests-metric--available">
            <span>Disponibles</span>
            <strong id="cmm-requests-available">—</strong>
            <small>Ya accesibles en la biblioteca</small>
        </article>

        <article class="cmm-requests-metric cmm-requests-metric--progress">
            <span>En curso</span>
            <strong id="cmm-requests-in-progress">—</strong>
            <small>Descarga o importación activa</small>
        </article>

        <article class="cmm-requests-metric cmm-requests-metric--waiting">
            <span>Esperando</span>
            <strong id="cmm-requests-waiting">—</strong>
            <small>Pendientes de avanzar</small>
        </article>

        <article class="cmm-requests-metric cmm-requests-metric--attention">
            <span>Atención</span>
            <strong id="cmm-requests-attention">—</strong>
            <small>Requieren revisión</small>
        </article>
    </section>


    <section
        class="cmm-requests-toolbar"
        aria-label="Filtros de solicitudes"
    >
        <label class="cmm-requests-field cmm-requests-field--search">
            <span>Buscar</span>

            <input
                id="cmm-requests-search"
                type="search"
                autocomplete="off"
                placeholder="Título…"
            >
        </label>


        <label class="cmm-requests-field">
            <span>Etapa</span>

            <select id="cmm-requests-stage">
                <option value="">Todas</option>
                <option value="REQUESTED">Solicitud recibida</option>
                <option value="ARR_RECEIVED">Recibida por Arr</option>
                <option value="GRABBED">Encontrada</option>
                <option value="QUEUED">En cola</option>
                <option value="DOWNLOADING">Descargando</option>
                <option value="DOWNLOAD_COMPLETE">Descarga completa</option>
                <option value="IMPORTED">Importada</option>
                <option value="AVAILABLE">Disponible</option>
                <option value="FAILED">Requiere atención</option>
            </select>
        </label>


        <label class="cmm-requests-field">
            <span>Tipo</span>

            <select id="cmm-requests-type">
                <option value="">Todos</option>
                <option value="movie">Películas</option>
                <option value="tv">Series</option>
            </select>
        </label>


        <button
            id="cmm-requests-reset"
            class="cmm-requests-reset"
            type="button"
        >
            Limpiar filtros
        </button>
    </section>


    <div
        id="cmm-requests-error"
        class="cmm-error"
        role="alert"
        hidden
    ></div>


    <!-- CMM_K7D9B_R1D_R2_BEGIN -->

    <div class="cmm-requests-list-heading">
        <div>
            <strong>Propuestas de eliminación</strong>

            <span>
                Acciones locales registradas por CMM.
                Esta sección es sólo lectura.
            </span>
        </div>

        <strong id="cmm-delete-proposals-count">
            —
        </strong>
    </div>


    <div
        id="cmm-delete-proposals-error"
        class="cmm-error"
        role="alert"
        hidden
    ></div>


    <div
        id="cmm-delete-proposals-list"
        class="cmm-requests-list"
        aria-live="polite"
    ></div>


    <div
        id="cmm-delete-proposals-empty"
        class="cmm-requests-empty"
        hidden
    >
        <strong>No hay propuestas de eliminación.</strong>

        <span>
            No existen acciones DELETE registradas
            por el flujo productivo actual.
        </span>
    </div>


    <!-- CMM_K7D9B_R1D_R2_END -->


    <div class="cmm-requests-list-heading">
        <div>
            <strong>Pipeline actual</strong>

            <span>
                Estado derivado de Seerr, Arr, qBittorrent,
                filesystem y Jellyfin.
            </span>
        </div>

        <strong id="cmm-requests-result-count">
            —
        </strong>
    </div>


    <div
        id="cmm-requests-list"
        class="cmm-requests-list"
        aria-live="polite"
    ></div>


    <div
        id="cmm-requests-empty"
        class="cmm-requests-empty"
        hidden
    >
        <strong>No hay solicitudes para estos filtros.</strong>

        <span>
            Modificá la búsqueda o limpiá los filtros.
        </span>
    </div>


    <div class="cmm-note cmm-requests-note">
        <strong>Modo observación.</strong>

        <p>
            Esta vista representa el estado actual del pipeline.
            No ejecuta búsquedas, descargas, importaciones ni eliminaciones.
        </p>
    </div>


    <noscript>
        <div class="cmm-error">
            Solicitudes requiere JavaScript.
        </div>
    </noscript>
</section>

<script
    src="recursos/js/solicitudes.js?v=7"
    defer
></script>
