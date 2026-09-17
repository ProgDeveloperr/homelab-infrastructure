<section
    class="cmm-storage"
    aria-labelledby="cmm-storage-title"
>
    <header class="cmm-page-heading cmm-storage-heading">
        <div>
            <span class="cmm-eyebrow">
                Observabilidad
            </span>

            <h1 id="cmm-storage-title">
                Almacenamiento
            </h1>

            <p>
                Capacidad, ocupación lógica, identidad física
                y ahorro por hardlinks.
            </p>
        </div>

        <div
            id="cmm-storage-status"
            class="cmm-storage-status"
            data-state="loading"
            role="status"
            aria-live="polite"
        >
            <span
                class="cmm-storage-status-dot"
                aria-hidden="true"
            ></span>

            <div class="cmm-storage-status-copy">
                <strong id="cmm-storage-status-value">
                    …
                </strong>

                <span id="cmm-storage-status-text">
                    Consultando almacenamiento…
                </span>
            </div>
        </div>
    </header>


    <div
        id="cmm-storage-error"
        class="cmm-error cmm-storage-error"
        role="alert"
        hidden
    ></div>


    <section
        class="cmm-storage-capacity"
        aria-labelledby="cmm-storage-capacity-title"
    >
        <div class="cmm-storage-section-heading">
            <div>
                <span class="cmm-eyebrow">
                    Capacidad
                </span>

                <h2 id="cmm-storage-capacity-title">
                    Capacidad del filesystem
                </h2>
            </div>

            <time id="cmm-storage-captured">
                —
            </time>
        </div>


        <div class="cmm-storage-capacity-summary">
            <article class="cmm-storage-metric">
                <span>Total</span>
                <strong id="cmm-storage-total">—</strong>
                <small>Capacidad total</small>
            </article>

            <article class="cmm-storage-metric cmm-storage-metric--used">
                <span>Usado</span>
                <strong id="cmm-storage-used">—</strong>
                <small id="cmm-storage-used-percent">—</small>
            </article>

            <article class="cmm-storage-metric cmm-storage-metric--free">
                <span>Libre</span>
                <strong id="cmm-storage-free">—</strong>
                <small id="cmm-storage-free-percent">—</small>
            </article>

            <article class="cmm-storage-metric cmm-storage-metric--reserved">
                <span>Reservado</span>
                <strong id="cmm-storage-reserved">—</strong>
                <small id="cmm-storage-reserved-percent">—</small>
            </article>
        </div>


        <div
            id="cmm-storage-bar"
            class="cmm-storage-bar"
            role="img"
            aria-label="Distribución de capacidad"
        >
            <span
                id="cmm-storage-bar-used"
                class="cmm-storage-bar-segment cmm-storage-bar-segment--used"
            ></span>

            <span
                id="cmm-storage-bar-free"
                class="cmm-storage-bar-segment cmm-storage-bar-segment--free"
            ></span>

            <span
                id="cmm-storage-bar-reserved"
                class="cmm-storage-bar-segment cmm-storage-bar-segment--reserved"
            ></span>
        </div>


        <div class="cmm-storage-legend">
            <span>
                <i class="cmm-storage-legend-dot cmm-storage-legend-dot--used"></i>
                Usado
            </span>

            <span>
                <i class="cmm-storage-legend-dot cmm-storage-legend-dot--free"></i>
                Libre
            </span>

            <span>
                <i class="cmm-storage-legend-dot cmm-storage-legend-dot--reserved"></i>
                Reservado / no disponible
            </span>
        </div>
    </section>


    <div class="cmm-storage-grid">
        <section
            class="cmm-storage-panel"
            aria-labelledby="cmm-storage-logical-title"
        >
            <div class="cmm-storage-panel-heading">
                <span class="cmm-eyebrow">
                    Referencias
                </span>

                <h2 id="cmm-storage-logical-title">
                    Contenido lógico
                </h2>
            </div>

            <dl class="cmm-storage-list">
                <div>
                    <dt>Biblioteca</dt>
                    <dd id="cmm-storage-library">—</dd>
                </div>

                <div>
                    <dt>Películas</dt>
                    <dd id="cmm-storage-movies">—</dd>
                </div>

                <div>
                    <dt>Series</dt>
                    <dd id="cmm-storage-series">—</dd>
                </div>

                <div>
                    <dt>Torrents</dt>
                    <dd id="cmm-storage-torrents">—</dd>
                </div>

                <div class="cmm-storage-list-total">
                    <dt>Todas las referencias</dt>
                    <dd id="cmm-storage-all-references">—</dd>
                </div>
            </dl>
        </section>


        <section
            class="cmm-storage-panel"
            aria-labelledby="cmm-storage-physical-title"
        >
            <div class="cmm-storage-panel-heading">
                <span class="cmm-eyebrow">
                    Identidad
                </span>

                <h2 id="cmm-storage-physical-title">
                    Ocupación física
                </h2>
            </div>

            <dl class="cmm-storage-list">
                <div>
                    <dt>Lógico único</dt>
                    <dd id="cmm-storage-unique-logical">—</dd>
                </div>

                <div>
                    <dt>Asignado físicamente</dt>
                    <dd id="cmm-storage-unique-allocated">—</dd>
                </div>

                <div class="cmm-storage-list-total">
                    <dt>Objetos físicos únicos</dt>
                    <dd id="cmm-storage-unique-objects">—</dd>
                </div>
            </dl>
        </section>


        <section
            class="cmm-storage-panel"
            aria-labelledby="cmm-storage-hardlinks-title"
        >
            <div class="cmm-storage-panel-heading">
                <span class="cmm-eyebrow">
                    Eficiencia
                </span>

                <h2 id="cmm-storage-hardlinks-title">
                    Hardlinks
                </h2>
            </div>

            <dl class="cmm-storage-list">
                <div>
                    <dt>Grupos compartidos</dt>
                    <dd id="cmm-storage-hardlink-groups">—</dd>
                </div>

                <div class="cmm-storage-list-total">
                    <dt>Espacio lógico compartido</dt>
                    <dd id="cmm-storage-hardlink-shared">—</dd>
                </div>
            </dl>
        </section>


        <section
            class="cmm-storage-panel"
            aria-labelledby="cmm-storage-references-title"
        >
            <div class="cmm-storage-panel-heading">
                <span class="cmm-eyebrow">
                    Inventario
                </span>

                <h2 id="cmm-storage-references-title">
                    Referencias
                </h2>
            </div>

            <dl class="cmm-storage-list">
                <div>
                    <dt>Biblioteca</dt>
                    <dd id="cmm-storage-reference-library">—</dd>
                </div>

                <div>
                    <dt>Torrent</dt>
                    <dd id="cmm-storage-reference-torrent">—</dd>
                </div>

                <div class="cmm-storage-list-total">
                    <dt>Total</dt>
                    <dd id="cmm-storage-reference-total">—</dd>
                </div>
            </dl>
        </section>
    </div>


    <aside class="cmm-note cmm-storage-note">
        <strong>Lectura de los datos.</strong>

        Los tamaños lógicos representan referencias y pueden
        contabilizar más de una vez contenido compartido mediante
        hardlinks. La ocupación física única evita esa duplicación.
    </aside>
</section>

<script
    src="recursos/js/almacenamiento.js?v=1"
    defer
></script>
