(() => {
    'use strict';

    const root =
        document.getElementById(
            'cmm-library-root'
        );

    if (!root) {
        return;
    }

    const API =
        'api/biblioteca.php';

    const ACTION_API =
        'api/acciones.php';

    const PROPOSAL_API =
        'api/acciones-propuesta.php';

    const SCHEMA =
        'cmm.library.v1';

    const REFRESH_MS =
        60000;

    let items = [];


    const byId = (id) =>
        document.getElementById(id);


    const integer = (value) => {
        if (!Number.isFinite(value)) {
            return '—';
        }

        return new Intl.NumberFormat(
            'es-AR'
        ).format(value);
    };


    const normalized = (value) =>
        String(value ?? '')
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            )
            .toLocaleLowerCase(
                'es-AR'
            );


    const typeLabel = (type) =>
        type === 'series'
            ? 'Serie'
            : 'Película';


    const ownershipLabel = (value) => {
        if (value === 'ARR') {
            return 'Gestionado';
        }

        if (value === 'MANUAL') {
            return 'Manual';
        }

        return 'Desconocido';
    };


    const stateLabel = (state) => {
        const labels = {
            ARR_MANAGED_AVAILABLE:
                'Disponible',

            ARR_MANAGED_JELLYFIN_MISSING:
                'Falta en Jellyfin',

            ARR_MANAGED_FILESYSTEM_MISSING:
                'Archivo faltante',

            ARR_REGISTERED_NO_FILE:
                'Registrado · sin archivo',

            ARR_STATE_DRIFT:
                'Estado inconsistente',

            MANUAL_AVAILABLE:
                'Disponible',

            JELLYFIN_FILESYSTEM_MISSING:
                'Jellyfin · archivo faltante',

            FILESYSTEM_ONLY:
                'Sólo filesystem',

            UNKNOWN:
                'Desconocido',
        };

        return labels[state]
            ?? state
            ?? 'Desconocido';
    };


    const setText = (id, value) => {
        const element = byId(id);

        if (element) {
            element.textContent = value;
        }
    };


    const create = (
        tag,
        className,
        text
    ) => {
        const element =
            document.createElement(tag);

        if (className) {
            element.className =
                className;
        }

        if (text !== undefined) {
            element.textContent =
                text;
        }

        return element;
    };


    const setStatus = (
        state,
        count,
        text
    ) => {
        const status =
            byId('cmm-library-status');

        if (!status) {
            return;
        }

        status.className =
            'cmm-library-status '
            +
            `cmm-library-status--${state}`;

        setText(
            'cmm-library-status-count',
            count
        );

        setText(
            'cmm-library-status-text',
            text
        );
    };


    const validate = (data) => {
        if (
            !data
            ||
            typeof data !== 'object'
            ||
            data.schema !== SCHEMA
            ||
            !data.summary
            ||
            typeof data.summary !== 'object'
            ||
            !Array.isArray(data.items)
            ||
            data.summary.total
                !== data.items.length
        ) {
            throw new Error(
                'Contrato de biblioteca inválido.'
            );
        }

        return data;
    };


    const syncStateOptions = (summary) => {
        const select =
            byId('cmm-library-state');

        if (!select) {
            return;
        }

        const previous =
            select.value;

        while (
            select.options.length > 1
        ) {
            select.remove(1);
        }

        const states =
            Object.keys(
                summary.inventory_states
                ?? {}
            )
                .filter(
                    (state) =>
                        (
                            summary
                                .inventory_states[
                                    state
                                ]
                            ?? 0
                        ) > 0
                )
                .sort(
                    (a, b) =>
                        stateLabel(a)
                            .localeCompare(
                                stateLabel(b),
                                'es-AR'
                            )
                );

        for (const state of states) {
            const option =
                document.createElement(
                    'option'
                );

            option.value =
                state;

            option.textContent =
                `${stateLabel(state)} `
                +
                `(${integer(
                    summary
                        .inventory_states[
                            state
                        ]
                )})`;

            select.appendChild(
                option
            );
        }

        if (
            states.includes(previous)
        ) {
            select.value =
                previous;
        }
    };


    const renderSummary = (data) => {
        const available =
            data.items.filter(
                (item) =>
                    item.presence
                        ?.filesystem === true
            ).length;

        setText(
            'cmm-library-total',
            integer(
                data.summary.total
            )
        );

        setText(
            'cmm-library-available',
            integer(available)
        );

        setText(
            'cmm-library-arr',
            integer(
                data.summary
                    .ownership?.ARR
                ?? 0
            )
        );

        setText(
            'cmm-library-manual',
            integer(
                data.summary
                    .ownership?.MANUAL
                ?? 0
            )
        );
    };


    const presenceChip = (
        label,
        present
    ) => {
        const chip =
            create(
                'span',
                'cmm-library-presence '
                +
                (
                    present
                        ? 'cmm-library-presence--on'
                        : 'cmm-library-presence--off'
                )
            );

        const dot =
            create(
                'span',
                'cmm-library-presence-dot'
            );

        dot.setAttribute(
            'aria-hidden',
            'true'
        );

        chip.append(
            dot,
            document.createTextNode(
                label
            )
        );

        return chip;
    };


    const episodesText = (item) => {
        if (item.type !== 'series') {
            return null;
        }

        const episodes =
            item.episodes;

        if (
            !episodes
            ||
            episodes.source
                === 'UNAVAILABLE'
        ) {
            return 'Episodios no disponibles';
        }

        return (
            `${integer(
                episodes.filesystem_present
            )}`
            +
            ' / '
            +
            `${integer(
                episodes.total
            )}`
            +
            ' episodios presentes'
        );
    };



    // CMM_K7D9A_R2_BEGIN
    //
    // UI productiva de preflight DELETE.
    //
    // Esta fase NO crea acciones,
    // NO autoriza ejecución,
    // NO invoca executor y
    // NO elimina archivos.

    const deletePreflightBusy =
        new Set();

    const PROPOSAL_UI_STAGING_ONLY =
        false;

    const deleteProposalBusy =
        new Set();


    const isDeletePreflightCandidate =
        (item) =>
            (
                item.type === 'movie'
                &&
                item.ownership === 'MANUAL'
                &&
                item.inventory_state
                    === 'MANUAL_AVAILABLE'
                &&
                item.pipeline_state
                    === 'IDLE'
                &&
                item.presence?.arr
                    === false
                &&
                item.presence?.filesystem
                    === true
                &&
                item.presence?.jellyfin
                    === true
            );


    const ensureDeleteUiStyle = () => {

        if (
            document.getElementById(
                'cmm-delete-preflight-style'
            )
        ) {
            return;
        }

        const style =
            document.createElement(
                'style'
            );

        style.id =
            'cmm-delete-preflight-style';

        style.textContent = `
            .cmm-library-action-row {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                margin-top: 0.85rem;
                padding-top: 0.75rem;
                border-top:
                    1px solid
                    rgba(148,163,184,.18);
            }

            .cmm-library-delete-preflight {
                appearance: none;
                border:
                    1px solid
                    rgba(220,38,38,.55);
                background:
                    rgba(127,29,29,.12);
                color: inherit;
                border-radius: 0.55rem;
                padding:
                    0.48rem
                    0.75rem;
                font: inherit;
                font-size: 0.82rem;
                font-weight: 650;
                cursor: pointer;
                transition:
                    background .15s ease,
                    border-color .15s ease,
                    opacity .15s ease;
            }

            .cmm-library-delete-preflight:hover {
                background:
                    rgba(220,38,38,.20);
                border-color:
                    rgba(220,38,38,.8);
            }

            .cmm-library-delete-preflight:disabled {
                cursor: wait;
                opacity: .6;
            }

            .cmm-delete-preflight-overlay {
                position: fixed;
                inset: 0;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background:
                    rgba(0,0,0,.62);
            }

            .cmm-delete-preflight-overlay[hidden] {
                display: none;
            }

            .cmm-delete-preflight-dialog {
                width: min(
                    100%,
                    31rem
                );
                max-height: 85vh;
                overflow: auto;
                border:
                    1px solid
                    rgba(148,163,184,.25);
                border-radius: .85rem;
                padding: 1.15rem;
                background:
                    #111827;
                color:
                    #f8fafc;
                box-shadow:
                    0 24px 70px
                    rgba(0,0,0,.45);
            }

            .cmm-delete-preflight-dialog h3 {
                margin:
                    0
                    0
                    .7rem;
                font-size: 1.05rem;
            }

            .cmm-delete-preflight-dialog p {
                margin:
                    .45rem
                    0;
                line-height: 1.5;
            }

            .cmm-delete-preflight-result {
                margin-top: .8rem;
                padding: .75rem;
                border-radius: .6rem;
                background:
                    rgba(148,163,184,.10);
                font-size: .88rem;
            }

            .cmm-delete-preflight-result--ok {
                border:
                    1px solid
                    rgba(34,197,94,.42);
            }

            .cmm-delete-preflight-result--blocked {
                border:
                    1px solid
                    rgba(239,68,68,.42);
            }

            .cmm-delete-preflight-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 1rem;
            }

            .cmm-delete-proposal-create {
                appearance: none;
                border:
                    1px solid
                    rgba(59,130,246,.55);
                background:
                    rgba(30,64,175,.16);
                color: inherit;
                border-radius: .55rem;
                padding:
                    .48rem
                    .85rem;
                font: inherit;
                font-weight: 650;
                cursor: pointer;
                margin-right: .6rem;
            }

            .cmm-delete-proposal-create:disabled {
                cursor: not-allowed;
                opacity: .55;
            }

            .cmm-delete-preflight-close {
                appearance: none;
                border:
                    1px solid
                    rgba(148,163,184,.32);
                background:
                    rgba(148,163,184,.10);
                color: inherit;
                border-radius: .55rem;
                padding:
                    .48rem
                    .85rem;
                font: inherit;
                cursor: pointer;
            }
        `;

        document.head.appendChild(
            style
        );
    };


    const ensureDeleteDialog = () => {

        ensureDeleteUiStyle();

        let overlay =
            byId(
                'cmm-delete-preflight-overlay'
            );

        if (overlay) {
            return overlay;
        }

        overlay =
            create(
                'div',
                'cmm-delete-preflight-overlay'
            );

        overlay.id =
            'cmm-delete-preflight-overlay';

        overlay.hidden = true;

        overlay.setAttribute(
            'role',
            'presentation'
        );

        const dialog =
            create(
                'section',
                'cmm-delete-preflight-dialog'
            );

        dialog.setAttribute(
            'role',
            'dialog'
        );

        dialog.setAttribute(
            'aria-modal',
            'true'
        );

        dialog.setAttribute(
            'aria-labelledby',
            'cmm-delete-preflight-title'
        );


        const title =
            create(
                'h3',
                '',
                'Comprobar eliminación'
            );

        title.id =
            'cmm-delete-preflight-title';


        const media =
            create(
                'p',
                ''
            );

        media.id =
            'cmm-delete-preflight-media';


        const result =
            create(
                'div',
                'cmm-delete-preflight-result'
            );

        result.id =
            'cmm-delete-preflight-result';


        const warning =
            create(
                'p',
                '',
                'Esta fase sólo verifica elegibilidad. '
                +
                'No existe confirmación ejecutable '
                +
                'y ningún archivo será eliminado.'
            );

        warning.id =
            'cmm-delete-preflight-warning';


        const actions =
            create(
                'div',
                'cmm-delete-preflight-actions'
            );


        const proposal =
            create(
                'button',
                'cmm-delete-proposal-create',
                'Crear solicitud'
            );

        proposal.id =
            'cmm-delete-proposal-create';

        proposal.type =
            'button';

        proposal.hidden =
            true;

        proposal.disabled =
            true;


        const close =
            create(
                'button',
                'cmm-delete-preflight-close',
                'Cerrar'
            );

        close.type =
            'button';


        const hide = () => {
            overlay.hidden = true;
        };


        close.addEventListener(
            'click',
            hide
        );


        overlay.addEventListener(
            'click',
            (event) => {
                if (
                    event.target
                    === overlay
                ) {
                    hide();
                }
            }
        );


        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key === 'Escape'
                    &&
                    !overlay.hidden
                ) {
                    hide();
                }
            }
        );


        actions.append(
            proposal,
            close
        );


        dialog.append(
            title,
            media,
            result,
            warning,
            actions
        );


        overlay.appendChild(
            dialog
        );


        document.body.appendChild(
            overlay
        );


        return overlay;
    };


    const showDeletePreflight =
        (
            item,
            data,
            error
        ) => {

            const overlay =
                ensureDeleteDialog();

            const title =
                byId(
                    'cmm-delete-preflight-title'
                );

            const media =
                byId(
                    'cmm-delete-preflight-media'
                );

            const result =
                byId(
                    'cmm-delete-preflight-result'
                );


            media.textContent =
                `${item.title} · ID #${item.id}`;


            if (error) {

                title.textContent =
                    'No se pudo verificar';

                result.className =
                    'cmm-delete-preflight-result '
                    +
                    'cmm-delete-preflight-result--blocked';

                result.textContent =
                    error instanceof Error
                        ? error.message
                        : 'Preflight no disponible.';

            } else if (
                data?.eligible === true
            ) {

                title.textContent =
                    'Preflight aprobado';

                result.className =
                    'cmm-delete-preflight-result '
                    +
                    'cmm-delete-preflight-result--ok';

                result.textContent =
                    'La película cumple todas las '
                    +
                    'condiciones para preparar una '
                    +
                    'eliminación futura. '
                    +
                    'No se creó ninguna acción y '
                    +
                    'no se ejecutó ningún borrado.';

            } else {

                title.textContent =
                    'Eliminación bloqueada';

                result.className =
                    'cmm-delete-preflight-result '
                    +
                    'cmm-delete-preflight-result--blocked';

                const reasons =
                    Array.isArray(
                        data?.reasons
                    )
                        ? data.reasons
                        : [];

                result.textContent =
                    reasons.length
                        ? (
                            'El servidor bloqueó '
                            +
                            'el preflight: '
                            +
                            reasons.join(', ')
                        )
                        : (
                            'El servidor bloqueó '
                            +
                            'el preflight.'
                        );
            }


            const proposalButton =
                byId(
                    'cmm-delete-proposal-create'
                );

            const warning =
                byId(
                    'cmm-delete-preflight-warning'
                );

            if (proposalButton) {
                proposalButton.hidden =
                    true;

                proposalButton.disabled =
                    true;

                proposalButton.textContent =
                    'Crear solicitud';
            }

            if (warning) {
                warning.textContent =
                    'Esta fase sólo verifica elegibilidad. '
                    +
                    'No existe confirmación ejecutable '
                    +
                    'y ningún archivo será eliminado.';
            }

            overlay.hidden = false;
        };


    const stageDeleteProposalUi =
        async (item) => {

            const proposalButton =
                byId(
                    'cmm-delete-proposal-create'
                );

            const warning =
                byId(
                    'cmm-delete-preflight-warning'
                );

            if (
                !proposalButton
                ||
                !warning
            ) {
                return;
            }

            proposalButton.hidden =
                false;

            proposalButton.disabled =
                true;

            proposalButton.textContent =
                'Comprobando disponibilidad…';

            try {

                const response =
                    await fetch(
                        PROPOSAL_API,
                        {
                            method:
                                'POST',

                            cache:
                                'no-store',

                            credentials:
                                'same-origin',

                            headers: {
                                Accept:
                                    'application/json',

                                'Content-Type':
                                    'application/json',
                            },

                            body:
                                JSON.stringify({
                                    operation:
                                        'GET_PROPOSAL_TOKEN',
                                }),
                        }
                    );

                const data =
                    await response.json();

                if (
                    !response.ok
                    ||
                    data?.schema
                        !==
                        'cmm.actions.proposal.v1'
                    ||
                    data?.ok
                        !== true
                    ||
                    data?.operation
                        !==
                        'GET_PROPOSAL_TOKEN'
                    ||
                    typeof data?.csrf_token
                        !== 'string'
                    ||
                    !/^[a-f0-9]{64}$/.test(
                        data.csrf_token
                    )
                    ||
                    data?.proposal_creation_enabled
                        !== true
                    ||
                    data?.action_created
                        !== false
                    ||
                    data?.database_mutation
                        !== false
                    ||
                    data?.authorization_issued
                        !== false
                    ||
                    data?.executor_invoked
                        !== false
                    ||
                    data?.filesystem_mutation
                        !== false
                    ||
                    data?.irreversible_boundary_crossed
                        !== false
                    ||
                    data?.permanent_delete
                        !== false
                ) {
                    throw new Error(
                        'Contrato de propuesta inválido.'
                    );
                }

                if (
                    PROPOSAL_UI_STAGING_ONLY
                    !== false
                ) {
                    throw new Error(
                        'Modo de activación inválido.'
                    );
                }

                const randomBytes =
                    new Uint8Array(
                        16
                    );

                crypto.getRandomValues(
                    randomBytes
                );

                const randomHex =
                    Array.from(
                        randomBytes,
                        (value) =>
                            value
                                .toString(16)
                                .padStart(2, '0')
                    ).join('');

                const requestId =
                    'K7D9B_UI_'
                    +
                    randomHex;

                proposalButton.dataset
                    .requestId =
                        requestId;

                proposalButton.dataset
                    .csrfToken =
                        data.csrf_token;

                proposalButton.dataset
                    .mediaId =
                        String(
                            item.id
                        );

                proposalButton.onclick =
                    () => {
                        runCreateDeleteProposal(
                            item,
                            proposalButton
                        );
                    };

                proposalButton.textContent =
                    'Crear solicitud';

                proposalButton.disabled =
                    false;

                warning.textContent =
                    'Crear la solicitud registrará '
                    +
                    'una acción PENDING_CONFIRMATION. '
                    +
                    'Esto NO autoriza la eliminación, '
                    +
                    'NO ejecuta ningún borrado y '
                    +
                    'NO modifica archivos.';

            } catch (error) {

                proposalButton.textContent =
                    'Crear solicitud no disponible';

                proposalButton.disabled =
                    true;

                warning.textContent =
                    error instanceof Error
                        ? error.message
                        : (
                            'No se pudo preparar '
                            +
                            'la solicitud.'
                        );
            }
        };


    const runCreateDeleteProposal =
        async (
            item,
            button
        ) => {

            if (
                deleteProposalBusy.has(
                    item.id
                )
            ) {
                return;
            }

            const csrfToken =
                button.dataset
                    .csrfToken
                ?? '';

            const requestId =
                button.dataset
                    .requestId
                ?? '';

            const mediaId =
                Number(
                    button.dataset
                        .mediaId
                );

            if (
                mediaId !== item.id
                ||
                !/^[a-f0-9]{64}$/.test(
                    csrfToken
                )
                ||
                !/^K7D9B_UI_[a-f0-9]{32}$/.test(
                    requestId
                )
            ) {
                throw new Error(
                    'Contexto de solicitud inválido.'
                );
            }

            deleteProposalBusy.add(
                item.id
            );

            const title =
                byId(
                    'cmm-delete-preflight-title'
                );

            const result =
                byId(
                    'cmm-delete-preflight-result'
                );

            const warning =
                byId(
                    'cmm-delete-preflight-warning'
                );

            button.disabled =
                true;

            button.textContent =
                'Creando solicitud…';

            try {

                const response =
                    await fetch(
                        PROPOSAL_API,
                        {
                            method:
                                'POST',

                            cache:
                                'no-store',

                            credentials:
                                'same-origin',

                            headers: {
                                Accept:
                                    'application/json',

                                'Content-Type':
                                    'application/json',
                            },

                            body:
                                JSON.stringify({
                                    operation:
                                        'CREATE_DELETE_PROPOSAL',

                                    media_item_id:
                                        item.id,

                                    request_id:
                                        requestId,

                                    csrf_token:
                                        csrfToken,
                                }),
                        }
                    );

                const data =
                    await response.json();

                if (
                    !response.ok
                    ||
                    data?.schema
                        !==
                        'cmm.actions.proposal.v1'
                    ||
                    data?.ok
                        !== true
                    ||
                    data?.operation
                        !==
                        'CREATE_DELETE_PROPOSAL'
                    ||
                    data?.proposal_creation_enabled
                        !== true
                    ||
                    !Number.isInteger(
                        data?.action_id
                    )
                    ||
                    data?.status
                        !==
                        'PENDING_CONFIRMATION'
                    ||
                    data?.authorization_issued
                        !== false
                    ||
                    data?.executor_invoked
                        !== false
                    ||
                    data?.filesystem_mutation
                        !== false
                    ||
                    data?.irreversible_boundary_crossed
                        !== false
                    ||
                    data?.permanent_delete
                        !== false
                ) {
                    const message =
                        (
                            typeof data?.error
                            === 'string'
                        )
                            ? data.error
                            : (
                                'Contrato de creación '
                                +
                                'inválido.'
                            );

                    throw new Error(
                        message
                    );
                }

                if (title) {
                    title.textContent =
                        'Solicitud creada';
                }

                if (result) {
                    result.className =
                        'cmm-delete-preflight-result '
                        +
                        'cmm-delete-preflight-result--ok';

                    result.textContent =
                        `Acción #${data.action_id} `
                        +
                        'registrada como '
                        +
                        'PENDING_CONFIRMATION. '
                        +
                        'Todavía no está autorizada '
                        +
                        'para eliminar.';
                }

                if (warning) {
                    warning.textContent =
                        'No se eliminó ningún archivo. '
                        +
                        'No se emitió autorización '
                        +
                        'irreversible y no se invocó '
                        +
                        'ningún executor.';
                }

                button.textContent =
                    `Solicitud #${data.action_id} creada`;

                button.disabled =
                    true;

                button.onclick =
                    null;

            } catch (error) {

                if (title) {
                    title.textContent =
                        'No se pudo crear la solicitud';
                }

                if (result) {
                    result.className =
                        'cmm-delete-preflight-result '
                        +
                        'cmm-delete-preflight-result--blocked';

                    result.textContent =
                        error instanceof Error
                            ? error.message
                            : (
                                'Error creando '
                                +
                                'la solicitud.'
                            );
                }

                if (warning) {
                    warning.textContent =
                        'No se considera creada una '
                        +
                        'solicitud hasta recibir una '
                        +
                        'respuesta válida del servidor.';
                }

                button.textContent =
                    'Reintentar crear solicitud';

                button.disabled =
                    false;

            } finally {

                deleteProposalBusy.delete(
                    item.id
                );
            }
        };


    const runDeletePreflight =
        async (
            item,
            button
        ) => {

            if (
                deletePreflightBusy.has(
                    item.id
                )
            ) {
                return;
            }


            deletePreflightBusy.add(
                item.id
            );


            const originalText =
                button.textContent;


            button.disabled = true;
            button.textContent =
                'Verificando…';


            try {

                const response =
                    await fetch(
                        ACTION_API,
                        {
                            method: 'POST',

                            cache:
                                'no-store',

                            headers: {
                                Accept:
                                    'application/json',

                                'Content-Type':
                                    'application/json',
                            },

                            body:
                                JSON.stringify({
                                    operation:
                                        'DELETE_PREFLIGHT',

                                    media_item_id:
                                        item.id,
                                }),
                        }
                    );


                const data =
                    await response.json();


                if (
                    !response.ok
                    ||
                    data?.schema
                        !==
                        'cmm.actions.preflight.v1'
                    ||
                    data?.mode
                        !==
                        'PREFLIGHT_ONLY'
                    ||
                    data?.media?.id
                        !==
                        item.id
                    ||
                    data?.action_created
                        !== false
                    ||
                    data?.executor_invoked
                        !== false
                    ||
                    data?.filesystem_mutation
                        !== false
                    ||
                    data?.database_mutation
                        !== false
                    ||
                    data?.permanent_delete
                        !== false
                ) {
                    throw new Error(
                        'Contrato de preflight inválido.'
                    );
                }


                showDeletePreflight(
                    item,
                    data,
                    null
                );

                if (
                    data?.eligible
                    === true
                ) {
                    await stageDeleteProposalUi(
                        item
                    );
                }


            } catch (error) {

                showDeletePreflight(
                    item,
                    null,
                    error
                );


            } finally {

                deletePreflightBusy.delete(
                    item.id
                );

                button.disabled = false;

                button.textContent =
                    originalText;
            }
        };

    // CMM_K7D9A_R2_END

    // CMM_K7D9B_R1C_R2_UI_STAGING
    // CMM_K7D9B_R1C_R4B_WEB_ACTIVATION


    const createCard = (item) => {
        const card =
            create(
                'article',
                'cmm-library-card'
            );

        const poster =
            create(
                'div',
                'cmm-library-poster'
            );

        poster.setAttribute(
            'aria-hidden',
            'true'
        );


        const posterType =
            create(
                'span',
                'cmm-library-poster-type',
                typeLabel(item.type)
            );


        const posterInitial =
            create(
                'strong',
                'cmm-library-poster-initial',
                item.title
                    .trim()
                    .charAt(0)
                    .toLocaleUpperCase(
                        'es-AR'
                    )
                || '•'
            );


        poster.append(
            posterType,
            posterInitial
        );


        if (
            Number.isInteger(item.id)
            &&
            item.id > 0
        ) {

            const image =
                create(
                    'img',
                    'cmm-library-poster-image'
                );


            image.alt = '';
            image.loading = 'lazy';
            image.decoding = 'async';
            image.draggable = false;

            image.hidden = true;


            image.style.position =
                'absolute';

            image.style.top = '0';
            image.style.right = '0';
            image.style.bottom = '0';
            image.style.left = '0';

            image.style.width = '100%';
            image.style.height = '100%';

            image.style.objectFit =
                'cover';

            image.style.display =
                'block';

            image.style.pointerEvents =
                'none';


            poster.style.position =
                'relative';

            poster.style.overflow =
                'hidden';


            image.addEventListener(
                'load',
                () => {

                    posterType.hidden =
                        true;

                    posterInitial.hidden =
                        true;

                    image.hidden =
                        false;
                },
                {
                    once: true
                }
            );


            image.addEventListener(
                'error',
                () => {

                    image.remove();
                },
                {
                    once: true
                }
            );


            poster.append(
                image
            );


            image.src =
                `api/caratula.php?id=${item.id}`;
        }


        const body =
            create(
                'div',
                'cmm-library-card-body'
            );

        const badges =
            create(
                'div',
                'cmm-library-badges'
            );

        badges.append(
            create(
                'span',
                'cmm-library-badge '
                +
                'cmm-library-badge--type',
                typeLabel(item.type)
            ),

            create(
                'span',
                'cmm-library-badge '
                +
                (
                    item.ownership === 'ARR'
                        ? 'cmm-library-badge--arr'
                        : 'cmm-library-badge--manual'
                ),
                ownershipLabel(
                    item.ownership
                )
            )
        );

        body.append(
            badges,

            create(
                'h3',
                'cmm-library-card-title',
                item.title
            ),

            create(
                'div',
                'cmm-library-card-state',
                stateLabel(
                    item.inventory_state
                )
            )
        );


        const presence =
            create(
                'div',
                'cmm-library-presence-row'
            );

        presence.append(
            presenceChip(
                'Arr',
                item.presence.arr
            ),

            presenceChip(
                'Jellyfin',
                item.presence.jellyfin
            ),

            presenceChip(
                'Archivo',
                item.presence.filesystem
            )
        );

        body.append(presence);


        const episodeText =
            episodesText(item);

        if (episodeText !== null) {
            body.append(
                create(
                    'div',
                    'cmm-library-episodes',
                    episodeText
                )
            );
        }


        const footer =
            create(
                'div',
                'cmm-library-card-footer'
            );

        footer.append(
            create(
                'span',
                '',
                `ID #${item.id}`
            ),

            create(
                'span',
                '',
                item.pipeline_state === 'IDLE'
                    ? 'Sin actividad'
                    : item.pipeline_state
            )
        );

        body.append(footer);


        if (
            isDeletePreflightCandidate(
                item
            )
        ) {

            ensureDeleteUiStyle();

            const actionRow =
                create(
                    'div',
                    'cmm-library-action-row'
                );


            const deleteButton =
                create(
                    'button',
                    'cmm-library-delete-preflight',
                    'Eliminar'
                );


            deleteButton.type =
                'button';


            deleteButton.setAttribute(
                'aria-label',
                `Comprobar eliminación de ${item.title}`
            );


            deleteButton.addEventListener(
                'click',
                () => {
                    runDeletePreflight(
                        item,
                        deleteButton
                    );
                }
            );


            actionRow.appendChild(
                deleteButton
            );


            body.appendChild(
                actionRow
            );
        }


        card.append(
            poster,
            body
        );

        return card;
    };


    const filters = () => ({
        search:
            normalized(
                byId(
                    'cmm-library-search'
                )?.value
            ).trim(),

        type:
            byId(
                'cmm-library-type'
            )?.value ?? '',

        ownership:
            byId(
                'cmm-library-ownership'
            )?.value ?? '',

        state:
            byId(
                'cmm-library-state'
            )?.value ?? '',
    });


    const filteredItems = () => {
        const active =
            filters();

        return items
            .filter(
                (item) => {
                    if (
                        active.type
                        &&
                        item.type !== active.type
                    ) {
                        return false;
                    }

                    if (
                        active.ownership
                        &&
                        item.ownership
                            !== active.ownership
                    ) {
                        return false;
                    }

                    if (
                        active.state
                        &&
                        item.inventory_state
                            !== active.state
                    ) {
                        return false;
                    }

                    if (active.search) {
                        const haystack =
                            normalized(
                                [
                                    item.title,
                                    item.sort_title,
                                    item.ids?.imdb,
                                    item.ids?.tmdb,
                                    item.ids?.tvdb,
                                ]
                                    .filter(
                                        (value) =>
                                            value !== null
                                            &&
                                            value !== undefined
                                    )
                                    .join(' ')
                            );

                        if (
                            !haystack.includes(
                                active.search
                            )
                        ) {
                            return false;
                        }
                    }

                    return true;
                }
            )
            .sort(
                (a, b) =>
                    (
                        a.sort_title
                        || a.title
                    ).localeCompare(
                        b.sort_title
                        || b.title,
                        'es-AR',
                        {
                            sensitivity: 'base',
                            numeric: true,
                        }
                    )
            );
    };


    const renderItems = () => {
        const grid =
            byId('cmm-library-grid');

        const empty =
            byId('cmm-library-empty');

        if (!grid || !empty) {
            return;
        }

        const visible =
            filteredItems();

        grid.replaceChildren();

        for (const item of visible) {
            grid.appendChild(
                createCard(item)
            );
        }

        empty.hidden =
            visible.length !== 0;

        setText(
            'cmm-library-result-count',
            visible.length === items.length
                ? `${integer(
                    visible.length
                )} elementos`
                : `${integer(
                    visible.length
                )} de ${integer(
                    items.length
                )} elementos`
        );
    };


    const hideError = () => {
        const box =
            byId('cmm-library-error');

        if (!box) {
            return;
        }

        box.hidden = true;
        box.textContent = '';
    };


    const showError = (error) => {
        const box =
            byId('cmm-library-error');

        if (box) {
            box.hidden = false;

            box.textContent =
                error instanceof Error
                    ? error.message
                    : 'No se pudo consultar la biblioteca.';
        }

        setStatus(
            'error',
            '—',
            'Datos no disponibles'
        );
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

            items =
                data.items.slice();

            syncStateOptions(
                data.summary
            );

            renderSummary(data);
            renderItems();
            hideError();

            setStatus(
                'ok',
                integer(
                    data.summary.total
                ),
                'Biblioteca sincronizada'
            );

        } catch (error) {
            showError(error);
        }
    };


    const reset = () => {
        for (const id of [
            'cmm-library-search',
            'cmm-library-type',
            'cmm-library-ownership',
            'cmm-library-state',
        ]) {
            const field = byId(id);

            if (field) {
                field.value = '';
            }
        }

        renderItems();

        byId(
            'cmm-library-search'
        )?.focus();
    };


    byId(
        'cmm-library-search'
    )?.addEventListener(
        'input',
        renderItems
    );


    for (const id of [
        'cmm-library-type',
        'cmm-library-ownership',
        'cmm-library-state',
    ]) {
        byId(id)
            ?.addEventListener(
                'change',
                renderItems
            );
    }


    byId(
        'cmm-library-reset'
    )?.addEventListener(
        'click',
        reset
    );


    load();

    window.setInterval(
        load,
        REFRESH_MS
    );

})();
