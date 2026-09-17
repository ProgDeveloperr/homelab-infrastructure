(() => {
    'use strict';

    const API_ENDPOINT =
        'api/sistema.php';

    const SCHEMA =
        'cmm.system.v1';

    const REFRESH_MS =
        15000;

    const PROVIDER_LABELS = {
        storage:
            'Almacenamiento',

        library:
            'Biblioteca',

        posters:
            'Carátulas',

        requests:
            'Solicitudes',

        downloads:
            'Descargas',

        activity:
            'Actividad',
    };


    const elements = {
        status:
            document.getElementById(
                'cmm-system-status'
            ),

        statusValue:
            document.getElementById(
                'cmm-system-status-value'
            ),

        statusText:
            document.getElementById(
                'cmm-system-status-text'
            ),

        error:
            document.getElementById(
                'cmm-system-error'
            ),

        healthCard:
            document.getElementById(
                'cmm-system-health-card'
            ),

        health:
            document.getElementById(
                'cmm-system-health'
            ),

        uptime:
            document.getElementById(
                'cmm-system-uptime'
            ),

        memoryPercent:
            document.getElementById(
                'cmm-system-memory-percent'
            ),

        providerRatio:
            document.getElementById(
                'cmm-system-provider-ratio'
            ),

        os:
            document.getElementById(
                'cmm-system-os'
            ),

        kernel:
            document.getElementById(
                'cmm-system-kernel'
            ),

        architecture:
            document.getElementById(
                'cmm-system-architecture'
            ),

        hostUptime:
            document.getElementById(
                'cmm-system-host-uptime'
            ),

        cpuModel:
            document.getElementById(
                'cmm-system-cpu-model'
            ),

        logicalProcessors:
            document.getElementById(
                'cmm-system-logical-processors'
            ),

        load1:
            document.getElementById(
                'cmm-system-load-1'
            ),

        load5:
            document.getElementById(
                'cmm-system-load-5'
            ),

        load15:
            document.getElementById(
                'cmm-system-load-15'
            ),

        memoryPercentDetail:
            document.getElementById(
                'cmm-system-memory-percent-detail'
            ),

        memoryBar:
            document.getElementById(
                'cmm-system-memory-bar'
            ),

        memoryBarUsed:
            document.getElementById(
                'cmm-system-memory-bar-used'
            ),

        memoryUsed:
            document.getElementById(
                'cmm-system-memory-used'
            ),

        memoryAvailable:
            document.getElementById(
                'cmm-system-memory-available'
            ),

        memoryTotal:
            document.getElementById(
                'cmm-system-memory-total'
            ),

        brokerState:
            document.getElementById(
                'cmm-system-broker-state'
            ),

        brokerVersion:
            document.getElementById(
                'cmm-system-broker-version'
            ),

        brokerMode:
            document.getElementById(
                'cmm-system-broker-mode'
            ),

        refreshTimer:
            document.getElementById(
                'cmm-system-refresh-timer'
            ),

        refreshResult:
            document.getElementById(
                'cmm-system-refresh-result'
            ),

        providerSummary:
            document.getElementById(
                'cmm-system-provider-summary'
            ),

        providerTotal:
            document.getElementById(
                'cmm-system-provider-total'
            ),

        providerSuccess:
            document.getElementById(
                'cmm-system-provider-success'
            ),

        providerError:
            document.getElementById(
                'cmm-system-provider-error'
            ),

        providerUnknown:
            document.getElementById(
                'cmm-system-provider-unknown'
            ),

        providerList:
            document.getElementById(
                'cmm-system-provider-list'
            ),

        captured:
            document.getElementById(
                'cmm-system-captured'
            ),
    };


    let loading = false;


    function requireElements() {
        for (
            const [
                name,
                element,
            ]
            of Object.entries(elements)
        ) {
            if (!element) {
                throw new Error(
                    `Elemento faltante: ${name}`
                );
            }
        }
    }


    function exactKeys(
        value,
        expected
    ) {
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
            Object.keys(value).sort();

        const wanted =
            expected.slice().sort();

        return (
            actual.length === wanted.length
            &&
            actual.every(
                (
                    item,
                    index
                ) =>
                    item === wanted[index]
            )
        );
    }


    function stringValue(value) {
        return (
            typeof value === 'string'
            &&
            value.length > 0
        );
    }


    function integer(value) {
        return (
            Number.isInteger(value)
            &&
            value >= 0
        );
    }


    function numberValue(value) {
        return (
            typeof value === 'number'
            &&
            Number.isFinite(value)
        );
    }


    function timestamp(value) {
        if (!stringValue(value)) {
            return false;
        }

        return Number.isFinite(
            new Date(value).getTime()
        );
    }


    function validate(data) {
        if (
            !exactKeys(
                data,
                [
                    'schema',
                    'host',
                    'cpu',
                    'memory',
                    'cmm',
                    'snapshot',
                ]
            )
            ||
            data.schema !== SCHEMA
        ) {
            throw new Error(
                'Contrato de Sistema inválido.'
            );
        }


        if (
            !exactKeys(
                data.host,
                [
                    'operating_system',
                    'kernel_version',
                    'architecture',
                    'uptime_seconds',
                ]
            )
            ||
            !stringValue(
                data.host.operating_system
            )
            ||
            !stringValue(
                data.host.kernel_version
            )
            ||
            !stringValue(
                data.host.architecture
            )
            ||
            !integer(
                data.host.uptime_seconds
            )
        ) {
            throw new Error(
                'Datos del host inválidos.'
            );
        }


        if (
            !exactKeys(
                data.cpu,
                [
                    'model',
                    'logical_processors',
                    'load_1',
                    'load_5',
                    'load_15',
                ]
            )
            ||
            !stringValue(
                data.cpu.model
            )
            ||
            !integer(
                data.cpu.logical_processors
            )
            ||
            data.cpu.logical_processors < 1
        ) {
            throw new Error(
                'Datos de CPU inválidos.'
            );
        }


        for (
            const key
            of [
                'load_1',
                'load_5',
                'load_15',
            ]
        ) {
            if (
                !numberValue(
                    data.cpu[key]
                )
                ||
                data.cpu[key] < 0
            ) {
                throw new Error(
                    'Carga de CPU inválida.'
                );
            }
        }


        if (
            !exactKeys(
                data.memory,
                [
                    'total_bytes',
                    'used_bytes',
                    'available_bytes',
                    'used_percent',
                ]
            )
            ||
            !integer(
                data.memory.total_bytes
            )
            ||
            data.memory.total_bytes <= 0
            ||
            !integer(
                data.memory.used_bytes
            )
            ||
            !integer(
                data.memory.available_bytes
            )
            ||
            !numberValue(
                data.memory.used_percent
            )
            ||
            data.memory.used_bytes
            +
            data.memory.available_bytes
            !==
            data.memory.total_bytes
        ) {
            throw new Error(
                'Datos de memoria inválidos.'
            );
        }


        if (
            !exactKeys(
                data.cmm,
                [
                    'broker',
                    'refresh',
                    'providers',
                ]
            )
        ) {
            throw new Error(
                'Runtime CMM inválido.'
            );
        }


        if (
            !exactKeys(
                data.cmm.broker,
                [
                    'state',
                    'version',
                    'mode',
                ]
            )
            ||
            ![
                'OK',
                'ERROR',
                'UNKNOWN',
            ].includes(
                data.cmm.broker.state
            )
            ||
            !stringValue(
                data.cmm.broker.version
            )
            ||
            !stringValue(
                data.cmm.broker.mode
            )
        ) {
            throw new Error(
                'Broker inválido.'
            );
        }


        if (
            !exactKeys(
                data.cmm.refresh,
                [
                    'timer_state',
                    'last_result',
                ]
            )
            ||
            ![
                'ACTIVE',
                'INACTIVE',
                'UNKNOWN',
            ].includes(
                data.cmm.refresh.timer_state
            )
            ||
            ![
                'SUCCESS',
                'ERROR',
                'UNKNOWN',
            ].includes(
                data.cmm.refresh.last_result
            )
        ) {
            throw new Error(
                'Refresh inválido.'
            );
        }


        const providers =
            data.cmm.providers;


        if (
            !exactKeys(
                providers,
                [
                    'total',
                    'success',
                    'error',
                    'unknown',
                    'items',
                ]
            )
            ||
            !integer(providers.total)
            ||
            !integer(providers.success)
            ||
            !integer(providers.error)
            ||
            !integer(providers.unknown)
            ||
            providers.total !== 6
            ||
            providers.success
            +
            providers.error
            +
            providers.unknown
            !==
            providers.total
            ||
            !Array.isArray(
                providers.items
            )
            ||
            providers.items.length !== 6
        ) {
            throw new Error(
                'Providers inválidos.'
            );
        }


        const expectedNames =
            Object.keys(
                PROVIDER_LABELS
            ).sort();

        const names = [];


        for (
            const item
            of providers.items
        ) {
            if (
                !exactKeys(
                    item,
                    [
                        'name',
                        'result',
                    ]
                )
                ||
                !Object.prototype.hasOwnProperty.call(
                    PROVIDER_LABELS,
                    item.name
                )
                ||
                ![
                    'SUCCESS',
                    'ERROR',
                    'UNKNOWN',
                ].includes(
                    item.result
                )
            ) {
                throw new Error(
                    'Provider inválido.'
                );
            }

            names.push(item.name);
        }


        names.sort();


        if (
            names.length
            !==
            new Set(names).size
            ||
            names.some(
                (
                    value,
                    index
                ) =>
                    value
                    !==
                    expectedNames[index]
            )
        ) {
            throw new Error(
                'Conjunto de providers inválido.'
            );
        }


        if (
            !exactKeys(
                data.snapshot,
                [
                    'captured_at_utc',
                ]
            )
            ||
            !timestamp(
                data.snapshot.captured_at_utc
            )
        ) {
            throw new Error(
                'Snapshot inválido.'
            );
        }


        return data;
    }


    function setText(
        element,
        value
    ) {
        element.textContent =
            String(value);
    }


    function formatBytes(value) {
        const units = [
            'B',
            'KiB',
            'MiB',
            'GiB',
            'TiB',
        ];

        let amount =
            Number(value);

        let unit = 0;


        while (
            amount >= 1024
            &&
            unit < units.length - 1
        ) {
            amount /= 1024;
            unit++;
        }


        const digits =
            unit >= 3
                ? 2
                : unit >= 2
                    ? 1
                    : 0;


        return (
            amount.toLocaleString(
                'es-AR',
                {
                    minimumFractionDigits:
                        digits,

                    maximumFractionDigits:
                        digits,
                }
            )
            +
            ' '
            +
            units[unit]
        );
    }


    function formatPercent(value) {
        return (
            Number(value).toLocaleString(
                'es-AR',
                {
                    minimumFractionDigits:
                        2,

                    maximumFractionDigits:
                        2,
                }
            )
            +
            ' %'
        );
    }


    function formatLoad(value) {
        return Number(value).toLocaleString(
            'es-AR',
            {
                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2,
            }
        );
    }


    function formatUptime(seconds) {
        let remaining =
            Math.max(
                0,
                Math.floor(seconds)
            );

        const days =
            Math.floor(
                remaining / 86400
            );

        remaining %= 86400;

        const hours =
            Math.floor(
                remaining / 3600
            );

        remaining %= 3600;

        const minutes =
            Math.floor(
                remaining / 60
            );

        const parts = [];


        if (days > 0) {
            parts.push(
                `${days} d`
            );
        }


        if (
            hours > 0
            ||
            days > 0
        ) {
            parts.push(
                `${hours} h`
            );
        }


        parts.push(
            `${minutes} min`
        );


        return parts.join(' ');
    }


    function formatDateTime(value) {
        return new Intl.DateTimeFormat(
            'es-AR',
            {
                dateStyle:
                    'short',

                timeStyle:
                    'medium',
            }
        ).format(
            new Date(value)
        );
    }


    function freshness(value) {
        const age =
            Math.max(
                0,
                Math.floor(
                    (
                        Date.now()
                        -
                        new Date(
                            value
                        ).getTime()
                    )
                    /
                    1000
                )
            );


        if (age < 60) {
            return (
                `actualizado hace ${age} s`
            );
        }


        if (age < 3600) {
            return (
                'actualizado hace '
                +
                Math.floor(
                    age / 60
                )
                +
                ' min'
            );
        }


        if (age < 86400) {
            return (
                'actualizado hace '
                +
                Math.floor(
                    age / 3600
                )
                +
                ' h'
            );
        }


        return (
            'actualizado hace '
            +
            Math.floor(
                age / 86400
            )
            +
            ' d'
        );
    }


    function healthy(data) {
        const providers =
            data.cmm.providers;


        return (
            data.cmm.broker.state
            ===
            'OK'
            &&
            data.cmm.refresh.timer_state
            ===
            'ACTIVE'
            &&
            data.cmm.refresh.last_result
            ===
            'SUCCESS'
            &&
            providers.total === 6
            &&
            providers.success === 6
            &&
            providers.error === 0
            &&
            providers.unknown === 0
        );
    }


    function setStatus(
        state,
        value,
        message
    ) {
        elements.status.classList.remove(
            'cmm-system-status--loading',
            'cmm-system-status--healthy',
            'cmm-system-status--degraded',
            'cmm-system-status--error'
        );


        elements.status.classList.add(
            `cmm-system-status--${state}`
        );


        setText(
            elements.statusValue,
            value
        );

        setText(
            elements.statusText,
            message
        );
    }


    function clearError() {
        elements.error.hidden = true;
        elements.error.textContent = '';
    }


    function showError(message) {
        elements.error.textContent =
            String(message);

        elements.error.hidden = false;
    }


    function renderProviders(
        providers
    ) {
        elements.providerList.replaceChildren();


        for (
            const item
            of providers.items
        ) {
            const row =
                document.createElement(
                    'div'
                );

            row.className =
                'cmm-system-provider-item';


            const dot =
                document.createElement(
                    'span'
                );

            dot.className =
                (
                    'cmm-system-provider-dot '
                    +
                    (
                        item.result === 'SUCCESS'
                            ?
                            'cmm-system-provider-dot--success'
                            :
                            item.result === 'ERROR'
                                ?
                                'cmm-system-provider-dot--error'
                                :
                                'cmm-system-provider-dot--unknown'
                    )
                );

            dot.setAttribute(
                'aria-hidden',
                'true'
            );


            const name =
                document.createElement(
                    'strong'
                );

            name.textContent =
                PROVIDER_LABELS[
                    item.name
                ];


            const result =
                document.createElement(
                    'span'
                );

            result.className =
                'cmm-system-provider-result';

            result.textContent =
                item.result === 'SUCCESS'
                    ?
                    'OK'
                    :
                    item.result === 'ERROR'
                        ?
                        'Error'
                        :
                        'Desconocido';


            row.append(
                dot,
                name,
                result
            );

            elements.providerList.append(
                row
            );
        }
    }


    function render(data) {
        const isHealthy =
            healthy(data);

        const providers =
            data.cmm.providers;

        const memory =
            data.memory;

        const memoryWidth =
            Math.max(
                0,
                Math.min(
                    100,
                    Number(
                        memory.used_percent
                    )
                )
            );


        elements.healthCard.classList.remove(
            'cmm-system-metric--healthy',
            'cmm-system-metric--degraded'
        );

        elements.healthCard.classList.add(
            isHealthy
                ?
                'cmm-system-metric--healthy'
                :
                'cmm-system-metric--degraded'
        );


        setText(
            elements.health,
            isHealthy
                ? 'Saludable'
                : 'Degradado'
        );

        setText(
            elements.uptime,
            formatUptime(
                data.host.uptime_seconds
            )
        );

        setText(
            elements.memoryPercent,
            formatPercent(
                memory.used_percent
            )
        );

        setText(
            elements.providerRatio,
            (
                providers.success
                +
                '/'
                +
                providers.total
            )
        );


        setText(
            elements.os,
            data.host.operating_system
        );

        setText(
            elements.kernel,
            data.host.kernel_version
        );

        setText(
            elements.architecture,
            data.host.architecture
        );

        setText(
            elements.hostUptime,
            formatUptime(
                data.host.uptime_seconds
            )
        );


        setText(
            elements.cpuModel,
            data.cpu.model
        );

        setText(
            elements.logicalProcessors,
            data.cpu.logical_processors
        );

        setText(
            elements.load1,
            formatLoad(
                data.cpu.load_1
            )
        );

        setText(
            elements.load5,
            formatLoad(
                data.cpu.load_5
            )
        );

        setText(
            elements.load15,
            formatLoad(
                data.cpu.load_15
            )
        );


        setText(
            elements.memoryPercentDetail,
            formatPercent(
                memory.used_percent
            )
        );

        setText(
            elements.memoryUsed,
            formatBytes(
                memory.used_bytes
            )
        );

        setText(
            elements.memoryAvailable,
            formatBytes(
                memory.available_bytes
            )
        );

        setText(
            elements.memoryTotal,
            formatBytes(
                memory.total_bytes
            )
        );


        elements.memoryBarUsed.style.width =
            `${memoryWidth}%`;

        elements.memoryBar.setAttribute(
            'aria-label',
            (
                'Memoria usada '
                +
                formatPercent(
                    memory.used_percent
                )
            )
        );


        setText(
            elements.brokerState,
            data.cmm.broker.state
        );

        setText(
            elements.brokerVersion,
            data.cmm.broker.version
        );

        setText(
            elements.brokerMode,
            data.cmm.broker.mode
        );

        setText(
            elements.refreshTimer,
            data.cmm.refresh.timer_state
        );

        setText(
            elements.refreshResult,
            data.cmm.refresh.last_result
        );


        setText(
            elements.providerSummary,
            (
                providers.success
                +
                '/'
                +
                providers.total
                +
                ' OK'
            )
        );

        setText(
            elements.providerTotal,
            providers.total
        );

        setText(
            elements.providerSuccess,
            providers.success
        );

        setText(
            elements.providerError,
            providers.error
        );

        setText(
            elements.providerUnknown,
            providers.unknown
        );


        renderProviders(
            providers
        );


        setText(
            elements.captured,
            formatDateTime(
                data.snapshot.captured_at_utc
            )
        );

        elements.captured.dateTime =
            data.snapshot.captured_at_utc;


        if (isHealthy) {
            setStatus(
                'healthy',
                'OK',
                freshness(
                    data.snapshot.captured_at_utc
                )
            );
        } else {
            setStatus(
                'degraded',
                'Atención',
                (
                    'Estado degradado · '
                    +
                    freshness(
                        data.snapshot.captured_at_utc
                    )
                )
            );
        }
    }


    async function load() {
        if (loading) {
            return;
        }

        loading = true;

        setStatus(
            'loading',
            '…',
            'Consultando sistema…'
        );


        try {
            const response =
                await fetch(
                    API_ENDPOINT,
                    {
                        method:
                            'GET',

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
                validate(
                    await response.json()
                );


            render(data);
            clearError();

        } catch (error) {

            setStatus(
                'error',
                'Error',
                'No se pudo consultar el sistema'
            );

            showError(
                'No fue posible cargar la telemetría de Sistema.'
            );

            console.error(
                'CMM system:',
                error
            );

        } finally {
            loading = false;
        }
    }


    function start() {
        try {
            requireElements();

        } catch (error) {
            console.error(
                'CMM system:',
                error
            );

            return;
        }


        load();

        window.setInterval(
            load,
            REFRESH_MS
        );
    }


    if (
        document.readyState === 'loading'
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
