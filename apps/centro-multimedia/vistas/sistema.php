<section
    class="cmm-system"
    aria-labelledby="cmm-system-title"
>
    <header class="cmm-page-heading cmm-system-heading">
        <div>
            <p class="cmm-eyebrow">
                Observabilidad
            </p>

            <h1 id="cmm-system-title">
                Sistema
            </h1>

            <p>
                Estado del host y del runtime interno del Centro Multimedia.
            </p>
        </div>

        <div
            id="cmm-system-status"
            class="cmm-system-status cmm-system-status--loading"
            role="status"
            aria-live="polite"
        >
            <span
                class="cmm-system-status-dot"
                aria-hidden="true"
            ></span>

            <div class="cmm-system-status-copy">
                <strong id="cmm-system-status-value">
                    …
                </strong>

                <span id="cmm-system-status-text">
                    Consultando sistema…
                </span>
            </div>
        </div>
    </header>


    <div
        id="cmm-system-error"
        class="cmm-error cmm-system-error"
        role="alert"
        hidden
    ></div>


    <section
        class="cmm-system-summary"
        aria-label="Resumen del sistema"
    >
        <article
            id="cmm-system-health-card"
            class="cmm-system-metric cmm-system-metric--status"
        >
            <span>Estado CMM</span>

            <strong id="cmm-system-health">
                —
            </strong>

            <small>
                Broker, refresh y providers
            </small>
        </article>


        <article class="cmm-system-metric">
            <span>Uptime</span>

            <strong id="cmm-system-uptime">
                —
            </strong>

            <small>
                Tiempo activo del host
            </small>
        </article>


        <article class="cmm-system-metric">
            <span>Memoria usada</span>

            <strong id="cmm-system-memory-percent">
                —
            </strong>

            <small>
                Sobre memoria disponible al sistema
            </small>
        </article>


        <article class="cmm-system-metric">
            <span>Providers</span>

            <strong id="cmm-system-provider-ratio">
                —
            </strong>

            <small>
                Proyecciones saludables
            </small>
        </article>
    </section>


    <div class="cmm-system-grid">

        <section
            class="cmm-system-panel"
            aria-labelledby="cmm-system-host-title"
        >
            <div class="cmm-system-section-heading">
                <div>
                    <span>Host</span>

                    <h2 id="cmm-system-host-title">
                        Plataforma
                    </h2>
                </div>
            </div>

            <dl class="cmm-system-list">
                <div>
                    <dt>Sistema operativo</dt>
                    <dd id="cmm-system-os">—</dd>
                </div>

                <div>
                    <dt>Kernel</dt>
                    <dd id="cmm-system-kernel">—</dd>
                </div>

                <div>
                    <dt>Arquitectura</dt>
                    <dd id="cmm-system-architecture">—</dd>
                </div>

                <div>
                    <dt>Uptime</dt>
                    <dd id="cmm-system-host-uptime">—</dd>
                </div>
            </dl>
        </section>


        <section
            class="cmm-system-panel"
            aria-labelledby="cmm-system-cpu-title"
        >
            <div class="cmm-system-section-heading">
                <div>
                    <span>CPU</span>

                    <h2 id="cmm-system-cpu-title">
                        Procesador y carga
                    </h2>
                </div>
            </div>

            <dl class="cmm-system-list">
                <div>
                    <dt>Modelo</dt>
                    <dd id="cmm-system-cpu-model">—</dd>
                </div>

                <div>
                    <dt>Procesadores lógicos</dt>
                    <dd id="cmm-system-logical-processors">—</dd>
                </div>

                <div>
                    <dt>Carga 1 min</dt>
                    <dd id="cmm-system-load-1">—</dd>
                </div>

                <div>
                    <dt>Carga 5 min</dt>
                    <dd id="cmm-system-load-5">—</dd>
                </div>

                <div>
                    <dt>Carga 15 min</dt>
                    <dd id="cmm-system-load-15">—</dd>
                </div>
            </dl>
        </section>


        <section
            class="cmm-system-panel cmm-system-panel--wide"
            aria-labelledby="cmm-system-memory-title"
        >
            <div class="cmm-system-section-heading">
                <div>
                    <span>Memoria</span>

                    <h2 id="cmm-system-memory-title">
                        Uso de RAM
                    </h2>
                </div>

                <strong id="cmm-system-memory-percent-detail">
                    —
                </strong>
            </div>

            <div
                id="cmm-system-memory-bar"
                class="cmm-system-memory-bar"
                role="img"
                aria-label="Uso de memoria"
            >
                <span
                    id="cmm-system-memory-bar-used"
                    class="cmm-system-memory-bar-used"
                ></span>
            </div>

            <div class="cmm-system-memory-summary">
                <div>
                    <span>Usada</span>
                    <strong id="cmm-system-memory-used">—</strong>
                </div>

                <div>
                    <span>Disponible</span>
                    <strong id="cmm-system-memory-available">—</strong>
                </div>

                <div>
                    <span>Total</span>
                    <strong id="cmm-system-memory-total">—</strong>
                </div>
            </div>
        </section>


        <section
            class="cmm-system-panel"
            aria-labelledby="cmm-system-runtime-title"
        >
            <div class="cmm-system-section-heading">
                <div>
                    <span>CMM</span>

                    <h2 id="cmm-system-runtime-title">
                        Runtime
                    </h2>
                </div>
            </div>

            <dl class="cmm-system-list">
                <div>
                    <dt>Broker</dt>
                    <dd id="cmm-system-broker-state">—</dd>
                </div>

                <div>
                    <dt>Versión broker</dt>
                    <dd id="cmm-system-broker-version">—</dd>
                </div>

                <div>
                    <dt>Modo broker</dt>
                    <dd id="cmm-system-broker-mode">—</dd>
                </div>

                <div>
                    <dt>Timer refresh</dt>
                    <dd id="cmm-system-refresh-timer">—</dd>
                </div>

                <div>
                    <dt>Último refresh</dt>
                    <dd id="cmm-system-refresh-result">—</dd>
                </div>
            </dl>
        </section>


        <section
            class="cmm-system-panel"
            aria-labelledby="cmm-system-provider-title"
        >
            <div class="cmm-system-section-heading">
                <div>
                    <span>Pipeline</span>

                    <h2 id="cmm-system-provider-title">
                        Providers
                    </h2>
                </div>

                <strong id="cmm-system-provider-summary">
                    —
                </strong>
            </div>

            <div
                class="cmm-system-provider-counters"
                aria-label="Resumen de providers"
            >
                <div>
                    <span>Total</span>
                    <strong id="cmm-system-provider-total">—</strong>
                </div>

                <div>
                    <span>OK</span>
                    <strong id="cmm-system-provider-success">—</strong>
                </div>

                <div>
                    <span>Error</span>
                    <strong id="cmm-system-provider-error">—</strong>
                </div>

                <div>
                    <span>Desconocido</span>
                    <strong id="cmm-system-provider-unknown">—</strong>
                </div>
            </div>

            <div
                id="cmm-system-provider-list"
                class="cmm-system-provider-list"
                aria-label="Estado individual de providers"
            ></div>
        </section>
    </div>


    <aside class="cmm-note cmm-system-note">
        <strong>
            Sólo observabilidad.
        </strong>

        <span>
            La vista consume una proyección saneada y no permite
            reinicios, cambios de servicios ni acciones sobre el host.
            Última captura:
            <time id="cmm-system-captured">
                —
            </time>
        </span>
    </aside>


    <script
        src="recursos/js/sistema.js?v=1"
        defer
    ></script>
</section>
