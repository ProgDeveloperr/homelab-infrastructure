       <section     id="panel-eventos-servidor"
            class="panel panel-dispositivos panel-eventos"
        >
            <div class="panel__encabezado">
                <div>
                    <p class="etiqueta">
                        Historial y automatización
                    </p>

                    <h2>Actividad del servidor</h2>
                </div>

                <span
                    class="estado-eventos estado-eventos--<?= htmlspecialchars(
                        obtenerClaseEstadoEvento(
                            $estadoEventos['nivel']
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            str_replace(
                                '-',
                                ' ',
                                $estadoEventos['nivel']
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <?php if (!$estadoEventos['disponible']): ?>
                <div class="mensaje-flotante mensaje-flotante--error">
                    No se pudo leer la actividad del servidor.
                </div>
            <?php elseif (
                $estadoEventos['nivel'] !== 'normal'
            ): ?>
                <div class="mensaje-eventos mensaje-eventos--<?= htmlspecialchars(
                    obtenerClaseEstadoEvento(
                        $estadoEventos['nivel']
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">
                    <?= htmlspecialchars(
                        $estadoEventos['mensaje'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <div class="resumen-eventos">
                <div>
                    <span>Timers</span>

                    <strong>
                        <?= (int) (
                            $estadoEventos['resumen'][
                                'timers_activos'
                            ] ?? 0
                        ) ?>
                        /
                        <?= (int) (
                            $estadoEventos['resumen'][
                                'timers_total'
                            ] ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Timers fallidos</span>

                    <strong>
                        <?= (int) (
                            $estadoEventos['resumen'][
                                'timers_fallidos'
                            ] ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Servicios fallidos</span>

                    <strong>
                        <?= (int) (
                            $estadoEventos['resumen'][
                                'servicios_fallidos'
                            ] ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Arranque actual</span>

                    <strong>
                        <?= htmlspecialchars(
                            formatearUptimeServicio(
                                isset(
                                    $estadoEventos['arranque'][
                                        'actual_unix'
                                    ]
                                )
                                    ? max(
                                        0,
                                        time() - (int) (
                                            $estadoEventos[
                                                'arranque'
                                            ]['actual_unix']
                                        )
                                    )
                                    : null
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>
            </div>

            <div class="rejilla-eventos-principales">
                <article class="tarjeta-evento-principal">
                    <div class="tarjeta-evento-principal__encabezado">
                        <div>
                            <span>Sistema</span>

                            <h3>Arranque del servidor</h3>
                        </div>

                        <span
                            class="insignia-evento insignia-evento--<?= !empty(
                                $estadoEventos['arranque'][
                                    'anterior_crash'
                                ]
                            )
                                ? 'advertencia'
                                : 'normal' ?>"
                        >
                            <?= !empty(
                                $estadoEventos['arranque'][
                                    'anterior_crash'
                                ]
                            )
                                ? 'Revisar'
                                : 'Normal' ?>
                        </span>
                    </div>

                    <dl class="detalle-evento">
                        <div>
                            <dt>Inicio actual</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    formatearMomentoTimer(
                                        $estadoEventos['arranque'][
                                            'actual_unix'
                                        ] ?? null
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Estado anterior</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    (string) (
                                        $estadoEventos['arranque'][
                                            'mensaje'
                                        ] ?? 'Sin datos'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>
                    </dl>
                </article>

                <article class="tarjeta-evento-principal">
                    <div class="tarjeta-evento-principal__encabezado">
                        <div>
                            <span>Últimos registros</span>

                            <h3>Estado consolidado</h3>
                        </div>

                        <span
                            class="insignia-evento insignia-evento--<?= htmlspecialchars(
                                obtenerClaseEstadoEvento(
                                    $estadoEventos['nivel']
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <?= htmlspecialchars(
                                ucfirst(
                                    $estadoEventos['nivel']
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>

                    <div class="lista-eventos-recientes">
                        <?php
                        $nombresEventos = [
                            'servidor' => 'Servidor',
                            'smart' => 'SMART',
                            'servicios' => 'Docker',
                            'backups' => 'Backups',
                        ];
                        ?>

                        <?php foreach (
                            $nombresEventos as
                            $claveEvento => $nombreEvento
                        ): ?>
                            <div>
                                <span>
                                    <?= htmlspecialchars(
                                        $nombreEvento,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                                <p>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $estadoEventos[
                                                'eventos_recientes'
                                            ][$claveEvento]
                                            ?? 'Sin registros'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>

            <div class="encabezado-lista-timers">
                <div>
                    <span>Tareas automáticas</span>

                    <strong>
                        Última y próxima ejecución
                    </strong>
                </div>

                <span>
                    <?= count(
                        $estadoEventos['timers']
                    ) ?>
                    tareas registradas
                </span>
            </div>

            <div class="lista-timers">
                <?php foreach (
                    $estadoEventos['timers'] as $timer
                ): ?>
                    <?php
                    $nivelTimer = obtenerClaseEstadoEvento(
                        (string) (
                            $timer['nivel'] ?? 'sin-datos'
                        )
                    );
                    ?>

                    <article
                        class="tarjeta-timer tarjeta-timer--<?= htmlspecialchars(
                            $nivelTimer,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <div class="tarjeta-timer__identidad">
                            <span
                                class="categoria-timer categoria-timer--<?= htmlspecialchars(
                                    (string) (
                                        $timer['categoria']
                                        ?? 'sistema'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    formatearCategoriaTimer(
                                        (string) (
                                            $timer['categoria']
                                            ?? 'sistema'
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <div>
                                <h3>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $timer['nombre']
                                            ?? 'Timer'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $timer['descripcion']
                                            ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>
                            </div>
                        </div>

                        <div class="tarjeta-timer__estado">
                            <span
                                class="insignia-evento insignia-evento--<?= htmlspecialchars(
                                    $nivelTimer,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    formatearResultadoTimer(
                                        (string) (
                                            $timer[
                                                'resultado_servicio'
                                            ] ?? ''
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </div>

                        <dl class="detalle-timer">
                            <div>
                                <dt>Última ejecución</dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        formatearMomentoTimer(
                                            $timer[
                                                'ultima_ejecucion_unix'
                                            ] ?? null
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>

                            <div>
                                <dt>Próxima</dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        formatearCuentaRegresivaTimer(
                                            $timer[
                                                'proxima_ejecucion_unix'
                                            ] ?? null
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="pie-eventos">
                <span>
                    <?= htmlspecialchars(
                        $estadoEventos['mensaje'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <strong>
                    Actualizado
                    <?= htmlspecialchars(
                        formatearAntiguedadSmart(
                            $estadoEventos[
                                'antiguedad_segundos'
                            ]
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>
        </section>

        <section
