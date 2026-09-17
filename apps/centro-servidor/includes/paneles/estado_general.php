    id="panel-general-servidor"
    class="panel panel-dispositivos panel-servidor"
>
    <div class="panel__encabezado">
        <div>
            <p class="etiqueta">Recursos y servicios</p>
            <h2>Estado general del servidor</h2>
        </div>

        <span
            class="estado-general estado-general--<?= htmlspecialchars(
                $estadoGeneralServidor['nivel'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
            <?= htmlspecialchars(
                ucfirst(
                    str_replace(
                        '-',
                        ' ',
                        $estadoGeneralServidor['nivel']
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>
    </div>

    <?php if (!$estadoGeneralServidor['disponible']): ?>
        <div class="mensaje-flotante mensaje-flotante--error">
            No se pudo leer el estado general del servidor.
        </div>
    <?php elseif (
        $estadoGeneralServidor['nivel'] !== 'normal'
    ): ?>
        <div class="mensaje-flotante mensaje-flotante--error">
            <?= htmlspecialchars(
                $estadoGeneralServidor['mensaje'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <?php
    $cpu = $estadoGeneralServidor['cpu'];
    $memoria = $estadoGeneralServidor['memoria'];
    $swap = $estadoGeneralServidor['swap'];
    $discoServidor = $estadoGeneralServidor['disco'];
    $sistema = $estadoGeneralServidor['sistema'];
    $docker = $estadoGeneralServidor['docker'];

    $cpuUso = obtenerNumeroEstado(
        $cpu,
        'uso_porcentaje',
        0
    );

    $memoriaUso = obtenerNumeroEstado(
        $memoria,
        'uso_porcentaje',
        0
    );

    $swapUso = obtenerNumeroEstado(
        $swap,
        'uso_porcentaje',
        0
    );

    $discoUso = obtenerNumeroEstado(
        $discoServidor,
        'uso_porcentaje',
        0
    );
    ?>

    <div class="rejilla-servidor">
        <article class="tarjeta-servidor">
            <div class="tarjeta-servidor__encabezado">
                <span>Procesador</span>

                <strong>
                    <?= number_format(
                        (float) $cpuUso,
                        1,
                        ',',
                        '.'
                    ) ?>%
                </strong>
            </div>

            <div class="barra-metrica">
                <div
                    class="barra-metrica__progreso"
                    style="width: <?= min(
                        100,
                        (float) $cpuUso
                    ) ?>%"
                ></div>
            </div>

            <small>
                <?= (int) (
                    $cpu['hilos'] ?? 0
                ) ?> hilos · carga
                <?= number_format(
                    (float) ($cpu['carga_1'] ?? 0),
                    2,
                    ',',
                    '.'
                ) ?>
            </small>
        </article>

        <article class="tarjeta-servidor">
            <div class="tarjeta-servidor__encabezado">
                <span>Memoria RAM</span>

                <strong>
                    <?= number_format(
                        (float) $memoriaUso,
                        1,
                        ',',
                        '.'
                    ) ?>%
                </strong>
            </div>

            <div class="barra-metrica">
                <div
                    class="barra-metrica__progreso"
                    style="width: <?= min(
                        100,
                        (float) $memoriaUso
                    ) ?>%"
                ></div>
            </div>

            <small>
                <?= htmlspecialchars(
                    formatearBytes(
                        (int) (
                            $memoria[
                                'disponible_bytes'
                            ] ?? 0
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
                disponibles
            </small>
        </article>

        <article class="tarjeta-servidor">
            <div class="tarjeta-servidor__encabezado">
                <span>Swap</span>

                <strong>
                    <?= number_format(
                        (float) $swapUso,
                        1,
                        ',',
                        '.'
                    ) ?>%
                </strong>
            </div>

            <div class="barra-metrica">
                <div
                    class="barra-metrica__progreso"
                    style="width: <?= min(
                        100,
                        (float) $swapUso
                    ) ?>%"
                ></div>
            </div>

            <small>
                <?= htmlspecialchars(
                    formatearBytes(
                        (int) (
                            $swap['usada_bytes'] ?? 0
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
                utilizados
            </small>
        </article>

        <article class="tarjeta-servidor">
            <div class="tarjeta-servidor__encabezado">
                <span>Disco raíz</span>

                <strong>
                    <?= number_format(
                        (float) $discoUso,
                        1,
                        ',',
                        '.'
                    ) ?>%
                </strong>
            </div>

            <div class="barra-metrica">
                <div
                    class="barra-metrica__progreso"
                    style="width: <?= min(
                        100,
                        (float) $discoUso
                    ) ?>%"
                ></div>
            </div>

            <small>
                <?= htmlspecialchars(
                    formatearBytes(
                        (int) (
                            $discoServidor[
                                'libre_bytes'
                            ] ?? 0
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
                libres
            </small>
        </article>

        <article class="tarjeta-servidor">
            <span>Temperatura CPU</span>

            <strong>
                <?= number_format(
                    (float) (
                        $cpu['temperatura_c'] ?? 0
                    ),
                    1,
                    ',',
                    '.'
                ) ?> °C
            </strong>

            <small>Intel Atom N450</small>
        </article>

        <article class="tarjeta-servidor">
            <span>Tiempo encendido</span>

            <strong>
                <?= htmlspecialchars(
                    formatearDuracionServidor(
                        isset($sistema['uptime_segundos'])
                            ? (int) $sistema[
                                'uptime_segundos'
                            ]
                            : null
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

            <small>Desde el último arranque</small>
        </article>

        <article class="tarjeta-servidor">
            <span>Contenedores Docker</span>

            <strong>
                <?= (int) ($docker['activos'] ?? 0) ?>
                / <?= (int) ($docker['total'] ?? 0) ?>
            </strong>

            <small>
                <?= (int) ($docker['healthy'] ?? 0) ?>
                saludables ·
                <?= (int) ($docker['unhealthy'] ?? 0) ?>
                con fallas
            </small>
        </article>

        <article class="tarjeta-servidor">
            <span>Servicios y timers</span>

            <strong>
                <?= (int) (
                    $sistema['servicios_fallidos'] ?? 0
                ) ?>
                fallidos
            </strong>

            <small>
                <?= (int) ($sistema['timers_jgg'] ?? 0) ?>
                timers JGG registrados
            </small>
        </article>
    </div>

    <div class="detalle-servidor">
        <div>
            <span>Contenedores activos</span>

            <strong>
                <?= htmlspecialchars(
                    str_replace(
                        ',',
                        ' · ',
                        (string) (
                            $docker[
                                'nombres_activos'
                            ] ?? 'Sin datos'
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </div>

        <div>
            <span>Actualización</span>

            <strong>
                <?= htmlspecialchars(
                    formatearAntiguedadSmart(
                        $estadoGeneralServidor[
                            'antiguedad_segundos'
                        ]
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

            <?php if (
                $estadoGeneralServidor[
                    'actualizado_unix'
                ] > 0
            ): ?>
                <small>
                    <?= date(
                        'd/m/Y H:i:s',
                        $estadoGeneralServidor[
                            'actualizado_unix'
                        ]
                    ) ?>
                </small>
            <?php endif; ?>
        </div>
    </div>

    <p class="nota-servidor">
        Las métricas se generan cada cinco minutos mediante
        systemd. El panel no ejecuta comandos privilegiados.
    </p>
</section>

<section
