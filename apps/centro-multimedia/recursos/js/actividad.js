(() => {
    'use strict';


    const API_ENDPOINT =
        'api/actividad.php';

    const POSTER_ENDPOINT =
        'api/caratula.php?id=';

    const SCHEMA =
        'cmm.activity.v1';


    const EVENT_LABELS = {
        REQUEST_CREATED:
            'Solicitud creada',

        DOWNLOAD_COMPLETED:
            'Descarga completada',
    };


    const SOURCE_LABELS = {
        SEERR:
            'Seerr',

        QBITTORRENT:
            'qBittorrent',
    };


    const MEDIA_LABELS = {
        movie:
            'Película',

        series:
            'Serie',
    };


    const elements = {
        status:
            document.getElementById(
                'cmm-activity-status'
            ),

        statusValue:
            document.getElementById(
                'cmm-activity-status-value'
            ),

        statusText:
            document.getElementById(
                'cmm-activity-status-text'
            ),

        total:
            document.getElementById(
                'cmm-activity-total'
            ),

        requests:
            document.getElementById(
                'cmm-activity-requests'
            ),

        completed:
            document.getElementById(
                'cmm-activity-completed'
            ),

        movies:
            document.getElementById(
                'cmm-activity-movies'
            ),

        series:
            document.getElementById(
                'cmm-activity-series'
            ),

        search:
            document.getElementById(
                'cmm-activity-search'
            ),

        event:
            document.getElementById(
                'cmm-activity-event'
            ),

        type:
            document.getElementById(
                'cmm-activity-type'
            ),

        reset:
            document.getElementById(
                'cmm-activity-reset'
            ),

        error:
            document.getElementById(
                'cmm-activity-error'
            ),

        resultCount:
            document.getElementById(
                'cmm-activity-result-count'
            ),

        list:
            document.getElementById(
                'cmm-activity-list'
            ),

        empty:
            document.getElementById(
                'cmm-activity-empty'
            ),
    };


    let activityItems = [];


    function requireElements() {
        for (
            const [
                name,
                element,
            ]
            of Object.entries(
                elements
            )
        ) {
            if (!element) {
                throw new Error(
                    `Elemento faltante: ${name}`
                );
            }
        }
    }


    function isInteger(value) {
        return (
            Number.isInteger(value)
            &&
            value >= 0
        );
    }


    function isString(value) {
        return (
            typeof value
            ===
            'string'
        );
    }


    function validTimestamp(value) {
        if (!isString(value)) {
            return false;
        }

        const parsed =
            new Date(value);

        return Number.isFinite(
            parsed.getTime()
        );
    }


    function validateSummary(
        summary,
        items
    ) {
        if (
            !summary
            ||
            typeof summary
            !==
            'object'
            ||
            Array.isArray(summary)
        ) {
            throw new Error(
                'Resumen de actividad inválido.'
            );
        }


        const required = [
            'total',
            'request_created',
            'download_completed',
            'movies',
            'series',
        ];


        for (const key of required) {
            if (
                !isInteger(
                    summary[key]
                )
            ) {
                throw new Error(
                    'Resumen de actividad inválido.'
                );
            }
        }


        if (
            summary.total
            !==
            items.length
        ) {
            throw new Error(
                'Total de actividad inconsistente.'
            );
        }


        const requestCount =
            items.filter(
                (item) =>
                    item.event_type
                    ===
                    'REQUEST_CREATED'
            ).length;

        const completedCount =
            items.filter(
                (item) =>
                    item.event_type
                    ===
                    'DOWNLOAD_COMPLETED'
            ).length;

        const movieCount =
            items.filter(
                (item) =>
                    item.media.type
                    ===
                    'movie'
            ).length;

        const seriesCount =
            items.filter(
                (item) =>
                    item.media.type
                    ===
                    'series'
            ).length;


        if (
            requestCount
            !==
            summary.request_created
            ||
            completedCount
            !==
            summary.download_completed
            ||
            movieCount
            !==
            summary.movies
            ||
            seriesCount
            !==
            summary.series
        ) {
            throw new Error(
                'Resumen de actividad inconsistente.'
            );
        }
    }


    function validateItem(item) {
        if (
            !item
            ||
            typeof item
            !==
            'object'
            ||
            Array.isArray(item)
        ) {
            throw new Error(
                'Evento de actividad inválido.'
            );
        }


        if (
            !isString(item.id)
            ||
            !/^[0-9a-f]{24}$/.test(
                item.id
            )
        ) {
            throw new Error(
                'Identificador de evento inválido.'
            );
        }


        if (
            !Object.prototype.hasOwnProperty.call(
                EVENT_LABELS,
                item.event_type
            )
        ) {
            throw new Error(
                'Tipo de evento inválido.'
            );
        }


        if (
            !Object.prototype.hasOwnProperty.call(
                SOURCE_LABELS,
                item.source
            )
        ) {
            throw new Error(
                'Origen de evento inválido.'
            );
        }


        if (
            !validTimestamp(
                item.event_at_utc
            )
        ) {
            throw new Error(
                'Fecha de evento inválida.'
            );
        }


        if (
            !item.media
            ||
            typeof item.media
            !==
            'object'
            ||
            Array.isArray(
                item.media
            )
        ) {
            throw new Error(
                'Medio de actividad inválido.'
            );
        }


        if (
            !Number.isInteger(
                item.media.id
            )
            ||
            item.media.id <= 0
        ) {
            throw new Error(
                'Identificador de medio inválido.'
            );
        }


        if (
            !Object.prototype.hasOwnProperty.call(
                MEDIA_LABELS,
                item.media.type
            )
        ) {
            throw new Error(
                'Tipo de medio inválido.'
            );
        }


        if (
            !isString(
                item.media.title
            )
            ||
            item.media.title.trim()
            ===
            ''
        ) {
            throw new Error(
                'Título de medio inválido.'
            );
        }
    }


    function validatePayload(data) {
        if (
            !data
            ||
            typeof data
            !==
            'object'
            ||
            Array.isArray(data)
        ) {
            throw new Error(
                'Respuesta de actividad inválida.'
            );
        }


        if (
            data.schema
            !==
            SCHEMA
        ) {
            throw new Error(
                'Versión de actividad incompatible.'
            );
        }


        if (
            !validTimestamp(
                data.generated_at_utc
            )
        ) {
            throw new Error(
                'Fecha de actualización inválida.'
            );
        }


        if (
            !Array.isArray(
                data.items
            )
        ) {
            throw new Error(
                'Lista de actividad inválida.'
            );
        }


        for (
            const item
            of data.items
        ) {
            validateItem(item);
        }


        validateSummary(
            data.summary,
            data.items
        );


        return data;
    }


    function normalized(value) {
        return String(
            value
            ?? ''
        )
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            )
            .toLowerCase()
            .trim();
    }


    function formatDate(value) {
        const date =
            new Date(value);

        return new Intl.DateTimeFormat(
            'es-AR',
            {
                dateStyle:
                    'medium',

                timeStyle:
                    'short',
            }
        ).format(date);
    }


    function formatUpdated(value) {
        const date =
            new Date(value);

        return new Intl.DateTimeFormat(
            'es-AR',
            {
                hour:
                    '2-digit',

                minute:
                    '2-digit',
            }
        ).format(date);
    }


    function createNode(
        tag,
        className,
        text
    ) {
        const node =
            document.createElement(
                tag
            );

        if (className) {
            node.className =
                className;
        }

        if (
            text
            !==
            undefined
        ) {
            node.textContent =
                text;
        }

        return node;
    }


    function setStatus(
        mode,
        value,
        text
    ) {
        elements.status.className =
            (
                'cmm-activity-status '
                +
                `cmm-activity-status--${mode}`
            );

        elements.statusValue.textContent =
            value;

        elements.statusText.textContent =
            text;
    }


    function showError(message) {
        elements.error.textContent =
            message;

        elements.error.hidden =
            false;
    }


    function clearError() {
        elements.error.textContent =
            '';

        elements.error.hidden =
            true;
    }


    function renderSummary(summary) {
        elements.total.textContent =
            String(
                summary.total
            );

        elements.requests.textContent =
            String(
                summary.request_created
            );

        elements.completed.textContent =
            String(
                summary.download_completed
            );

        elements.movies.textContent =
            String(
                summary.movies
            );

        elements.series.textContent =
            String(
                summary.series
            );
    }


    function getFilteredItems() {
        const search =
            normalized(
                elements.search.value
            );

        const eventFilter =
            elements.event.value;

        const typeFilter =
            elements.type.value;


        return activityItems.filter(
            (item) => {

                if (
                    eventFilter
                    &&
                    item.event_type
                    !==
                    eventFilter
                ) {
                    return false;
                }


                if (
                    typeFilter
                    &&
                    item.media.type
                    !==
                    typeFilter
                ) {
                    return false;
                }


                if (
                    search
                    &&
                    !normalized(
                        item.media.title
                    ).includes(
                        search
                    )
                ) {
                    return false;
                }


                return true;
            }
        );
    }


    function buildPoster(item) {
        const poster =
            createNode(
                'div',
                'cmm-activity-poster'
            );


        const placeholder =
            createNode(
                'span',
                'cmm-activity-poster-placeholder',
                (
                    item.media.type
                    ===
                    'movie'
                    ?
                    'PEL'
                    :
                    'SER'
                )
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
            (
                POSTER_ENDPOINT
                +
                encodeURIComponent(
                    String(
                        item.media.id
                    )
                )
            );


        image.addEventListener(
            'load',
            () => {
                poster.classList.add(
                    'cmm-activity-poster--loaded'
                );
            },
            {
                once:
                    true,
            }
        );


        image.addEventListener(
            'error',
            () => {
                image.remove();
            },
            {
                once:
                    true,
            }
        );


        poster.append(
            placeholder,
            image
        );


        return poster;
    }


    function buildEventCard(item) {
        const tone =
            (
                item.event_type
                ===
                'DOWNLOAD_COMPLETED'
                ?
                'complete'
                :
                'request'
            );


        const card =
            createNode(
                'article',
                (
                    'cmm-activity-item '
                    +
                    `cmm-activity-item--${tone}`
                )
            );

        card.setAttribute(
            'role',
            'listitem'
        );


        const poster =
            buildPoster(item);


        const body =
            createNode(
                'div',
                'cmm-activity-item-body'
            );


        const badges =
            createNode(
                'div',
                'cmm-activity-badges'
            );


        badges.append(
            createNode(
                'span',
                (
                    'cmm-activity-chip '
                    +
                    `cmm-activity-chip--${tone}`
                ),
                EVENT_LABELS[
                    item.event_type
                ]
            ),

            createNode(
                'span',
                'cmm-activity-chip',
                MEDIA_LABELS[
                    item.media.type
                ]
            ),

            createNode(
                'span',
                'cmm-activity-chip cmm-activity-chip--source',
                SOURCE_LABELS[
                    item.source
                ]
            )
        );


        const title =
            createNode(
                'h3',
                'cmm-activity-item-title',
                item.media.title
            );


        const copy =
            createNode(
                'p',
                'cmm-activity-item-copy',
                (
                    item.event_type
                    ===
                    'REQUEST_CREATED'
                    ?
                    'La solicitud ingresó al flujo multimedia.'
                    :
                    'La descarga terminó correctamente.'
                )
            );


        body.append(
            badges,
            title,
            copy
        );


        const time =
            createNode(
                'div',
                'cmm-activity-time'
            );


        const timeLabel =
            createNode(
                'span',
                null,
                'Fecha'
            );


        const timeValue =
            document.createElement(
                'time'
            );

        timeValue.dateTime =
            item.event_at_utc;

        timeValue.textContent =
            formatDate(
                item.event_at_utc
            );


        time.append(
            timeLabel,
            timeValue
        );


        card.append(
            poster,
            body,
            time
        );


        return card;
    }


    function renderItems() {
        const filtered =
            getFilteredItems();


        elements.list.replaceChildren();


        for (
            const item
            of filtered
        ) {
            elements.list.append(
                buildEventCard(item)
            );
        }


        elements.resultCount.textContent =
            (
                filtered.length
                ===
                1
                ?
                '1 evento'
                :
                `${filtered.length} eventos`
            );


        elements.empty.hidden =
            filtered.length !== 0;
    }


    function resetFilters() {
        elements.search.value =
            '';

        elements.event.value =
            '';

        elements.type.value =
            '';

        renderItems();

        elements.search.focus();
    }


    function bindFilters() {
        elements.search.addEventListener(
            'input',
            renderItems
        );

        elements.event.addEventListener(
            'change',
            renderItems
        );

        elements.type.addEventListener(
            'change',
            renderItems
        );

        elements.reset.addEventListener(
            'click',
            resetFilters
        );
    }


    async function loadActivity() {
        setStatus(
            'loading',
            '…',
            'Consultando actividad…'
        );

        clearError();


        const response =
            await fetch(
                API_ENDPOINT,
                {
                    cache:
                        'no-store',

                    headers: {
                        Accept:
                            'application/json',
                    },
                }
            );


        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}`
            );
        }


        const data =
            validatePayload(
                await response.json()
            );


        activityItems =
            data.items.slice();


        renderSummary(
            data.summary
        );

        renderItems();


        setStatus(
            'ready',
            'OK',
            (
                'Actualizado '
                +
                formatUpdated(
                    data.generated_at_utc
                )
            )
        );
    }


    async function start() {
        try {
            requireElements();

            bindFilters();

            await loadActivity();

        } catch (error) {

            setStatus(
                'error',
                'Error',
                'No se pudo cargar la actividad'
            );

            showError(
                'No fue posible consultar el historial de actividad.'
            );

            console.error(
                'CMM activity:',
                error
            );
        }
    }


    if (
        document.readyState
        ===
        'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            start,
            {
                once:
                    true,
            }
        );

    } else {
        start();
    }
})();
