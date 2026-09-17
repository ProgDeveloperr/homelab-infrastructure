<section
    class="cmm-downloads"
    aria-labelledby="cmm-downloads-title"
>
    <header
        class="cmm-page-heading cmm-downloads-heading"
    >
        <div>
            <span class="cmm-eyebrow">
                Transferencias
            </span>

            <h2 id="cmm-downloads-title">
                Descargas
            </h2>

            <p>
                Estado actual de las transferencias
                correlacionadas con la biblioteca multimedia.
            </p>
        </div>

        <div
            id="cmm-downloads-status"
            class="cmm-downloads-status cmm-downloads-status--loading"
            aria-live="polite"
        >
            <span
                class="cmm-downloads-status-dot"
                aria-hidden="true"
            ></span>

            <div class="cmm-downloads-status-copy">
                <strong id="cmm-downloads-status-value">
                    —
                </strong>

                <span id="cmm-downloads-status-text">
                    Consultando datos…
                </span>
            </div>
        </div>
    </header>


    <div class="cmm-downloads-summary">
        <article class="cmm-downloads-metric">
            <span>Total</span>
            <strong id="cmm-downloads-total">—</strong>
            <small>Transferencias visibles</small>
        </article>

        <article
            class="cmm-downloads-metric cmm-downloads-metric--downloading"
        >
            <span>Descargando</span>
            <strong id="cmm-downloads-downloading">—</strong>
            <small>Transferencia activa</small>
        </article>

        <article
            class="cmm-downloads-metric cmm-downloads-metric--complete"
        >
            <span>Completadas</span>
            <strong id="cmm-downloads-complete">—</strong>
            <small>Progreso finalizado</small>
        </article>

        <article
            class="cmm-downloads-metric cmm-downloads-metric--waiting"
        >
            <span>En espera</span>
            <strong id="cmm-downloads-waiting">—</strong>
            <small>Pausadas o pendientes</small>
        </article>

        <article
            class="cmm-downloads-metric cmm-downloads-metric--attention"
        >
            <span>Atención</span>
            <strong id="cmm-downloads-attention">—</strong>
            <small>Requieren revisión</small>
        </article>
    </div>


    <div
        class="cmm-downloads-toolbar"
        aria-label="Filtros de descargas"
    >
        <label
            class="cmm-downloads-field cmm-downloads-field--search"
        >
            <span>Buscar</span>

            <input
                id="cmm-downloads-search"
                type="search"
                autocomplete="off"
                placeholder="Título…"
            >
        </label>

        <label class="cmm-downloads-field">
            <span>Estado</span>

            <select id="cmm-downloads-status-filter">
                <option value="">Todos</option>
                <option value="DOWNLOADING">Descargando</option>
                <option value="COMPLETE">Completadas</option>
                <option value="WAITING">En espera</option>
                <option value="ATTENTION">Atención</option>
            </select>
        </label>

        <label class="cmm-downloads-field">
            <span>Tipo</span>

            <select id="cmm-downloads-type">
                <option value="">Todos</option>
                <option value="movie">Películas</option>
                <option value="series">Series</option>
            </select>
        </label>

        <button
            id="cmm-downloads-reset"
            class="cmm-downloads-reset"
            type="button"
        >
            Limpiar
        </button>
    </div>


    <div class="cmm-downloads-list-heading">
        <div>
            <h3>Transferencias</h3>

            <p>
                Una tarjeta por descarga actualmente
                observada por qBittorrent.
            </p>
        </div>

        <span id="cmm-downloads-result-count">
            —
        </span>
    </div>


    <div
        id="cmm-downloads-error"
        class="cmm-error"
        role="alert"
        hidden
    ></div>


    <div
        id="cmm-downloads-list"
        class="cmm-downloads-list"
        aria-live="polite"
    ></div>


    <div
        id="cmm-downloads-empty"
        class="cmm-downloads-empty"
        hidden
    >
        No hay descargas que coincidan con los filtros.
    </div>


    <aside class="cmm-note cmm-downloads-note">
        Esta vista es de observabilidad.
        No expone hashes, nombres internos de torrents,
        rutas del filesystem ni identificadores de servicios externos.
    </aside>
</section>


<script
    src="recursos/js/descargas.js?v=1"
    defer
></script>
