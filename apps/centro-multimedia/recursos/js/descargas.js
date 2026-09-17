(() => {
    'use strict';

    const API =
        'api/descargas.php';

    const POSTER_API =
        'api/caratula.php?id=';

    const REFRESH_MS =
        30000;


    const STATUS = {
        DOWNLOADING: {
            label: 'Descargando',
            className: 'downloading',
        },

        COMPLETE: {
            label: 'Completada',
            className: 'complete',
        },

        WAITING: {
            label: 'En espera',
            className: 'waiting',
        },

        ATTENTION: {
            label: 'Atención',
            className: 'attention',
        },
    };


    const REASONS = {
        PROGRESS_COMPLETE:
            'Progreso finalizado',

        ACTIVE_TRANSFER:
            'Transferencia activa',

        STALLED:
            'Sin actividad',

        QUEUED:
            'En cola',

        PAUSED:
            'Pausada',

        CHECKING:
            'Verificando',

        METADATA:
            'Obteniendo metadatos',

        ERROR:
            'Error de descarga',

        MISSING_FILES:
            'Archivos ausentes',

        UNKNOWN_INCOMPLETE:
            'Pendiente',
    };


    let items = [];
    let loading = false;


    const byId = (id) =>
        document.getElementById(id);


    const normalized = (value) =>
        String(
            value ?? ''
        )
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            )
            .toLowerCase();


    const integer = (value) =>
        new Intl.NumberFormat(
            'es-AR'
        ).format(
            Number(value) || 0
        );


    const bytes = (value) => {
        const number =
            Number(value) || 0;

        if (number <= 0) {
            return '0 B';
        }

        const units = [
            'B',
            'KiB',
            'MiB',
            'GiB',
            'TiB',
        ];

        const exponent =
            Math.min(
                Math.floor(
                    Math.log(number)
                    /
                    Math.log(1024)
                ),
                units.length - 1
            );

        const scaled =
            number
            /
            Math.pow(
                1024,
                exponent
            );

        return (
            new Intl.NumberFormat(
                'es-AR',
                {
                    maximumFractionDigits:
                        exponent === 0
                            ? 0
                            : 2,
                }
            ).format(scaled)
            +
            ' '
            +
            units[exponent]
        );
    };


    const speed = (value) => {
        const number =
            Number(value) || 0;

        return number > 0
            ? `${bytes(number)}/s`
            : '—';
    };


    const eta = (seconds) => {
        if (
            seconds === null
            ||
            seconds === undefined
        ) {
            return '—';
        }

        const value =
            Math.max(
                0,
                Number(seconds) || 0
            );

        if (value < 60) {
            return `${Math.round(value)} s`;
        }

        if (value < 3600) {
            return `${Math.ceil(value / 60)} min`;
        }

        if (value < 86400) {
            return `${Math.ceil(value / 3600)} h`;
        }

        return `${Math.ceil(value / 86400)} d`;
    };


    const dateTime = (value) => {
        if (!value) {
            return '—';
        }

        const date =
            new Date(value);

        if (
            Number.isNaN(
                date.getTime()
            )
        ) {
            return '—';
        }

        return new Intl.DateTimeFormat(
            'es-AR',
            {
                dateStyle: 'short',
                timeStyle: 'short',
            }
        ).format(date);
    };


    const exactKeys = (
        value,
        expected
    ) => {
        if (
            !value
            ||
            typeof value !== 'object'
            ||
            Array.isArray(value)
        ) {
            return false;
        }

        const actual =
            Object.keys(value)
                .sort();

        const wanted =
            expected
                .slice()
                .sort();

        return (
            actual.length
            ===
            wanted.length
            &&
            actual.every(
                (key, index) =>
                    key
                    ===
                    wanted[index]
            )
        );
    };


    const validInteger = (
        value,
        positive = false
    ) =>
        Number.isInteger(value)
        &&
        (
            positive
                ? value > 0
                : value >= 0
        );


    const validate = (data) => {
        if (
            !exactKeys(
                data,
                [
                    'schema',
                    'generated_at_utc',
                    'summary',
                    'items',
                ]
            )
            ||
            data.schema
            !==
            'cmm.downloads.v1'
        ) {
            throw new Error(
                'Respuesta de Descargas incompatible.'
            );
        }


        if (
            !exactKeys(
                data.summary,
                [
                    'total',
                    'downloading',
                    'complete',
                    'waiting',
                    'attention',
                ]
            )
        ) {
            throw new Error(
                'Resumen de Descargas inválido.'
            );
        }


        for (
            const key
            of [
                'total',
                'downloading',
                'complete',
                'waiting',
                'attention',
            ]
        ) {
            if (
                !validInteger(
                    data.summary[key]
                )
            ) {
                throw new Error(
                    'Métrica de Descargas inválida.'
                );
            }
        }


        if (
            !Array.isArray(
                data.items
            )
            ||
            data.items.length
            !==
            data.summary.total
        ) {
            throw new Error(
                'Listado de Descargas inválido.'
            );
        }


        const seen = new Set();


        for (
            const item
            of data.items
        ) {
            if (
                !exactKeys(
                    item,
                    [
                        'id',
                        'media',
                        'targets',
                        'status',
                        'status_reason',
                        'progress',
                        'size_bytes',
                        'downloaded_bytes',
                        'dlspeed_bps',
                        'eta_seconds',
                        'completed_at_utc',
                    ]
                )
            ) {
                throw new Error(
                    'Elemento de Descargas inválido.'
                );
            }


            if (
                !validInteger(
                    item.id,
                    true
                )
                ||
                seen.has(
                    item.id
                )
            ) {
                throw new Error(
                    'Identificador de descarga inválido.'
                );
            }

            seen.add(
                item.id
            );


            if (
                !exactKeys(
                    item.media,
                    [
                        'id',
                        'type',
                        'title',
                    ]
                )
                ||
                !validInteger(
                    item.media.id,
                    true
                )
                ||
                ![
                    'movie',
                    'series',
                ].includes(
                    item.media.type
                )
                ||
                typeof item.media.title
                !==
                'string'
                ||
                item.media.title.trim()
                ===
                ''
            ) {
                throw new Error(
                    'Media de descarga inválida.'
                );
            }


            if (
                !exactKeys(
                    item.targets,
                    [
                        'mode',
                        'count',
                        'seasons',
                    ]
                )
                ||
                ![
                    'media',
                    'episodes',
                ].includes(
                    item.targets.mode
                )
                ||
                !validInteger(
                    item.targets.count,
                    true
                )
                ||
                !Array.isArray(
                    item.targets.seasons
                )
            ) {
                throw new Error(
                    'Destino de descarga inválido.'
                );
            }


            if (
                !Object.prototype
                    .hasOwnProperty
                    .call(
                        STATUS,
                        item.status
                    )
                ||
                !Object.prototype
                    .hasOwnProperty
                    .call(
                        REASONS,
                        item.status_reason
                    )
            ) {
                throw new Error(
                    'Estado de descarga inválido.'
                );
            }


            if (
                typeof item.progress
                !==
                'number'
                ||
                !Number.isFinite(
                    item.progress
                )
                ||
                item.progress < 0
                ||
                item.progress > 1
            ) {
                throw new Error(
                    'Progreso de descarga inválido.'
                );
            }


            for (
                const key
                of [
                    'size_bytes',
                    'downloaded_bytes',
                    'dlspeed_bps',
                ]
            ) {
                if (
                    !validInteger(
                        item[key]
                    )
                ) {
                    throw new Error(
                        'Telemetría de descarga inválida.'
                    );
                }
            }


            if (
                item.eta_seconds
                !==
                null
                &&
                !validInteger(
                    item.eta_seconds
                )
            ) {
                throw new Error(
                    'ETA de descarga inválida.'
                );
            }
        }


        return data;
    };


    const create = (
        tag,
        className = '',
        text = ''
    ) => {
        const node =
            document.createElement(tag);

        if (className) {
            node.className =
                className;
        }

        if (text !== '') {
            node.textContent =
                text;
        }

        return node;
    };


    const setText = (
        id,
        value
    ) => {
        const node =
            byId(id);

        if (node) {
            node.textContent =
                String(value);
        }
    };


    const setStatus = (
        state,
        value,
        text
    ) => {
        const box =
            byId(
                'cmm-downloads-status'
            );

        if (!box) {
            return;
        }

        box.className =
            'cmm-downloads-status '
            +
            `cmm-downloads-status--${state}`;

        setText(
            'cmm-downloads-status-value',
            value
        );

        setText(
            'cmm-downloads-status-text',
            text
        );
    };


    const renderSummary = (
        data
    ) => {
        setText(
            'cmm-downloads-total',
            integer(
                data.summary.total
            )
        );

        setText(
            'cmm-downloads-downloading',
            integer(
                data.summary.downloading
            )
        );

        setText(
            'cmm-downloads-complete',
            integer(
                data.summary.complete
            )
        );

        setText(
            'cmm-downloads-waiting',
            integer(
                data.summary.waiting
            )
        );

        setText(
            'cmm-downloads-attention',
            integer(
                data.summary.attention
            )
        );
    };


    const poster = (item) => {
        const box =
            create(
                'div',
                'cmm-downloads-poster'
            );

        const placeholder =
            create(
                'div',
                'cmm-downloads-poster-placeholder',
                item.media.type
                ===
                'series'
                    ? 'SERIE'
                    : 'PELÍCULA'
            );

        const image =
            document.createElement(
                'img'
            );

        image.alt =
            `Carátula de ${item.media.title}`;

        image.loading =
            'lazy';

        image.decoding =
            'async';

        image.src =
            POSTER_API
            +
            encodeURIComponent(
                String(
                    item.media.id
                )
            );

        image.addEventListener(
            'load',
            () => {
                placeholder.hidden =
                    true;
            }
        );

        image.addEventListener(
            'error',
            () => {
                image.remove();

                placeholder.hidden =
                    false;
            }
        );

        box.append(
            placeholder,
            image
        );

        return box;
    };


    const targetText = (
        item
    ) => {
        if (
            item.targets.mode
            ===
            'media'
        ) {
            return 'Archivo de película';
        }

        const seasons =
            item.targets.seasons
                .map(
                    (season) =>
                        `T${season}`
                )
                .join(', ');

        return (
            `${integer(
                item.targets.count
            )} episodios`
            +
            (
                seasons
                    ? ` · ${seasons}`
                    : ''
            )
        );
    };


    const progressBlock = (
        item
    ) => {
        const wrapper =
            create(
                'div',
                'cmm-downloads-progress'
            );

        const top =
            create(
                'div',
                'cmm-downloads-progress-head'
            );

        const percent =
            Math.round(
                item.progress
                *
                100
            );

        top.append(
            create(
                'span',
                '',
                'Progreso'
            ),
            create(
                'strong',
                '',
                `${percent}%`
            )
        );


        const track =
            create(
                'div',
                'cmm-downloads-progress-track'
            );

        const bar =
            create(
                'div',
                'cmm-downloads-progress-bar'
            );

        bar.style.width =
            `${Math.min(
                100,
                Math.max(
                    0,
                    percent
                )
            )}%`;

        track.append(
            bar
        );

        wrapper.append(
            top,
            track
        );

        return wrapper;
    };


    const telemetry = (
        item
    ) => {
        const grid =
            create(
                'div',
                'cmm-downloads-telemetry'
            );


        const values = [
            [
                'Transferido',
                (
                    `${bytes(
                        item.downloaded_bytes
                    )}`
                    +
                    ' / '
                    +
                    `${bytes(
                        item.size_bytes
                    )}`
                ),
            ],

            [
                'Velocidad',
                speed(
                    item.dlspeed_bps
                ),
            ],

            [
                'ETA',
                eta(
                    item.eta_seconds
                ),
            ],

            [
                'Finalizada',
                dateTime(
                    item.completed_at_utc
                ),
            ],
        ];


        for (
            const [
                label,
                value,
            ]
            of values
        ) {
            const box =
                create(
                    'div',
                    'cmm-downloads-telemetry-item'
                );

            box.append(
                create(
                    'span',
                    '',
                    label
                ),
                create(
                    'strong',
                    '',
                    value
                )
            );

            grid.append(
                box
            );
        }


        return grid;
    };


    const createCard = (
        item
    ) => {
        const state =
            STATUS[
                item.status
            ];

        const card =
            create(
                'article',
                (
                    'cmm-downloads-item '
                    +
                    `cmm-downloads-item--${state.className}`
                )
            );


        const body =
            create(
                'div',
                'cmm-downloads-item-body'
            );


        const badges =
            create(
                'div',
                'cmm-downloads-badges'
            );


        badges.append(
            create(
                'span',
                'cmm-downloads-chip cmm-downloads-chip--type',
                item.media.type
                ===
                'series'
                    ? 'Serie'
                    : 'Película'
            ),

            create(
                'span',
                (
                    'cmm-downloads-chip '
                    +
                    `cmm-downloads-chip--${state.className}`
                ),
                state.label
            )
        );


        const title =
            create(
                'h3',
                'cmm-downloads-title',
                item.media.title
            );


        const reason =
            create(
                'p',
                'cmm-downloads-reason',
                (
                    REASONS[
                        item.status_reason
                    ]
                    ??
                    item.status_reason
                )
            );


        const target =
            create(
                'p',
                'cmm-downloads-target',
                targetText(item)
            );


        const footer =
            create(
                'div',
                'cmm-downloads-footer'
            );


        footer.append(
            create(
                'span',
                '',
                `Descarga #${item.id}`
            ),
            create(
                'span',
                '',
                `Media #${item.media.id}`
            )
        );


        body.append(
            badges,
            title,
            reason,
            target,
            progressBlock(item),
            telemetry(item),
            footer
        );


        card.append(
            poster(item),
            body
        );


        return card;
    };


    const filters = () => ({
        search:
            normalized(
                byId(
                    'cmm-downloads-search'
                )?.value
            ).trim(),

        status:
            byId(
                'cmm-downloads-status-filter'
            )?.value
            ??
            '',

        type:
            byId(
                'cmm-downloads-type'
            )?.value
            ??
            '',
    });


    const renderItems = () => {
        const list =
            byId(
                'cmm-downloads-list'
            );

        const empty =
            byId(
                'cmm-downloads-empty'
            );


        if (
            !list
            ||
            !empty
        ) {
            return;
        }


        const active =
            filters();


        const visible =
            items.filter(
                (item) => {

                    if (
                        active.status
                        &&
                        item.status
                        !==
                        active.status
                    ) {
                        return false;
                    }


                    if (
                        active.type
                        &&
                        item.media.type
                        !==
                        active.type
                    ) {
                        return false;
                    }


                    if (
                        active.search
                        &&
                        !normalized(
                            item.media.title
                        ).includes(
                            active.search
                        )
                    ) {
                        return false;
                    }


                    return true;
                }
            );


        list.replaceChildren();


        for (
            const item
            of visible
        ) {
            list.appendChild(
                createCard(item)
            );
        }


        empty.hidden =
            visible.length !== 0;


        setText(
            'cmm-downloads-result-count',
            visible.length === items.length
                ? `${integer(
                    visible.length
                )} descargas`
                : (
                    `${integer(
                        visible.length
                    )}`
                    +
                    ' de '
                    +
                    `${integer(
                        items.length
                    )}`
                )
        );
    };


    const showError = (
        error
    ) => {
        const box =
            byId(
                'cmm-downloads-error'
            );

        if (box) {
            box.hidden =
                false;

            box.textContent =
                error instanceof Error
                    ? error.message
                    : 'No se pudieron consultar las descargas.';
        }


        setStatus(
            'error',
            '—',
            'Datos no disponibles'
        );
    };


    const clearError = () => {
        const box =
            byId(
                'cmm-downloads-error'
            );

        if (box) {
            box.hidden =
                true;

            box.textContent =
                '';
        }
    };


    const load = async () => {
        if (loading) {
            return;
        }

        loading = true;


        try {

            const response =
                await fetch(
                    API,
                    {
                        method: 'GET',
                        cache: 'no-store',
                        headers: {
                            Accept:
                                'application/json',
                        },
                    }
                );


            if (!response.ok) {
                throw new Error(
                    `La API respondió HTTP ${response.status}.`
                );
            }


            const data =
                validate(
                    await response.json()
                );


            items =
                data.items.slice();


            renderSummary(
                data
            );

            renderItems();

            clearError();


            setStatus(
                'ok',
                integer(
                    data.summary.total
                ),
                (
                    'Sincronizado · '
                    +
                    dateTime(
                        data.generated_at_utc
                    )
                )
            );

        } catch (error) {

            showError(
                error
            );

        } finally {

            loading = false;
        }
    };


    byId(
        'cmm-downloads-search'
    )?.addEventListener(
        'input',
        renderItems
    );


    byId(
        'cmm-downloads-status-filter'
    )?.addEventListener(
        'change',
        renderItems
    );


    byId(
        'cmm-downloads-type'
    )?.addEventListener(
        'change',
        renderItems
    );


    byId(
        'cmm-downloads-reset'
    )?.addEventListener(
        'click',
        () => {

            for (
                const id
                of [
                    'cmm-downloads-search',
                    'cmm-downloads-status-filter',
                    'cmm-downloads-type',
                ]
            ) {
                const node =
                    byId(id);

                if (node) {
                    node.value =
                        '';
                }
            }

            renderItems();
        }
    );


    load();


    window.setInterval(
        load,
        REFRESH_MS
    );

})();
