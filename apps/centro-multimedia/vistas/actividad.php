<section
    class="cmm-activity"
    aria-labelledby="cmm-activity-title"
>
    <div class="cmm-page-heading cmm-activity-heading">
        <div>
            <h2 id="cmm-activity-title">
                Actividad
            </h2>

            <p>
                Historial cronológico de solicitudes y descargas
                completadas dentro del flujo multimedia.
            </p>
        </div>

        <div
            id="cmm-activity-status"
            class="cmm-activity-status cmm-activity-status--loading"
            role="status"
            aria-live="polite"
        >
            <span
                class="cmm-activity-status-dot"
                aria-hidden="true"
            ></span>

            <span class="cmm-activity-status-copy">
                <strong id="cmm-activity-status-value">
                    —
                </strong>

                <span id="cmm-activity-status-text">
                    Consultando actividad…
                </span>
            </span>
        </div>
    </div>


    <section
        class="cmm-activity-summary"
        aria-label="Resumen de actividad"
    >
        <article class="cmm-activity-metric">
            <span>Total</span>

            <strong id="cmm-activity-total">
                —
            </strong>

            <small>Eventos conocidos</small>
        </article>

        <article
            class="cmm-activity-metric cmm-activity-metric--request"
        >
            <span>Solicitudes</span>

            <strong id="cmm-activity-requests">
                —
            </strong>

            <small>Solicitudes creadas</small>
        </article>

        <article
            class="cmm-activity-metric cmm-activity-metric--complete"
        >
            <span>Completadas</span>

            <strong id="cmm-activity-completed">
                —
            </strong>

            <small>Descargas finalizadas</small>
        </article>

        <article class="cmm-activity-metric">
            <span>Películas</span>

            <strong id="cmm-activity-movies">
                —
            </strong>

            <small>Eventos de películas</small>
        </article>

        <article class="cmm-activity-metric">
            <span>Series</span>

            <strong id="cmm-activity-series">
                —
            </strong>

            <small>Eventos de series</small>
        </article>
    </section>


    <section
        class="cmm-activity-toolbar"
        aria-label="Filtros de actividad"
    >
        <label
            class="cmm-activity-field cmm-activity-field--search"
        >
            <span>Buscar</span>

            <input
                id="cmm-activity-search"
                type="search"
                autocomplete="off"
                placeholder="Título…"
            >
        </label>


        <label class="cmm-activity-field">
            <span>Evento</span>

            <select id="cmm-activity-event">
                <option value="">
                    Todos
                </option>

                <option value="REQUEST_CREATED">
                    Solicitud creada
                </option>

                <option value="DOWNLOAD_COMPLETED">
                    Descarga completada
                </option>
            </select>
        </label>


        <label class="cmm-activity-field">
            <span>Tipo</span>

            <select id="cmm-activity-type">
                <option value="">
                    Todos
                </option>

                <option value="movie">
                    Películas
                </option>

                <option value="series">
                    Series
                </option>
            </select>
        </label>


        <button
            id="cmm-activity-reset"
            class="cmm-activity-reset"
            type="button"
        >
            Limpiar filtros
        </button>
    </section>


    <div
        id="cmm-activity-error"
        class="cmm-error"
        role="alert"
        hidden
    ></div>


    <div class="cmm-activity-list-heading">
        <div>
            <strong>
                Historial multimedia
            </strong>

            <span>
                Ordenado desde el evento más reciente.
            </span>
        </div>

        <strong id="cmm-activity-result-count">
            —
        </strong>
    </div>


    <div
        id="cmm-activity-list"
        class="cmm-activity-list"
        role="list"
        aria-live="polite"
    ></div>


    <div
        id="cmm-activity-empty"
        class="cmm-activity-empty"
        hidden
    >
        <strong>
            No hay actividad para estos filtros.
        </strong>

        <span>
            Modificá la búsqueda o limpiá los filtros.
        </span>
    </div>


    <div class="cmm-note cmm-activity-note">
        <strong>
            Historial derivado.
        </strong>

        <p>
            Esta vista muestra hechos con timestamp verificable:
            solicitudes creadas y descargas completadas.
            No interpreta refrescos internos como actividad
            y no ejecuta ninguna acción.
        </p>
    </div>


    <noscript>
        <div class="cmm-error">
            Actividad requiere JavaScript.
        </div>
    </noscript>
</section>

<script
    src="recursos/js/actividad.js?v=1"
    defer
></script>
