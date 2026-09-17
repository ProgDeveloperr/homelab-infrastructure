(() => {
    'use strict';

    const API = 'api/solicitudes.php';

    const DELETE_PROPOSALS_API =
        'api/acciones-propuestas.php';

    const DELETE_PROPOSAL_ACTION_API =
        'api/acciones-propuesta.php';

    const REFRESH_MS = 30000;

    const STAGE_LABELS = {
        REQUESTED: 'Solicitud recibida',
        ARR_RECEIVED: 'Recibida por Arr',
        GRABBED: 'Encontrada',
        QUEUED: 'En cola',
        DOWNLOADING: 'Descargando',
        DOWNLOAD_COMPLETE: 'Descarga completa',
        IMPORTED: 'Importada',
        AVAILABLE: 'Disponible',
        FAILED: 'Requiere atención',
    };

    const STAGE_PROGRESS = {
        REQUESTED: 8,
        ARR_RECEIVED: 22,
        GRABBED: 38,
        QUEUED: 46,
        DOWNLOADING: 60,
        DOWNLOAD_COMPLETE: 74,
        IMPORTED: 88,
        AVAILABLE: 100,
        FAILED: 100,
    };

    const STAGE_CLASSES = {
        REQUESTED: 'waiting',
        ARR_RECEIVED: 'waiting',
        GRABBED: 'progress',
        QUEUED: 'progress',
        DOWNLOADING: 'progress',
        DOWNLOAD_COMPLETE: 'progress',
        IMPORTED: 'progress',
        AVAILABLE: 'available',
        FAILED: 'failed',
    };

    const byId = (id) =>
        document.getElementById(id);

    const create = (
        tag,
        className = '',
        text = null
    ) => {
        const element =
            document.createElement(tag);

        if (className) {
            element.className =
                className;
        }

        if (text !== null) {
            element.textContent =
                String(text);
        }

        return element;
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

    const integer = (value) =>
        new Intl.NumberFormat(
            'es-AR'
        ).format(value);

    const dateTime = (value) => {
        const parsed =
            new Date(value);

        if (
            !value
            ||
            Number.isNaN(
                parsed.getTime()
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
        ).format(parsed);
    };

    const bytes = (value) => {
        if (
            !Number.isFinite(value)
            ||
            value < 0
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

        let current =
            Number(value);

        let index = 0;

        while (
            current >= 1024
            &&
            index < units.length - 1
        ) {
            current /= 1024;
            index++;
        }

        return (
            current.toLocaleString(
                'es-AR',
                {
                    maximumFractionDigits:
                        current >= 100
                            ? 0
                            : 1,
                }
            )
            +
            ' '
            +
            units[index]
        );
    };

    const validate = (data) => {
        if (
            !data
            ||
            typeof data !== 'object'
            ||
            data.schema
                !==
                'cmm.requests.v1'
            ||
            !data.summary
            ||
            typeof data.summary
                !==
                'object'
            ||
            !Array.isArray(
                data.items
            )
        ) {
            throw new Error(
                'Contrato de Solicitudes inválido.'
            );
        }

        const keys = [
            'total',
            'available',
            'in_progress',
            'waiting',
            'attention',
        ];

        for (const key of keys) {
            if (
                !Number.isInteger(
                    data.summary[key]
                )
                ||
                data.summary[key] < 0
            ) {
                throw new Error(
                    'Resumen de Solicitudes inválido.'
                );
            }
        }

        if (
            data.summary.total
            !==
            data.items.length
        ) {
            throw new Error(
                'Total de Solicitudes inválido.'
            );
        }

        for (const item of data.items) {
            if (
                !item
                ||
                !Number.isInteger(item.id)
                ||
                item.id < 1
                ||
                !item.media
                ||
                !Number.isInteger(
                    item.media.id
                )
                ||
                ![
                    'movie',
                    'tv',
                ].includes(
                    item.media.type
                )
                ||
                typeof item.media.title
                    !==
                    'string'
                ||
                !Object.hasOwn(
                    STAGE_LABELS,
                    item.stage
                )
                ||
                !item.presence
                ||
                !Array.isArray(
                    item.downloads
                )
            ) {
                throw new Error(
                    'Solicitud inválida.'
                );
            }
        }

        return data;
    };

    let items = [];

    let deleteProposals = [];

    const deleteProposalCancelBusy =
        new Set();

    const deleteProposalAuthorizeBusy =
        new Set();

    let loading = false;

    // CMM_K7D9B_R1D_R2_READ_ONLY_PROPOSALS

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
        tone,
        value,
        text
    ) => {
        const node =
            byId(
                'cmm-requests-status'
            );

        if (!node) {
            return;
        }

        node.className =
            (
                'cmm-requests-status '
                +
                `cmm-requests-status--${tone}`
            );

        setText(
            'cmm-requests-status-value',
            value
        );

        setText(
            'cmm-requests-status-text',
            text
        );
    };

    const renderSummary = (data) => {
        setText(
            'cmm-requests-total',
            integer(
                data.summary.total
            )
        );

        setText(
            'cmm-requests-available',
            integer(
                data.summary.available
            )
        );

        setText(
            'cmm-requests-in-progress',
            integer(
                data.summary.in_progress
            )
        );

        setText(
            'cmm-requests-waiting',
            integer(
                data.summary.waiting
            )
        );

        setText(
            'cmm-requests-attention',
            integer(
                data.summary.attention
            )
        );
    };

    const chip = (
        text,
        className = ''
    ) =>
        create(
            'span',
            (
                'cmm-requests-chip '
                +
                className
            ).trim(),
            text
        );

    const createPoster = (item) => {
        const box =
            create(
                'div',
                'cmm-requests-poster'
            );

        const placeholder =
            create(
                'span',
                'cmm-requests-poster-placeholder',
                item.media.title
                    .trim()
                    .charAt(0)
                    .toUpperCase()
                    ||
                    '?'
            );

        const image =
            document.createElement(
                'img'
            );

        image.alt = '';
        image.loading = 'lazy';
        image.decoding = 'async';

        image.src =
            (
                'api/caratula.php?id='
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
                box.classList.add(
                    'cmm-requests-poster--loaded'
                );
            }
        );

        image.addEventListener(
            'error',
            () => {
                image.remove();
            }
        );

        box.append(
            placeholder,
            image
        );

        return box;
    };

    const stageTrack = (item) => {
        const box =
            create(
                'div',
                (
                    'cmm-requests-pipeline '
                    +
                    (
                        item.stage === 'FAILED'
                            ? 'cmm-requests-pipeline--failed'
                            : ''
                    )
                ).trim()
            );

        const heading =
            create(
                'div',
                'cmm-requests-pipeline-heading'
            );

        heading.append(
            create(
                'span',
                '',
                'Pipeline'
            ),
            create(
                'strong',
                '',
                STAGE_LABELS[
                    item.stage
                ]
            )
        );

        const track =
            create(
                'div',
                'cmm-requests-pipeline-track'
            );

        const fill =
            create(
                'span',
                'cmm-requests-pipeline-fill'
            );

        fill.style.width =
            `${
                STAGE_PROGRESS[
                    item.stage
                ]
            }%`;

        track.append(fill);

        box.append(
            heading,
            track
        );

        return box;
    };

    const transferBlock = (item) => {
        if (
            item.downloads.length
            ===
            0
        ) {
            return null;
        }

        const block =
            create(
                'div',
                'cmm-requests-transfer'
            );

        const size =
            item.downloads.reduce(
                (sum, value) =>
                    sum
                    +
                    (
                        Number.isFinite(
                            value.size_bytes
                        )
                            ? value.size_bytes
                            : 0
                    ),
                0
            );

        const downloaded =
            item.downloads.reduce(
                (sum, value) =>
                    sum
                    +
                    (
                        Number.isFinite(
                            value.downloaded_bytes
                        )
                            ? value.downloaded_bytes
                            : 0
                    ),
                0
            );

        const speed =
            item.downloads.reduce(
                (sum, value) =>
                    sum
                    +
                    (
                        Number.isFinite(
                            value.dlspeed_bps
                        )
                            ? value.dlspeed_bps
                            : 0
                    ),
                0
            );

        const progress =
            size > 0
                ? Math.min(
                    1,
                    downloaded / size
                )
                : Math.max(
                    ...item.downloads.map(
                        (value) =>
                            Number.isFinite(
                                value.progress
                            )
                                ? value.progress
                                : 0
                    )
                );

        const heading =
            create(
                'div',
                'cmm-requests-transfer-heading'
            );

        heading.append(
            create(
                'span',
                '',
                item.downloads.length === 1
                    ? 'Transferencia asociada'
                    : `${item.downloads.length} transferencias asociadas`
            ),
            create(
                'strong',
                '',
                `${
                    Math.round(
                        progress * 100
                    )
                }%`
            )
        );

        const detail =
            create(
                'div',
                'cmm-requests-transfer-details'
            );

        detail.append(
            create(
                'span',
                '',
                size > 0
                    ? `${bytes(
                        downloaded
                    )} / ${bytes(
                        size
                    )}`
                    : 'Tamaño no disponible'
            ),
            create(
                'span',
                '',
                speed > 0
                    ? `${bytes(speed)}/s`
                    : 'Sin tráfico'
            )
        );

        block.append(
            heading,
            detail
        );

        return block;
    };

    const createCard = (item) => {
        const tone =
            STAGE_CLASSES[
                item.stage
            ];

        const card =
            create(
                'article',
                (
                    'cmm-requests-item '
                    +
                    `cmm-requests-item--${tone}`
                )
            );

        const body =
            create(
                'div',
                'cmm-requests-item-body'
            );

        const badges =
            create(
                'div',
                'cmm-requests-badges'
            );

        badges.append(
            chip(
                item.media.type === 'movie'
                    ? 'Película'
                    : 'Serie',
                'cmm-requests-chip--type'
            ),
            chip(
                STAGE_LABELS[
                    item.stage
                ],
                `cmm-requests-chip--${tone}`
            )
        );

        const metadata =
            create(
                'div',
                'cmm-requests-meta'
            );

        metadata.append(
            create(
                'span',
                '',
                (
                    'Solicitada: '
                    +
                    dateTime(
                        item.requested_at_utc
                    )
                )
            )
        );

        if (
            item.media.type === 'tv'
            &&
            Array.isArray(
                item.seasons
            )
            &&
            item.seasons.length > 0
        ) {
            metadata.append(
                create(
                    'span',
                    '',
                    (
                        'Temporadas: '
                        +
                        item.seasons
                            .map(
                                (value) =>
                                    value.season_number
                            )
                            .join(', ')
                    )
                )
            );
        }

        const presence =
            create(
                'div',
                'cmm-requests-presence'
            );

        for (
            const [label, key]
            of [
                ['Arr', 'arr'],
                ['Archivo', 'filesystem'],
                ['Jellyfin', 'jellyfin'],
            ]
        ) {
            const present =
                Boolean(
                    item.presence[key]
                );

            presence.append(
                chip(
                    `${label}: ${
                        present
                            ? 'Sí'
                            : 'No'
                    }`,
                    present
                        ? 'cmm-requests-chip--yes'
                        : 'cmm-requests-chip--no'
                )
            );
        }

        body.append(
            badges,
            create(
                'h3',
                'cmm-requests-title',
                item.media.title
            ),
            metadata,
            stageTrack(item),
            presence
        );

        const transfer =
            transferBlock(item);

        if (transfer) {
            body.append(transfer);
        }

        const footer =
            create(
                'div',
                'cmm-requests-footer'
            );

        footer.append(
            create(
                'span',
                '',
                `Solicitud #${item.id}`
            ),
            create(
                'span',
                '',
                `Media #${item.media.id}`
            )
        );

        body.append(footer);

        card.append(
            createPoster(item),
            body
        );

        return card;
    };


    const validateDeleteProposals =
        (data) => {

            if (
                !data
                ||
                typeof data !== 'object'
                ||
                data.schema
                    !==
                    'cmm.delete-proposals.v1'
                ||
                data.ok !== true
                ||
                data.mode !== 'READ_ONLY'
                ||
                !Number.isInteger(
                    data.items_count
                )
                ||
                !Array.isArray(
                    data.items
                )
                ||
                data.items_count
                    !== data.items.length
                ||
                data.database_mutation
                    !== false
                ||
                data.action_created
                    !== false
                ||
                data.authorization_issued
                    !== false
                ||
                data.executor_invoked
                    !== false
                ||
                data.filesystem_mutation
                    !== false
                ||
                data.irreversible_boundary_crossed
                    !== false
                ||
                data.permanent_delete
                    !== false
            ) {
                throw new Error(
                    'Contrato de propuestas inválido.'
                );
            }

            for (
                const item
                of data.items
            ) {
                if (
                    !item
                    ||
                    !Number.isInteger(
                        item.action_id
                    )
                    ||
                    item.action_id < 1
                    ||
                    item.action_type
                        !== 'DELETE'
                    ||
                    !Number.isInteger(
                        item.media_item_id
                    )
                    ||
                    item.media_item_id < 1
                    ||
                    !item.media
                    ||
                    item.media.id
                        !== item.media_item_id
                    ||
                    typeof item.media.title
                        !== 'string'
                    ||
                    ![
                        'movie',
                        'tv',
                    ].includes(
                        item.media.type
                    )
                    ||
                    typeof item.status
                        !== 'string'
                    ||
                    typeof item.effective_state
                        !== 'string'
                    ||
                    typeof item.expired_now
                        !== 'boolean'
                    ||
                    typeof item.can_cancel
                        !== 'boolean'
                    ||
                    typeof item.request_id
                        !== 'string'
                    ||
                    !/^[A-Za-z0-9_-]{16,80}$/.test(
                        item.request_id
                    )
                    ||
                    item.proposal_only
                        !== true
                    ||
                    item.execution_authorized
                        !== false
                    ||
                    item.human_authorization_issued
                        !== false
                    ||
                    item.permanent_delete_authorized
                        !== false
                    ||
                    item.irreversible_boundary_authorized
                        !== false
                    ||
                    item.filesystem_mutation
                        !== false
                ) {
                    throw new Error(
                        'Propuesta DELETE inválida.'
                    );
                }
            }

            return data;
        };


    const proposalStateLabel =
        (item) => {

            if (
                item.effective_state
                    === 'EXPIRED'
            ) {
                return 'Vencida';
            }

            if (
                item.status
                    === 'PENDING_CONFIRMATION'
            ) {
                return 'Pendiente de confirmación';
            }

            if (
                item.status
                    === 'CANCELLED'
            ) {
                return 'Cancelada';
            }

            if (
                item.status
                    === 'SUCCESS'
            ) {
                return 'Completada';
            }

            if (
                item.status
                    === 'FAILED'
            ) {
                return 'Fallida';
            }

            return item.status;
        };


    const proposalTone =
        (item) => {

            if (
                item.effective_state
                    === 'EXPIRED'
                ||
                item.status
                    === 'FAILED'
            ) {
                return 'failed';
            }

            if (
                item.status
                    === 'PENDING_CONFIRMATION'
            ) {
                return 'waiting';
            }

            if (
                item.status
                    === 'SUCCESS'
            ) {
                return 'available';
            }

            return 'waiting';
        };




    const getDeleteProposalCsrf =
        async () => {

            const response =
                await fetch(
                    DELETE_PROPOSAL_ACTION_API,
                    {
                        method:
                            'POST',

                        credentials:
                            'same-origin',

                        cache:
                            'no-store',

                        headers: {
                            'Content-Type':
                                'application/json',

                            Accept:
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
                data?.ok !== true
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
                    'No se pudo obtener un token seguro.'
                );
            }

            return data.csrf_token;
        };


    const cancelDeleteProposal =
        async (
            item,
            button
        ) => {

            const actionId =
                item.action_id;

            if (
                deleteProposalCancelBusy.has(
                    actionId
                )
            ) {
                return;
            }

            const accepted =
                window.confirm(
                    (
                        `Cancelar la propuesta #${actionId} `
                        +
                        `de ${item.media.title}?`
                        +
                        '\n\n'
                        +
                        'Esto NO elimina el archivo.'
                    )
                );

            if (!accepted) {
                return;
            }

            deleteProposalCancelBusy.add(
                actionId
            );

            button.disabled = true;
            button.textContent =
                'Cancelando…';

            try {
                const csrf =
                    await getDeleteProposalCsrf();

                const response =
                    await fetch(
                        DELETE_PROPOSAL_ACTION_API,
                        {
                            method:
                                'POST',

                            credentials:
                                'same-origin',

                            cache:
                                'no-store',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                Accept:
                                    'application/json',
                            },

                            body:
                                JSON.stringify({
                                    operation:
                                        'CANCEL_DELETE_PROPOSAL',

                                    action_id:
                                        actionId,

                                    request_id:
                                        item.request_id,

                                    csrf_token:
                                        csrf,
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
                    data?.ok !== true
                    ||
                    data?.operation
                        !==
                        'CANCEL_DELETE_PROPOSAL'
                    ||
                    data?.action_id
                        !== actionId
                    ||
                    data?.status
                        !== 'CANCELLED'
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
                        (
                            data?.error
                            ??
                            'No se pudo cancelar la propuesta.'
                        )
                    );
                }

                await loadDeleteProposals();

            } catch (error) {
                showDeleteProposalError(
                    error
                );

                button.disabled = false;
                button.textContent =
                    'Cancelar propuesta';

            } finally {
                deleteProposalCancelBusy.delete(
                    actionId
                );
            }
        };



    const authorizeDeleteProposal =
        async (
            item,
            button
        ) => {

            const actionId =
                item.action_id;

            if (
                deleteProposalAuthorizeBusy.has(
                    actionId
                )
            ) {
                return;
            }

            if (
                item.status
                    !==
                    'PENDING_CONFIRMATION'
                ||
                item.effective_state
                    !==
                    'PENDING_CONFIRMATION'
            ) {
                showDeleteProposalError(
                    new Error(
                        'La propuesta ya no está vigente.'
                    )
                );

                return;
            }

            const phrase =
                `AUTORIZAR ELIMINACION #${actionId}`;

            const typed =
                window.prompt(
                    (
                        'Esta acción emite una autorización '
                        +
                        'firmada para la eliminación.\n\n'
                        +
                        'Todavía NO ejecutará ni eliminará '
                        +
                        'el archivo.\n\n'
                        +
                        'Escribí exactamente:\n'
                        +
                        phrase
                    )
                );

            if (typed !== phrase) {
                return;
            }

            deleteProposalAuthorizeBusy.add(
                actionId
            );

            button.disabled = true;

            button.textContent =
                'Autorizando…';

            try {
                const csrf =
                    await getDeleteProposalCsrf();

                const response =
                    await fetch(
                        DELETE_PROPOSAL_ACTION_API,
                        {
                            method:
                                'POST',

                            credentials:
                                'same-origin',

                            cache:
                                'no-store',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                Accept:
                                    'application/json',
                            },

                            body:
                                JSON.stringify({
                                    operation:
                                        'ISSUE_DELETE_AUTHORIZATION',

                                    action_id:
                                        actionId,

                                    request_id:
                                        item.request_id,

                                    csrf_token:
                                        csrf,
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
                    data?.ok !== true
                    ||
                    data?.operation
                        !==
                        'ISSUE_DELETE_AUTHORIZATION'
                    ||
                    data?.action_id
                        !== actionId
                    ||
                    data?.request_id
                        !== item.request_id
                    ||
                    data?.authorization_issued
                        !== true
                    ||
                    data?.execution_authorized
                        !== true
                    ||
                    data?.permanent_delete_authorized
                        !== true
                    ||
                    data?.irreversible_boundary_authorized
                        !== true
                    ||
                    data?.database_mutation
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
                    ||
                    typeof data?.ticket_sha256
                        !== 'string'
                    ||
                    !/^[a-f0-9]{64}$/.test(
                        data.ticket_sha256
                    )
                    ||
                    typeof data?.signature_sha256
                        !== 'string'
                    ||
                    !/^[a-f0-9]{64}$/.test(
                        data.signature_sha256
                    )
                ) {
                    throw new Error(
                        (
                            data?.error
                            ??
                            'No se pudo emitir la autorización.'
                        )
                    );
                }

                button.textContent =
                    'Autorización emitida';

                button.disabled = true;

                await loadDeleteProposals();

                window.alert(
                    (
                        `Autorización emitida para la acción #${actionId}.`
                        +
                        '\n\n'
                        +
                        'El archivo NO fue eliminado.'
                        +
                        '\n'
                        +
                        'El executor continúa desconectado.'
                    )
                );

            } catch (error) {
                showDeleteProposalError(
                    error
                );

                button.disabled = false;

                button.textContent =
                    'Autorizar eliminación';

            } finally {
                deleteProposalAuthorizeBusy.delete(
                    actionId
                );
            }
        };


    // CMM_K7D9B_R1E_R4_R1_AUTH_LIFECYCLE
    // CMM_K7D9B_R1E_R3B_HUMAN_AUTH_UI
    // CMM_K7D9B_R1D_R4_CANCEL_UI
    // CMM_K7D9B_R1D_R3_R1_DELETE_LAYOUT
    const createDeleteProposalCard =
        (item) => {

            const tone =
                proposalTone(
                    item
                );

            const authorization =
                item.authorization
                ?? {};

            const authorizationState =
                authorization.state
                ?? 'NONE';

            const card =
                create(
                    'article',
                    (
                        'cmm-requests-item '
                        +
                        'cmm-delete-proposal-item '
                        +
                        `cmm-requests-item--${tone}`
                    )
                );

            const body =
                create(
                    'div',
                    'cmm-requests-item-body'
                );

            const badges =
                create(
                    'div',
                    'cmm-requests-badges'
                );

            badges.append(
                chip(
                    'Eliminación',
                    'cmm-requests-chip--type'
                ),

                chip(
                    (
                        authorizationState
                            === 'ACTIVE'
                            ? 'Autorizada'
                            : (
                                authorizationState
                                    === 'EXPIRED'
                                    ? 'Autorización vencida'
                                    : proposalStateLabel(
                                        item
                                    )
                            )
                    ),
                    `cmm-requests-chip--${tone}`
                ),

                chip(
                    'Sólo lectura',
                    'cmm-requests-chip--type'
                )
            );

            const metadata =
                create(
                    'div',
                    'cmm-requests-meta'
                );

            metadata.append(
                create(
                    'span',
                    '',
                    (
                        'Creada: '
                        +
                        dateTime(
                            item.proposal_created_at_utc
                        )
                    )
                ),

                create(
                    'span',
                    '',
                    (
                        'Vence: '
                        +
                        dateTime(
                            item.proposal_expires_at_utc
                        )
                    )
                )
            );

            const safety =
                create(
                    'div',
                    'cmm-requests-presence'
                );

            safety.append(
                chip(
                    (
                        authorizationState
                            === 'ACTIVE'
                            ? 'Autorización: Sí'
                            : (
                                authorizationState
                                    === 'EXPIRED'
                                    ? 'Autorización: Vencida'
                                    : 'Autorización: No'
                            )
                    ),
                    (
                        authorizationState
                            === 'ACTIVE'
                            ? 'cmm-requests-chip--yes'
                            : (
                                authorizationState
                                    === 'EXPIRED'
                                    ? 'cmm-requests-chip--type'
                                    : 'cmm-requests-chip--no'
                            )
                    )
                ),

                chip(
                    'Executor: No',
                    'cmm-requests-chip--no'
                ),

                chip(
                    'Archivo modificado: No',
                    'cmm-requests-chip--no'
                )
            );

            const stateText =
                authorizationState
                    === 'ACTIVE'
                    ? (
                        'Existe una autorización firmada vigente'
                        +
                        (
                            authorization.expires_at_utc
                                ? (
                                    ' hasta '
                                    +
                                    dateTime(
                                        authorization.expires_at_utc
                                    )
                                )
                                : ''
                        )
                        +
                        '. El executor continúa desconectado; '
                        +
                        'el archivo no fue modificado.'
                    )
                    : (
                        authorizationState
                            === 'EXPIRED'
                            ? (
                                'La autorización firmada venció '
                                +
                                'y ya no habilita el borrado. '
                                +
                                'El archivo no fue modificado.'
                            )
                            : (
                                item.effective_state
                                    === 'EXPIRED'
                                    ? (
                                        'La propuesta superó su TTL. '
                                        +
                                        'El estado persistido continúa '
                                        +
                                        `${item.status}; no existe `
                                        +
                                        'autorización vigente.'
                                    )
                                    : (
                                        item.status
                                            === 'PENDING_CONFIRMATION'
                                            ? (
                                                'La propuesta está registrada '
                                                +
                                                'pero todavía no está '
                                                +
                                                'autorizada para eliminar.'
                                            )
                                            : (
                                                'Estado registrado: '
                                                +
                                                proposalStateLabel(
                                                    item
                                                )
                                                +
                                                '.'
                                            )
                                    )
                            )
                    );

            const state =
                create(
                    'div',
                    'cmm-requests-transfer'
                );

            state.append(
                create(
                    'strong',
                    'cmm-requests-transfer-heading',
                    'Estado de la propuesta'
                ),

                create(
                    'div',
                    'cmm-requests-transfer-details',
                    stateText
                )
            );

            const footer =
                create(
                    'div',
                    'cmm-requests-footer'
                );

            footer.append(
                create(
                    'span',
                    '',
                    `Acción #${item.action_id}`
                ),

                create(
                    'span',
                    '',
                    `Media #${item.media_item_id}`
                )
            );

            if (
                item.can_cancel
                &&
                item.status
                    === 'PENDING_CONFIRMATION'
                &&
                authorizationState
                    !== 'ACTIVE'
            ) {
                const cancelButton =
                    create(
                        'button',
                        'cmm-requests-reset',
                        'Cancelar propuesta'
                    );

                cancelButton.type =
                    'button';

                cancelButton.addEventListener(
                    'click',
                    () =>
                        cancelDeleteProposal(
                            item,
                            cancelButton
                        )
                );

                footer.append(
                    cancelButton
                );
            }


            if (
                item.status
                    === 'PENDING_CONFIRMATION'
                &&
                item.effective_state
                    === 'PENDING_CONFIRMATION'
                &&
                authorizationState
                    === 'NONE'
                &&
                authorization.can_authorize
                    === true
            ) {
                const authorizeButton =
                    create(
                        'button',
                        'cmm-requests-reset',
                        'Autorizar eliminación'
                    );

                authorizeButton.type =
                    'button';

                authorizeButton.addEventListener(
                    'click',
                    () =>
                        authorizeDeleteProposal(
                            item,
                            authorizeButton
                        )
                );

                footer.append(
                    authorizeButton
                );
            }

            body.append(
                badges,

                create(
                    'h3',
                    'cmm-requests-title',
                    item.media.title
                ),

                metadata,
                safety,
                state,
                footer
            );

            card.appendChild(
                body
            );

            return card;
        };


    // CMM_K7D9B_R1E_R4B_COMPACT_HISTORY
    const createDeleteProposalHistory =
        (items) => {

            const details =
                create(
                    'details',
                    'cmm-delete-history'
                );

            const summary =
                create(
                    'summary',
                    'cmm-delete-history-summary'
                );

            summary.append(
                create(
                    'span',
                    'cmm-delete-history-title',
                    'Historial de propuestas'
                ),

                create(
                    'span',
                    'cmm-delete-history-count',
                    (
                        items.length === 1
                            ? '1 anterior'
                            : `${integer(items.length)} anteriores`
                    )
                )
            );

            const rows =
                create(
                    'div',
                    'cmm-delete-history-list'
                );

            for (
                const item
                of [...items].reverse()
            ) {
                const row =
                    create(
                        'div',
                        'cmm-delete-history-row'
                    );

                const main =
                    create(
                        'div',
                        'cmm-delete-history-main'
                    );

                main.append(
                    create(
                        'strong',
                        'cmm-delete-history-name',
                        item.media.title
                    ),

                    create(
                        'span',
                        'cmm-delete-history-meta',
                        (
                            `Acción #${item.action_id}`
                            +
                            ' · '
                            +
                            proposalStateLabel(
                                item
                            )
                            +
                            ' · '
                            +
                            dateTime(
                                item.proposal_created_at_utc
                            )
                        )
                    )
                );

                const side =
                    create(
                        'span',
                        'cmm-delete-history-media',
                        `Media #${item.media_item_id}`
                    );

                row.append(
                    main,
                    side
                );

                rows.appendChild(
                    row
                );
            }

            details.append(
                summary,
                rows
            );

            return details;
        };


    const renderDeleteProposals =
        () => {
            const list =
                byId(
                    'cmm-delete-proposals-list'
                );

            const empty =
                byId(
                    'cmm-delete-proposals-empty'
                );

            const error =
                byId(
                    'cmm-delete-proposals-error'
                );

            if (
                !list
                ||
                !empty
                ||
                !error
            ) {
                return;
            }

            const terminalStates =
                new Set([
                    'CANCELLED',
                    'SUCCESS',
                    'FAILED',
                ]);

            const current =
                deleteProposals.filter(
                    (item) =>
                        !terminalStates.has(
                            item.status
                        )
                );

            const history =
                deleteProposals.filter(
                    (item) =>
                        terminalStates.has(
                            item.status
                        )
                );

            list.replaceChildren();

            for (
                const item
                of current
            ) {
                list.appendChild(
                    createDeleteProposalCard(
                        item
                    )
                );
            }

            if (
                history.length > 0
            ) {
                list.appendChild(
                    createDeleteProposalHistory(
                        history
                    )
                );
            }

            empty.hidden =
                deleteProposals.length
                    !== 0;

            error.hidden = true;
            error.textContent = '';

            let countText = '';

            if (
                current.length === 1
            ) {
                countText =
                    '1 activa';
            } else {
                countText =
                    `${integer(current.length)} activas`;
            }

            if (
                history.length > 0
            ) {
                countText +=
                    (
                        history.length === 1
                            ? ' · 1 anterior'
                            : (
                                ' · '
                                +
                                `${integer(history.length)} anteriores`
                            )
                    );
            }

            setText(
                'cmm-delete-proposals-count',
                countText
            );
        };


    const showDeleteProposalError =
        (error) => {

            const box =
                byId(
                    'cmm-delete-proposals-error'
                );

            if (box) {
                box.hidden = false;

                box.textContent =
                    error instanceof Error
                        ? error.message
                        : (
                            'No se pudieron consultar '
                            +
                            'las propuestas de eliminación.'
                        );
            }

            setText(
                'cmm-delete-proposals-count',
                '—'
            );
        };


    const loadDeleteProposals =
        async () => {

            try {
                const response =
                    await fetch(
                        DELETE_PROPOSALS_API,
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

                if (
                    !response.ok
                ) {
                    throw new Error(
                        'La API de propuestas respondió '
                        +
                        `HTTP ${response.status}.`
                    );
                }

                const data =
                    validateDeleteProposals(
                        await response.json()
                    );

                deleteProposals =
                    data.items.slice();

                renderDeleteProposals();

            } catch (error) {
                showDeleteProposalError(
                    error
                );
            }
        };


    const activeFilters = () => ({
        search:
            normalized(
                byId(
                    'cmm-requests-search'
                )?.value
            ).trim(),

        stage:
            byId(
                'cmm-requests-stage'
            )?.value
            ?? '',

        type:
            byId(
                'cmm-requests-type'
            )?.value
            ?? '',
    });

    const renderItems = () => {
        const list =
            byId(
                'cmm-requests-list'
            );

        const empty =
            byId(
                'cmm-requests-empty'
            );

        if (
            !list
            ||
            !empty
        ) {
            return;
        }

        const filters =
            activeFilters();

        const visible =
            items.filter(
                (item) => {
                    if (
                        filters.stage
                        &&
                        item.stage
                            !==
                            filters.stage
                    ) {
                        return false;
                    }

                    if (
                        filters.type
                        &&
                        item.media.type
                            !==
                            filters.type
                    ) {
                        return false;
                    }

                    if (
                        filters.search
                        &&
                        !normalized(
                            item.media.title
                        ).includes(
                            filters.search
                        )
                    ) {
                        return false;
                    }

                    return true;
                }
            );

        list.replaceChildren();

        for (const item of visible) {
            list.appendChild(
                createCard(item)
            );
        }

        empty.hidden =
            visible.length !== 0;

        setText(
            'cmm-requests-result-count',
            visible.length === items.length
                ? `${integer(
                    visible.length
                )} solicitudes`
                : `${integer(
                    visible.length
                )} de ${integer(
                    items.length
                )}`
        );
    };

    const showError = (error) => {
        const box =
            byId(
                'cmm-requests-error'
            );

        if (box) {
            box.hidden = false;
            box.textContent =
                error instanceof Error
                    ? error.message
                    : 'No se pudieron consultar las solicitudes.';
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
                'cmm-requests-error'
            );

        if (box) {
            box.hidden = true;
            box.textContent = '';
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

            renderSummary(data);
            renderItems();

            await loadDeleteProposals();

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
            showError(error);

        } finally {
            loading = false;
        }
    };

    byId(
        'cmm-requests-search'
    )?.addEventListener(
        'input',
        renderItems
    );

    byId(
        'cmm-requests-stage'
    )?.addEventListener(
        'change',
        renderItems
    );

    byId(
        'cmm-requests-type'
    )?.addEventListener(
        'change',
        renderItems
    );

    byId(
        'cmm-requests-reset'
    )?.addEventListener(
        'click',
        () => {
            for (
                const id
                of [
                    'cmm-requests-search',
                    'cmm-requests-stage',
                    'cmm-requests-type',
                ]
            ) {
                const node =
                    byId(id);

                if (node) {
                    node.value = '';
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
