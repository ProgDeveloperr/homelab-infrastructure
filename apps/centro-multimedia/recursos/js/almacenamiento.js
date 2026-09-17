(() => {
    'use strict';


    const API_ENDPOINT =
        'api/almacenamiento.php';

    const SCHEMA =
        'cmm.storage.v1';

    const REFRESH_MS =
        30000;

    const STALE_SECONDS =
        600;


    const elements = {
        status:
            document.getElementById(
                'cmm-storage-status'
            ),

        statusValue:
            document.getElementById(
                'cmm-storage-status-value'
            ),

        statusText:
            document.getElementById(
                'cmm-storage-status-text'
            ),

        error:
            document.getElementById(
                'cmm-storage-error'
            ),

        captured:
            document.getElementById(
                'cmm-storage-captured'
            ),

        total:
            document.getElementById(
                'cmm-storage-total'
            ),

        used:
            document.getElementById(
                'cmm-storage-used'
            ),

        usedPercent:
            document.getElementById(
                'cmm-storage-used-percent'
            ),

        free:
            document.getElementById(
                'cmm-storage-free'
            ),

        freePercent:
            document.getElementById(
                'cmm-storage-free-percent'
            ),

        reserved:
            document.getElementById(
                'cmm-storage-reserved'
            ),

        reservedPercent:
            document.getElementById(
                'cmm-storage-reserved-percent'
            ),

        bar:
            document.getElementById(
                'cmm-storage-bar'
            ),

        barUsed:
            document.getElementById(
                'cmm-storage-bar-used'
            ),

        barFree:
            document.getElementById(
                'cmm-storage-bar-free'
            ),

        barReserved:
            document.getElementById(
                'cmm-storage-bar-reserved'
            ),

        library:
            document.getElementById(
                'cmm-storage-library'
            ),

        movies:
            document.getElementById(
                'cmm-storage-movies'
            ),

        series:
            document.getElementById(
                'cmm-storage-series'
            ),

        torrents:
            document.getElementById(
                'cmm-storage-torrents'
            ),

        allReferences:
            document.getElementById(
                'cmm-storage-all-references'
            ),

        uniqueLogical:
            document.getElementById(
                'cmm-storage-unique-logical'
            ),

        uniqueAllocated:
            document.getElementById(
                'cmm-storage-unique-allocated'
            ),

        uniqueObjects:
            document.getElementById(
                'cmm-storage-unique-objects'
            ),

        hardlinkGroups:
            document.getElementById(
                'cmm-storage-hardlink-groups'
            ),

        hardlinkShared:
            document.getElementById(
                'cmm-storage-hardlink-shared'
            ),

        referenceLibrary:
            document.getElementById(
                'cmm-storage-reference-library'
            ),

        referenceTorrent:
            document.getElementById(
                'cmm-storage-reference-torrent'
            ),

        referenceTotal:
            document.getElementById(
                'cmm-storage-reference-total'
            ),
    };


    let loading = false;


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
            Object.keys(value).sort();

        const wanted =
            expected.slice().sort();


        return (
            actual.length === wanted.length
            &&
            actual.every(
                (
                    key,
                    index
                ) =>
                    key === wanted[index]
            )
        );
    };


    const nonNegativeInteger =
        value => (
            Number.isInteger(value)
            &&
            value >= 0
        );


    const percentage =
        value => (
            Number.isFinite(value)
            &&
            value >= 0
            &&
            value <= 100
        );


    const round2 =
        value =>
            Math.round(
                (
                    value
                    +
                    Number.EPSILON
                )
                *
                100
            )
            /
            100;


    const validate = data => {

        if (
            !exactKeys(
                data,
                [
                    'schema',
                    'capacity',
                    'logical',
                    'physical',
                    'hardlinks',
                    'references',
                    'snapshot',
                ]
            )
            ||
            data.schema !== SCHEMA
        ) {
            throw new Error(
                'Contrato de almacenamiento inválido.'
            );
        }


        if (
            !exactKeys(
                data.capacity,
                [
                    'total_bytes',
                    'used_bytes',
                    'used_percent',
                    'free_bytes',
                    'free_percent',
                    'reserved_or_unavailable_bytes',
                ]
            )
        ) {
            throw new Error(
                'Capacidad inválida.'
            );
        }


        for (
            const key
            of [
                'total_bytes',
                'used_bytes',
                'free_bytes',
                'reserved_or_unavailable_bytes',
            ]
        ) {
            if (
                !nonNegativeInteger(
                    data.capacity[key]
                )
            ) {
                throw new Error(
                    'Capacidad inválida.'
                );
            }
        }


        for (
            const key
            of [
                'used_percent',
                'free_percent',
            ]
        ) {
            if (
                !percentage(
                    data.capacity[key]
                )
            ) {
                throw new Error(
                    'Porcentaje inválido.'
                );
            }
        }


        if (
            data.capacity.total_bytes <= 0
            ||
            (
                data.capacity.used_bytes
                +
                data.capacity.free_bytes
                +
                data.capacity.reserved_or_unavailable_bytes
            )
            !==
            data.capacity.total_bytes
        ) {
            throw new Error(
                'Invariante de capacidad inválida.'
            );
        }


        if (
            Math.abs(
                data.capacity.used_percent
                -
                round2(
                    (
                        data.capacity.used_bytes
                        *
                        100
                    )
                    /
                    data.capacity.total_bytes
                )
            )
            > 0.01
            ||
            Math.abs(
                data.capacity.free_percent
                -
                round2(
                    (
                        data.capacity.free_bytes
                        *
                        100
                    )
                    /
                    data.capacity.total_bytes
                )
            )
            > 0.01
        ) {
            throw new Error(
                'Invariante porcentual inválida.'
            );
        }


        if (
            !exactKeys(
                data.logical,
                [
                    'all_references_bytes',
                    'library_bytes',
                    'movies_bytes',
                    'series_bytes',
                    'torrents_bytes',
                ]
            )
        ) {
            throw new Error(
                'Contenido lógico inválido.'
            );
        }


        for (
            const key
            of Object.keys(
                data.logical
            )
        ) {
            if (
                !nonNegativeInteger(
                    data.logical[key]
                )
            ) {
                throw new Error(
                    'Contenido lógico inválido.'
                );
            }
        }


        if (
            data.logical.library_bytes
            !==
            (
                data.logical.movies_bytes
                +
                data.logical.series_bytes
            )
            ||
            data.logical.all_references_bytes
            !==
            (
                data.logical.library_bytes
                +
                data.logical.torrents_bytes
            )
        ) {
            throw new Error(
                'Invariante lógica inválida.'
            );
        }


        if (
            !exactKeys(
                data.physical,
                [
                    'unique_allocated_bytes',
                    'unique_logical_bytes',
                    'unique_objects',
                ]
            )
        ) {
            throw new Error(
                'Ocupación física inválida.'
            );
        }


        for (
            const key
            of Object.keys(
                data.physical
            )
        ) {
            if (
                !nonNegativeInteger(
                    data.physical[key]
                )
            ) {
                throw new Error(
                    'Ocupación física inválida.'
                );
            }
        }


        if (
            !exactKeys(
                data.hardlinks,
                [
                    'cross_scope_groups',
                    'shared_logical_bytes',
                ]
            )
            ||
            !nonNegativeInteger(
                data.hardlinks.cross_scope_groups
            )
            ||
            !nonNegativeInteger(
                data.hardlinks.shared_logical_bytes
            )
        ) {
            throw new Error(
                'Hardlinks inválidos.'
            );
        }


        if (
            data.hardlinks.shared_logical_bytes
            !==
            (
                data.logical.all_references_bytes
                -
                data.physical.unique_logical_bytes
            )
        ) {
            throw new Error(
                'Invariante de hardlinks inválida.'
            );
        }


        if (
            !exactKeys(
                data.references,
                [
                    'total',
                    'library',
                    'torrent',
                ]
            )
            ||
            !nonNegativeInteger(
                data.references.total
            )
            ||
            !nonNegativeInteger(
                data.references.library
            )
            ||
            !nonNegativeInteger(
                data.references.torrent
            )
            ||
            data.references.total
            !==
            (
                data.references.library
                +
                data.references.torrent
            )
        ) {
            throw new Error(
                'Referencias inválidas.'
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
            typeof data.snapshot.captured_at_utc
            !== 'string'
        ) {
            throw new Error(
                'Snapshot inválido.'
            );
        }


        const captured =
            new Date(
                data.snapshot.captured_at_utc
            );


        if (
            !Number.isFinite(
                captured.getTime()
            )
        ) {
            throw new Error(
                'Fecha de snapshot inválida.'
            );
        }


        return data;
    };


    const requireElements = () => {

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
    };


    const setText = (
        element,
        value
    ) => {
        element.textContent =
            value;
    };


    const bytes = value =>
        (
            new Intl.NumberFormat(
                'es-AR',
                {
                    minimumFractionDigits:
                        2,

                    maximumFractionDigits:
                        2,
                }
            ).format(
                value
                /
                1024
                /
                1024
                /
                1024
            )
            +
            ' GiB'
        );


    const integer = value =>
        new Intl.NumberFormat(
            'es-AR'
        ).format(
            value
        );


    const percent = value =>
        (
            new Intl.NumberFormat(
                'es-AR',
                {
                    minimumFractionDigits:
                        2,

                    maximumFractionDigits:
                        2,
                }
            ).format(
                value
            )
            +
            ' %'
        );


    const dateTime = value => {

        const date =
            new Date(value);


        return new Intl.DateTimeFormat(
            'es-AR',
            {
                dateStyle:
                    'short',

                timeStyle:
                    'short',
            }
        ).format(
            date
        );
    };


    const ageSeconds = value =>
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


    const ageText = value => {

        if (value < 60) {
            return (
                `Actualizado hace ${value} s`
            );
        }


        if (value < 3600) {
            return (
                'Actualizado hace '
                +
                Math.floor(
                    value / 60
                )
                +
                ' min'
            );
        }


        return (
            'Actualizado hace '
            +
            Math.floor(
                value / 3600
            )
            +
            ' h'
        );
    };


    const setStatus = (
        state,
        value,
        text
    ) => {

        elements.status.dataset.state =
            state;

        setText(
            elements.statusValue,
            value
        );

        setText(
            elements.statusText,
            text
        );
    };


    const showError = message => {

        setText(
            elements.error,
            message
        );

        elements.error.hidden =
            false;
    };


    const clearError = () => {

        elements.error.hidden =
            true;

        setText(
            elements.error,
            ''
        );
    };


    const render = data => {

        const capacity =
            data.capacity;

        const logical =
            data.logical;

        const physical =
            data.physical;

        const hardlinks =
            data.hardlinks;

        const references =
            data.references;

        const snapshot =
            data.snapshot;


        const reservedPercent =
            round2(
                (
                    capacity.reserved_or_unavailable_bytes
                    *
                    100
                )
                /
                capacity.total_bytes
            );


        setText(
            elements.total,
            bytes(
                capacity.total_bytes
            )
        );

        setText(
            elements.used,
            bytes(
                capacity.used_bytes
            )
        );

        setText(
            elements.usedPercent,
            percent(
                capacity.used_percent
            )
        );

        setText(
            elements.free,
            bytes(
                capacity.free_bytes
            )
        );

        setText(
            elements.freePercent,
            percent(
                capacity.free_percent
            )
        );

        setText(
            elements.reserved,
            bytes(
                capacity.reserved_or_unavailable_bytes
            )
        );

        setText(
            elements.reservedPercent,
            percent(
                reservedPercent
            )
        );


        elements.barUsed.style.width =
            `${capacity.used_percent}%`;

        elements.barFree.style.width =
            `${capacity.free_percent}%`;

        elements.barReserved.style.width =
            `${reservedPercent}%`;


        elements.bar.setAttribute(
            'aria-label',
            (
                'Capacidad: '
                +
                percent(
                    capacity.used_percent
                )
                +
                ' usado, '
                +
                percent(
                    capacity.free_percent
                )
                +
                ' libre y '
                +
                percent(
                    reservedPercent
                )
                +
                ' reservado o no disponible'
            )
        );


        setText(
            elements.library,
            bytes(
                logical.library_bytes
            )
        );

        setText(
            elements.movies,
            bytes(
                logical.movies_bytes
            )
        );

        setText(
            elements.series,
            bytes(
                logical.series_bytes
            )
        );

        setText(
            elements.torrents,
            bytes(
                logical.torrents_bytes
            )
        );

        setText(
            elements.allReferences,
            bytes(
                logical.all_references_bytes
            )
        );


        setText(
            elements.uniqueLogical,
            bytes(
                physical.unique_logical_bytes
            )
        );

        setText(
            elements.uniqueAllocated,
            bytes(
                physical.unique_allocated_bytes
            )
        );

        setText(
            elements.uniqueObjects,
            integer(
                physical.unique_objects
            )
        );


        setText(
            elements.hardlinkGroups,
            integer(
                hardlinks.cross_scope_groups
            )
        );

        setText(
            elements.hardlinkShared,
            bytes(
                hardlinks.shared_logical_bytes
            )
        );


        setText(
            elements.referenceLibrary,
            integer(
                references.library
            )
        );

        setText(
            elements.referenceTorrent,
            integer(
                references.torrent
            )
        );

        setText(
            elements.referenceTotal,
            integer(
                references.total
            )
        );


        setText(
            elements.captured,
            (
                'Capturado '
                +
                dateTime(
                    snapshot.captured_at_utc
                )
            )
        );


        const age =
            ageSeconds(
                snapshot.captured_at_utc
            );


        setStatus(
            (
                age <= STALE_SECONDS
                ? 'ready'
                : 'stale'
            ),
            (
                age <= STALE_SECONDS
                ? 'OK'
                : '!'
            ),
            ageText(age)
        );
    };


    const load = async () => {

        if (loading) {
            return;
        }


        loading = true;


        try {
            setStatus(
                'loading',
                '…',
                'Consultando almacenamiento…'
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
                validate(
                    await response.json()
                );


            render(data);

        } catch (error) {

            setStatus(
                'error',
                'Error',
                'No se pudo actualizar'
            );

            showError(
                'No fue posible consultar el estado de almacenamiento.'
            );

            console.error(
                'CMM storage:',
                error
            );

        } finally {
            loading = false;
        }
    };


    const start = () => {

        try {
            requireElements();

        } catch (error) {

            console.error(
                'CMM storage:',
                error
            );

            return;
        }


        load();


        window.setInterval(
            load,
            REFRESH_MS
        );
    };


    if (
        document.readyState
        === 'loading'
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
