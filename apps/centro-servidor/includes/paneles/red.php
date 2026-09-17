<section
    id="panel-red-servidor"
    class="panel panel-dispositivos panel-red"
>
    <div class="panel__encabezado">
        <div>
            <p class="etiqueta">
                Infraestructura y acceso
            </p>

            <h2>Red y conectividad</h2>
        </div>

        <span
            class="estado-red estado-red--<?= htmlspecialchars(
                obtenerClaseEstadoRed(
                    $estadoRed['nivel']
                        ?? 'sin-datos'
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
                        $estadoRed['nivel']
                            ?? 'sin datos'
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>
    </div>

    <?php if (!$estadoRed['disponible']): ?>
        <div class="mensaje-flotante mensaje-flotante--error">
            No se pudo leer el estado de red.
        </div>
    <?php elseif ($estadoRed['nivel'] !== 'normal'): ?>
        <div class="mensaje-red mensaje-red--<?= htmlspecialchars(
            obtenerClaseEstadoRed(
                $estadoRed['nivel']
            ),
            ENT_QUOTES,
            'UTF-8'
        ) ?>">
            <?= htmlspecialchars(
                $estadoRed['mensaje'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <div class="resumen-red">
        <div>
            <span>Interfaz</span>

            <strong>
                <?= htmlspecialchars(
                    (string) (
                        $estadoRed['interfaz']['nombre']
                        ?? 'Sin datos'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </div>

        <div>
            <span>Dirección IP</span>

            <strong>
                <?= htmlspecialchars(
                    (string) (
                        $estadoRed['interfaz']['cidr']
                        ?? 'Sin datos'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </div>

        <div>
            <span>Señal Wi-Fi</span>

            <strong>
                <?= (int) (
                    $estadoRed['wifi']['senal_porcentaje']
                    ?? 0
                ) ?> %
            </strong>
        </div>

        <div>
            <span>Puertos</span>

            <strong>
                <?= (int) (
                    $estadoRed['puertos']['abiertos']
                    ?? 0
                ) ?>
                /
                <?= (int) (
                    $estadoRed['puertos']['total']
                    ?? 0
                ) ?>
            </strong>
        </div>
    </div>

    <div class="rejilla-red-principal">
        <article class="tarjeta-red">
            <div class="tarjeta-red__encabezado">
                <div>
                    <span>Conexión inalámbrica</span>
                    <h3>Wi-Fi</h3>
                </div>

                <span
                    class="insignia-red insignia-red--<?= htmlspecialchars(
                        obtenerClaseEstadoRed(
                            (string) (
                                $estadoRed['wifi']['nivel']
                                ?? 'sin-datos'
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            (string) (
                                $estadoRed['wifi']['nivel']
                                ?? 'Sin datos'
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <dl class="detalle-red">
                <div>
                    <dt>SSID</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['wifi']['ssid']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Señal</dt>

                    <dd>
                        <?= (int) (
                            $estadoRed['wifi'][
                                'senal_porcentaje'
                            ] ?? 0
                        ) ?> %
                    </dd>
                </div>

                <div>
                    <dt>Velocidad</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['wifi']['velocidad']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Frecuencia</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['wifi']['frecuencia']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Canal</dt>

                    <dd>
                        <?= (int) (
                            $estadoRed['wifi']['canal']
                            ?? 0
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Seguridad</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['wifi']['seguridad']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>
            </dl>
        </article>

        <article class="tarjeta-red">
            <div class="tarjeta-red__encabezado">
                <div>
                    <span>Configuración local</span>
                    <h3>Interfaz y direccionamiento</h3>
                </div>

                <span
                    class="insignia-red insignia-red--<?= !empty(
                        $estadoRed['interfaz']['ip_correcta']
                    )
                        ? 'normal'
                        : 'critico' ?>"
                >
                    <?= !empty(
                        $estadoRed['interfaz']['ip_correcta']
                    )
                        ? 'Correcta'
                        : 'Revisar' ?>
                </span>
            </div>

            <dl class="detalle-red">
                <div>
                    <dt>Interfaz</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['interfaz']['nombre']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Estado</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['interfaz']['estado']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Conexión</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['interfaz'][
                                    'conexion'
                                ] ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>IPv4</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['interfaz']['cidr']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Gateway</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['interfaz']['gateway']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>DNS</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['interfaz']['dns']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>
            </dl>
        </article>

        <article class="tarjeta-red">
            <div class="tarjeta-red__encabezado">
                <div>
                    <span>Red local</span>
                    <h3>Router y LAN</h3>
                </div>

                <span
                    class="insignia-red insignia-red--<?= htmlspecialchars(
                        obtenerClaseEstadoRed(
                            (string) (
                                $estadoRed['lan']['nivel']
                                ?? 'sin-datos'
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            (string) (
                                $estadoRed['lan']['nivel']
                                ?? 'Sin datos'
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <dl class="detalle-red">
                <div>
                    <dt>Router</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['lan']['router']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Estado</dt>

                    <dd>
                        <?= htmlspecialchars(
                            formatearEstadoBooleanoRed(
                                !empty(
                                    $estadoRed['lan'][
                                        'disponible'
                                    ]
                                )
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Latencia</dt>

                    <dd>
                        <?= htmlspecialchars(
                            formatearLatenciaRed(
                                $estadoRed['lan']['latencia_ms']
                                ?? null
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Pérdida</dt>

                    <dd>
                        <?= htmlspecialchars(
                            formatearPerdidaRed(
                                $estadoRed['lan'][
                                    'perdida_porcentaje'
                                ] ?? null
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>
            </dl>
        </article>

        <article class="tarjeta-red">
            <div class="tarjeta-red__encabezado">
                <div>
                    <span>Salida externa</span>
                    <h3>Internet y DNS</h3>
                </div>

                <span
                    class="insignia-red insignia-red--<?= htmlspecialchars(
                        obtenerClaseEstadoRed(
                            (string) (
                                $estadoRed['internet']['nivel']
                                ?? 'sin-datos'
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            (string) (
                                $estadoRed['internet']['nivel']
                                ?? 'Sin datos'
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <dl class="detalle-red">
                <div>
                    <dt>Internet</dt>

                    <dd>
                        <?= htmlspecialchars(
                            formatearEstadoBooleanoRed(
                                !empty(
                                    $estadoRed['internet'][
                                        'disponible'
                                    ]
                                )
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Latencia</dt>

                    <dd>
                        <?= htmlspecialchars(
                            formatearLatenciaRed(
                                $estadoRed['internet'][
                                    'latencia_ms'
                                ] ?? null
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Pérdida</dt>

                    <dd>
                        <?= htmlspecialchars(
                            formatearPerdidaRed(
                                $estadoRed['internet'][
                                    'perdida_porcentaje'
                                ] ?? null
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>DNS</dt>

                    <dd>
                        <?= htmlspecialchars(
                            formatearEstadoBooleanoRed(
                                !empty(
                                    $estadoRed['dns'][
                                        'funcionando'
                                    ]
                                )
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>Dominio de prueba</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['dns'][
                                    'dominio_prueba'
                                ] ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>IP resuelta</dt>

                    <dd>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['dns']['ip_resuelta']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>
            </dl>
        </article>
    </div>

    <?php
    $ngG = $estadoNetworkGuardian['guardian'] ?? [];
    $ngU = $estadoNetworkGuardian['uplink'] ?? [];
    $ngC = $estadoNetworkGuardian['comprobacion'] ?? [];
    $ngR = $estadoNetworkGuardian['recuperaciones'] ?? [];

    $ngModo = (string) ($ngG['modo'] ?? '');
    $ngTipo = (string) ($ngU['tipo'] ?? '');

    $ngUplinkTexto = match ($ngTipo) {
        'wifi' => 'Wi-Fi',
        'ethernet' => 'Ethernet',
        default => 'Sin datos',
    };

    $ngAutoTexto = !empty($ngG['recuperacion_habilitada'])
        ? 'Habilitada'
        : ($ngModo === 'ethernet-monitor'
            ? 'Solo monitoreo'
            : 'Deshabilitada');

    $ngResultado = ucfirst(
        str_replace(
            '_',
            ' ',
            (string) ($ngR['ultimo_resultado'] ?? 'sin_intentos')
        )
    );
    ?>

    <div class="encabezado-puertos-red">
        <div>
            <span>Protección de conectividad</span>
            <strong>JGG Network Guardian</strong>
        </div>

        <span><?= htmlspecialchars(
            ucfirst(
                (string) (
                    $estadoNetworkGuardian['nivel']
                    ?? 'Sin datos'
                )
            ),
            ENT_QUOTES,
            'UTF-8'
        ) ?></span>
    </div>

    <div class="resumen-red">
        <div>
            <span>Uplink protegido</span>
            <strong><?= htmlspecialchars(
                $ngUplinkTexto
                . ' · '
                . (string) ($ngU['interfaz'] ?? 'Sin datos'),
                ENT_QUOTES,
                'UTF-8'
            ) ?></strong>
        </div>

        <div>
            <span>Autorrecuperación</span>
            <strong><?= htmlspecialchars(
                $ngAutoTexto,
                ENT_QUOTES,
                'UTF-8'
            ) ?></strong>
        </div>

        <div>
            <span>Fallos consecutivos</span>
            <strong>
                <?= (int) ($ngG['fallos_consecutivos'] ?? 0) ?>
                /
                <?= (int) ($ngG['umbral_fallos'] ?? 0) ?>
            </strong>
        </div>

        <div>
            <span>Recuperaciones</span>
            <strong>
                <?= (int) ($ngR['exitosas'] ?? 0) ?> OK ·
                <?= (int) ($ngR['fallidas'] ?? 0) ?> fallidas
            </strong>
        </div>
    </div>

    <div class="pie-red">
        <span>
            Gateway
            <?= htmlspecialchars(
                (string) ($ngU['gateway'] ?? 'Sin datos'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
            ·
            <?= htmlspecialchars(
                $ngResultado,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>

        <strong>
            Latencia
            <?= htmlspecialchars(
                formatearLatenciaRed(
                    $ngC['latencia_ms'] ?? null
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>
    </div>

    <div class="encabezado-puertos-red">
        <div>
            <span>Servicios publicados</span>

            <strong>Puertos esenciales del servidor</strong>
        </div>

        <span>
            <?= (int) (
                $estadoRed['puertos']['abiertos']
                ?? 0
            ) ?>
            de
            <?= (int) (
                $estadoRed['puertos']['total']
                ?? 0
            ) ?>
            disponibles
        </span>
    </div>

    <div class="rejilla-puertos-red">
        <?php foreach (
            $estadoRed['puertos']['elementos']
            as $puerto
        ): ?>
            <article
                class="tarjeta-puerto-red tarjeta-puerto-red--<?= htmlspecialchars(
                    obtenerClaseEstadoRed(
                        (string) (
                            $puerto['nivel']
                            ?? 'sin-datos'
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
                <div>
                    <span>
                        <?= htmlspecialchars(
                            (string) (
                                $puerto['nombre']
                                ?? 'Servicio'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <strong>
                        <?= (int) (
                            $puerto['puerto']
                            ?? 0
                        ) ?>
                    </strong>
                </div>

                <span
                    class="insignia-red insignia-red--<?= !empty(
                        $puerto['abierto']
                    )
                        ? 'normal'
                        : 'critico' ?>"
                >
                    <?= !empty($puerto['abierto'])
                        ? 'Abierto'
                        : 'Cerrado' ?>
                </span>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="pie-red">
        <span>
            <?= htmlspecialchars(
                $estadoRed['mensaje'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>

        <strong>
            Actualizado
            <?= htmlspecialchars(
                formatearAntiguedadSmart(
                    $estadoRed['antiguedad_segundos']
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>
    </div>
</section>
