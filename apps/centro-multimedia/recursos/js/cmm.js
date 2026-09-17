(() => {
    'use strict';

    const API =
        'api/almacenamiento.php';

    const SCHEMA =
        'cmm.storage.v1';

    const REFRESH_MS =
        60000;


    const byId = (id) =>
        document.getElementById(id);


    const bytes = (value) => {

        if (
            !Number.isFinite(value)
            || value < 0
        ) {
            return '—';
        }

        const units = [
            'B',
            'KiB',
            'MiB',
            'GiB',
            'TiB',
        ];

        let n = value;
        let unit = 0;

        while (
            n >= 1024
            && unit < units.length - 1
        ) {
            n /= 1024;
            unit += 1;
        }

        const digits =
            unit >= 3
                ? 2
                : unit === 0
                    ? 0
                    : 1;

        return new Intl.NumberFormat(
            'es-AR',
            {
                maximumFractionDigits:
                    digits,
            }
        ).format(n)
            + ' '
            + units[unit];
    };


    const integer = (value) => {

        if (!Number.isFinite(value)) {
            return '—';
        }

        return new Intl.NumberFormat(
            'es-AR'
        ).format(value);
    };


    const percent = (value) => {

        if (!Number.isFinite(value)) {
            return '—';
        }

        return new Intl.NumberFormat(
            'es-AR',
            {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
            }
        ).format(value)
            + ' %';
    };


    const ageSeconds = (capturedAt) => {

        const captured =
            Date.parse(capturedAt);

        if (!Number.isFinite(captured)) {
            return NaN;
        }

        return Math.max(
            0,
            Math.floor(
                (
                    Date.now()
                    -
                    captured
                )
                /
                1000
            )
        );
    };


    const ageText = (seconds) => {

        if (!Number.isFinite(seconds)) {
            return 'antigüedad desconocida';
        }

        if (seconds < 60) {
            return 'actualizado hace menos de 1 min';
        }

        const minutes =
            Math.floor(seconds / 60);

        if (minutes < 60) {
            return `actualizado hace ${minutes} min`;
        }

        const hours =
            Math.floor(minutes / 60);

        return `actualizado hace ${hours} h`;
    };


    const setText = (id, value) => {

        const element =
            byId(id);

        if (element) {
            element.textContent = value;
        }
    };


    const validate = (data) => {

        if (
            !data
            || typeof data !== 'object'
            || data.schema !== SCHEMA
        ) {
            throw new Error(
                'Contrato de almacenamiento inválido.'
            );
        }

        const required = [
            'snapshot',
            'capacity',
            'logical',
            'physical',
            'hardlinks',
            'references',
        ];

        for (const key of required) {

            if (
                !data[key]
                || typeof data[key] !== 'object'
            ) {
                throw new Error(
                    `Sección ausente: ${key}`
                );
            }
        }

        return data;
    };


    const setStatus = (
        state,
        text
    ) => {

        const status =
            byId('cmm-estado-datos');

        if (!status) {
            return;
        }

        status.className =
            `cmm-data-status cmm-data-status--${state}`;

        const label =
            status.querySelector(
                'span:last-child'
            );

        if (label) {
            label.textContent = text;
        }
    };


    const render = (data) => {

        const snapshot =
            data.snapshot;

        const capacity =
            data.capacity;

        const logical =
            data.logical;

        const physical =
            data.physical;

        const hardlinks =
            data.hardlinks;

        const refs =
            data.references;


        setText(
            'snapshot-id',
            'Actual'
        );

        setText(
            'snapshot-time',
            snapshot.captured_at_utc
        );


        setText(
            'capacity-used',
            bytes(
                capacity.used_bytes
            )
        );

        setText(
            'capacity-total',
            `de ${bytes(
                capacity.total_bytes
            )}`
        );


        setText(
            'unique-allocated',
            bytes(
                physical.unique_allocated_bytes
            )
        );

        setText(
            'unique-objects',
            `${integer(
                physical.unique_objects
            )} objetos físicos únicos`
        );


        setText(
            'hardlink-shared',
            bytes(
                hardlinks.shared_logical_bytes
            )
        );

        setText(
            'hardlink-groups',
            `${integer(
                hardlinks.cross_scope_groups
            )} grupos compartidos`
        );


        setText(
            'library-logical',
            bytes(
                logical.library_bytes
            )
        );

        setText(
            'library-split',
            `${bytes(
                logical.movies_bytes
            )} películas · ${bytes(
                logical.series_bytes
            )} series`
        );


        setText(
            'capacity-percent',
            percent(
                capacity.used_percent
            )
        );

        setText(
            'legend-used',
            bytes(
                capacity.used_bytes
            )
        );

        setText(
            'legend-free',
            bytes(
                capacity.free_bytes
            )
        );

        setText(
            'legend-reserved',
            bytes(
                capacity
                    .reserved_or_unavailable_bytes
            )
        );


        const usedPercent =
            Math.max(
                0,
                Math.min(
                    100,
                    Number(
                        capacity.used_percent
                    ) || 0
                )
            );

        const bar =
            byId(
                'capacity-progress'
            );

        if (bar) {
            bar.style.width =
                `${usedPercent}%`;
        }

        const progress =
            document.querySelector(
                '.cmm-progress'
            );

        if (progress) {
            progress.setAttribute(
                'aria-valuenow',
                String(usedPercent)
            );
        }


        setText(
            'movies-logical',
            bytes(
                logical.movies_bytes
            )
        );

        setText(
            'series-logical',
            bytes(
                logical.series_bytes
            )
        );

        setText(
            'torrents-logical',
            bytes(
                logical.torrents_bytes
            )
        );

        setText(
            'references-logical',
            bytes(
                logical
                    .all_references_bytes
            )
        );


        setText(
            'references-total',
            integer(
                refs.total
            )
        );

        setText(
            'references-library',
            integer(
                refs.library
            )
        );

        setText(
            'references-torrent',
            integer(
                refs.torrent
            )
        );

        setText(
            'physical-objects',
            integer(
                physical.unique_objects
            )
        );


        const age =
            ageSeconds(
                snapshot.captured_at_utc
            );

        setStatus(
            age <= 600
                ? 'ok'
                : 'stale',
            ageText(age)
        );


        const error =
            byId('cmm-error');

        if (error) {
            error.hidden = true;
            error.textContent = '';
        }
    };


    const showError = (error) => {

        setStatus(
            'error',
            'Datos no disponibles'
        );

        const box =
            byId('cmm-error');

        if (box) {
            box.hidden = false;

            box.textContent =
                error instanceof Error
                    ? error.message
                    : 'No se pudo consultar el estado.';
        }
    };


    const load = async () => {

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

            render(data);

        } catch (error) {
            showError(error);
        }
    };


    document.addEventListener(
        'DOMContentLoaded',
        () => {

            load();

            window.setInterval(
                load,
                REFRESH_MS
            );

        }
    );

})();
