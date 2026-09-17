<section
    class="cmm-page"
    data-cmm-resumen
>

    <div class="cmm-page-heading">

        <div>

            <p class="cmm-kicker">
                Estado general
            </p>

            <h2>
                Resumen
            </h2>

            <p>
                Capacidad del servidor, utilización física
                del contenido CMM y efecto de los hardlinks.
            </p>

        </div>


        <div class="cmm-snapshot">

            <span>
                Snapshot
            </span>

            <strong id="snapshot-id">
                —
            </strong>

            <small id="snapshot-time">
                Sin datos
            </small>

        </div>

    </div>


    <section
        class="cmm-grid cmm-grid--metrics"
        aria-label="Métricas principales"
    >

        <article class="cmm-card">

            <span class="cmm-card-label">
                Disco utilizado
            </span>

            <strong
                id="capacity-used"
                class="cmm-card-value"
            >
                —
            </strong>

            <span
                id="capacity-total"
                class="cmm-card-secondary"
            >
                —
            </span>

        </article>


        <article class="cmm-card">

            <span class="cmm-card-label">
                Objetos físicos CMM
            </span>

            <strong
                id="unique-allocated"
                class="cmm-card-value"
            >
                —
            </strong>

            <span
                id="unique-objects"
                class="cmm-card-secondary"
            >
                —
            </span>

        </article>


        <article class="cmm-card">

            <span class="cmm-card-label">
                Duplicación lógica evitada
            </span>

            <strong
                id="hardlink-shared"
                class="cmm-card-value"
            >
                —
            </strong>

            <span
                id="hardlink-groups"
                class="cmm-card-secondary"
            >
                —
            </span>

        </article>


        <article class="cmm-card">

            <span class="cmm-card-label">
                Biblioteca lógica
            </span>

            <strong
                id="library-logical"
                class="cmm-card-value"
            >
                —
            </strong>

            <span
                id="library-split"
                class="cmm-card-secondary"
            >
                —
            </span>

        </article>

    </section>


    <section class="cmm-panel">

        <div class="cmm-panel-heading">

            <div>

                <h3>
                    Capacidad del filesystem
                </h3>

                <p>
                    Uso total del volumen donde reside CMM.
                </p>

            </div>

            <strong id="capacity-percent">
                —
            </strong>

        </div>


        <div
            class="cmm-progress"
            role="progressbar"
            aria-label="Uso del filesystem"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="0"
        >

            <div
                id="capacity-progress"
                class="cmm-progress-bar"
            ></div>

        </div>


        <div class="cmm-capacity-legend">

            <div>

                <span>
                    Usado
                </span>

                <strong id="legend-used">
                    —
                </strong>

            </div>


            <div>

                <span>
                    Libre
                </span>

                <strong id="legend-free">
                    —
                </strong>

            </div>


            <div>

                <span>
                    Reservado / no disponible
                </span>

                <strong id="legend-reserved">
                    —
                </strong>

            </div>

        </div>

    </section>


    <section class="cmm-grid cmm-grid--details">

        <article class="cmm-panel">

            <div class="cmm-panel-heading">

                <div>

                    <h3>
                        Contenido lógico
                    </h3>

                    <p>
                        Referencias visibles por categoría.
                    </p>

                </div>

            </div>


            <dl class="cmm-definition-list">

                <div>
                    <dt>Películas</dt>
                    <dd id="movies-logical">—</dd>
                </div>

                <div>
                    <dt>Series</dt>
                    <dd id="series-logical">—</dd>
                </div>

                <div>
                    <dt>Torrents</dt>
                    <dd id="torrents-logical">—</dd>
                </div>

                <div>
                    <dt>Total de referencias</dt>
                    <dd id="references-logical">—</dd>
                </div>

            </dl>

        </article>


        <article class="cmm-panel">

            <div class="cmm-panel-heading">

                <div>

                    <h3>
                        Referencias e identidad
                    </h3>

                    <p>
                        Estado del modelo hardlink-aware.
                    </p>

                </div>

            </div>


            <dl class="cmm-definition-list">

                <div>
                    <dt>Referencias totales</dt>
                    <dd id="references-total">—</dd>
                </div>

                <div>
                    <dt>Biblioteca</dt>
                    <dd id="references-library">—</dd>
                </div>

                <div>
                    <dt>Torrents</dt>
                    <dd id="references-torrent">—</dd>
                </div>

                <div>
                    <dt>Objetos únicos</dt>
                    <dd id="physical-objects">—</dd>
                </div>

            </dl>

        </article>

    </section>


    <aside class="cmm-note">

        <strong>
            Semántica de almacenamiento
        </strong>

        <p>
            “Disco utilizado” representa el uso completo del
            filesystem. “Objetos físicos CMM” contabiliza bloques
            asignados a objetos únicos de CMM. La duplicación lógica
            evitada muestra bytes que no deben contarse dos veces
            gracias a hardlinks.
        </p>

    </aside>


    <div
        id="cmm-error"
        class="cmm-error"
        hidden
        role="alert"
    ></div>

</section>
