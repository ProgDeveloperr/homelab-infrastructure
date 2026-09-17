<section
    id="cmm-library-root"
    class="cmm-library"
    aria-labelledby="cmm-library-title"
>
    <header class="cmm-page-heading">
        <div>
            <span class="cmm-library-eyebrow">
                Catálogo consolidado
            </span>

            <h2 id="cmm-library-title">
                Biblioteca
            </h2>

            <p>
                Películas y series detectadas por CMM.
                Vista administrativa de solo lectura.
            </p>
        </div>

        <div
            id="cmm-library-status"
            class="cmm-library-status cmm-library-status--loading"
            role="status"
            aria-live="polite"
        >
            <span
                class="cmm-library-status-dot"
                aria-hidden="true"
            ></span>

            <div>
                <strong id="cmm-library-status-count">
                    —
                </strong>

                <span id="cmm-library-status-text">
                    Consultando biblioteca…
                </span>
            </div>
        </div>
    </header>


    <section
        class="cmm-library-summary"
        aria-label="Resumen de biblioteca"
    >
        <article class="cmm-library-metric">
            <span>Total</span>
            <strong id="cmm-library-total">—</strong>
            <small>elementos catalogados</small>
        </article>

        <article class="cmm-library-metric">
            <span>Disponibles</span>
            <strong id="cmm-library-available">—</strong>
            <small>presentes en filesystem</small>
        </article>

        <article class="cmm-library-metric">
            <span>Gestionados</span>
            <strong id="cmm-library-arr">—</strong>
            <small>Radarr / Sonarr</small>
        </article>

        <article class="cmm-library-metric">
            <span>Manuales</span>
            <strong id="cmm-library-manual">—</strong>
            <small>fuera de Arr</small>
        </article>
    </section>


    <section
        class="cmm-library-toolbar"
        aria-label="Filtros de biblioteca"
    >
        <label
            class="cmm-library-field cmm-library-field--search"
        >
            <span>Buscar</span>

            <input
                id="cmm-library-search"
                type="search"
                placeholder="Título…"
                autocomplete="off"
                spellcheck="false"
            >
        </label>

        <label class="cmm-library-field">
            <span>Tipo</span>

            <select id="cmm-library-type">
                <option value="">Todos</option>
                <option value="movie">Películas</option>
                <option value="series">Series</option>
            </select>
        </label>

        <label class="cmm-library-field">
            <span>Origen</span>

            <select id="cmm-library-ownership">
                <option value="">Todos</option>
                <option value="ARR">Gestionados</option>
                <option value="MANUAL">Manuales</option>
            </select>
        </label>

        <label class="cmm-library-field">
            <span>Estado</span>

            <select id="cmm-library-state">
                <option value="">Todos</option>
            </select>
        </label>

        <button
            id="cmm-library-reset"
            class="cmm-library-reset"
            type="button"
        >
            Limpiar filtros
        </button>
    </section>


    <div class="cmm-library-result-bar">
        <span id="cmm-library-result-count">
            Esperando datos…
        </span>

        <span class="cmm-library-readonly">
            Solo lectura
        </span>
    </div>


    <div
        id="cmm-library-error"
        class="cmm-error"
        role="alert"
        hidden
    ></div>


    <div
        id="cmm-library-grid"
        class="cmm-library-grid"
        aria-live="polite"
    ></div>


    <div
        id="cmm-library-empty"
        class="cmm-library-empty"
        hidden
    >
        <strong>
            No hay elementos para estos filtros.
        </strong>

        <span>
            Modificá la búsqueda o limpiá los filtros.
        </span>
    </div>


    <div class="cmm-note cmm-library-note">
        <strong>
            Acciones deshabilitadas
        </strong>

        <p>
            Esta etapa sólo publica inventario y estado.
            No modifica archivos ni servicios multimedia.
        </p>
    </div>


    <noscript>
        <div class="cmm-error">
            Biblioteca requiere JavaScript.
        </div>
    </noscript>
</section>

<script
    src="recursos/js/biblioteca.js?v=2"
></script>
