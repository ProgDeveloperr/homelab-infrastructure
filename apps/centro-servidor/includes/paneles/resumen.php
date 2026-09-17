<section
    id="resumen-centro-servidor"
    class="panel panel-dispositivos panel-resumen-centro"
>
    <div class="panel__encabezado">
        <div>
            <p class="etiqueta">
                Vista ejecutiva
            </p>

            <h2>Resumen del servidor</h2>
        </div>

        <span
            class="estado-resumen estado-resumen--<?= htmlspecialchars(
                obtenerClaseEstadoServicio(
                    $estadoGeneralServidor['nivel']
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
                        $estadoGeneralServidor['nivel']
                            ?? 'sin datos'
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>
    </div>

    <div class="rejilla-resumen-principal">
        <article class="tarjeta-resumen tarjeta-resumen--sistema">
            <div class="tarjeta-resumen__encabezado">
                <div>
                    <span>Sistema</span>
                    <h3>Estado general</h3>
                </div>

                <span
                    class="insignia-resumen insignia-resumen--<?= htmlspecialchars(
                        obtenerClaseEstadoServicio(
                            $estadoGeneralServidor['nivel']
                                ?? 'sin-datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            $estadoGeneralServidor['nivel']
                                ?? 'Sin datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <div class="metricas-resumen">
                <div>
                    <span>CPU</span>
                    <strong>
                        <?= number_format(
                            (float) (
                                $estadoGeneralServidor['cpu'][
                                    'uso_porcentaje'
                                ] ?? 0
                            ),
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </strong>
                </div>

                <div>
                    <span>RAM</span>
                    <strong>
                        <?= number_format(
                            (float) (
                                $estadoGeneralServidor['memoria'][
                                    'uso_porcentaje'
                                ] ?? 0
                            ),
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </strong>
                </div>

                <div>
                    <span>Disco</span>
                    <strong>
                        <?= (int) (
                            $estadoGeneralServidor['disco'][
                                'uso_porcentaje'
                            ] ?? 0
                        ) ?>%
                    </strong>
                </div>

                <div>
                    <span>Temperatura</span>
                    <strong>
                        <?= number_format(
                            (float) (
                                $estadoGeneralServidor['cpu'][
                                    'temperatura_c'
                                ] ?? 0
                            ),
                            1,
                            ',',
                            '.'
                        ) ?> °C
                    </strong>
                </div>
            </div>

            <div class="tarjeta-resumen__pie">
                <span>
                    <?= htmlspecialchars(
                        $estadoGeneralServidor['mensaje']
                            ?? 'Sin información',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <a href="/centro-servidor/disco.php">
                    Ver recursos
                </a>
            </div>
        </article>

        <article class="tarjeta-resumen tarjeta-resumen--disco">
            <div class="tarjeta-resumen__encabezado">
                <div>
                    <span>Almacenamiento</span>
                    <h3>Salud SMART</h3>
                </div>

                <span
                    class="insignia-resumen insignia-resumen--<?= htmlspecialchars(
                        obtenerClaseEstadoServicio(
                            $estadoSmart['nivel']
                                ?? 'sin-datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            $estadoSmart['nivel']
                                ?? 'Sin datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <div class="metricas-resumen">
                <div>
                    <span>SMART</span>
                    <strong>
                        <?= !empty($estadoSmart['smart_pasado'])
                            ? 'PASSED'
                            : 'Revisar' ?>
                    </strong>
                </div>

                <div>
                    <span>Temperatura</span>
                    <strong>
                        <?= (int) (
                            $estadoSmart['temperatura_c']
                                ?? 0
                        ) ?> °C
                    </strong>
                </div>

                <div>
                    <span>Pendientes</span>
                    <strong>
                        <?= (int) (
                            $estadoSmart['sectores_pendientes']
                                ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>CRC SATA</span>
                    <strong>
                        <?= (int) (
                            $estadoSmart['errores_crc']
                                ?? 0
                        ) ?>
                    </strong>
                </div>
            </div>

            <div class="tarjeta-resumen__pie">
                <span>
                    <?= htmlspecialchars(
                        $estadoSmart['mensaje']
                            ?? 'Sin información',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <a href="/centro-servidor/disco.php">
                    Abrir diagnóstico
                </a>
            </div>
        </article>

        <article class="tarjeta-resumen tarjeta-resumen--servicios">
            <div class="tarjeta-resumen__encabezado">
                <div>
                    <span>Docker</span>
                    <h3>Servicios</h3>
                </div>

                <span
                    class="insignia-resumen insignia-resumen--<?= htmlspecialchars(
                        obtenerClaseEstadoServicio(
                            $estadoServicios['nivel']
                                ?? 'sin-datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            $estadoServicios['nivel']
                                ?? 'Sin datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <div class="metricas-resumen">
                <div>
                    <span>Activos</span>
                    <strong>
                        <?= (int) (
                            $estadoServicios['resumen']['activos']
                                ?? 0
                        ) ?>
                        /
                        <?= (int) (
                            $estadoServicios['resumen']['total']
                                ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Healthy</span>
                    <strong>
                        <?= (int) (
                            $estadoServicios['resumen']['healthy']
                                ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Detenidos</span>
                    <strong>
                        <?= (int) (
                            $estadoServicios['resumen']['detenidos']
                                ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Unhealthy</span>
                    <strong>
                        <?= (int) (
                            $estadoServicios['resumen']['unhealthy']
                                ?? 0
                        ) ?>
                    </strong>
                </div>
            </div>

            <div class="tarjeta-resumen__pie">
                <span>
                    <?= htmlspecialchars(
                        $estadoServicios['mensaje']
                            ?? 'Sin información',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <a href="/centro-servidor/servicios.php">
                    Ver servicios
                </a>
            </div>
        </article>

        <article class="tarjeta-resumen tarjeta-resumen--actividad">
            <div class="tarjeta-resumen__encabezado">
                <div>
                    <span>Automatización</span>
                    <h3>Actividad</h3>
                </div>

                <span
                    class="insignia-resumen insignia-resumen--<?= htmlspecialchars(
                        obtenerClaseEstadoEvento(
                            $estadoEventos['nivel']
                                ?? 'sin-datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            $estadoEventos['nivel']
                                ?? 'Sin datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <div class="metricas-resumen">
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
                    <span>Arranque anterior</span>
                    <strong>
                        <?= !empty(
                            $estadoEventos['arranque'][
                                'anterior_crash'
                            ]
                        )
                            ? 'Inesperado'
                            : 'Normal' ?>
                    </strong>
                </div>
            </div>

            <div class="tarjeta-resumen__pie">
                <span>
                    <?= htmlspecialchars(
                        $estadoEventos['mensaje']
                            ?? 'Sin información',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <a href="/centro-servidor/actividad.php">
                    Ver actividad
                </a>
            </div>
        </article>

        <article class="tarjeta-resumen tarjeta-resumen--backups">
            <div class="tarjeta-resumen__encabezado">
                <div>
                    <span>Protección</span>
                    <h3>Backups</h3>
                </div>

                <span
                    class="insignia-resumen insignia-resumen--<?= htmlspecialchars(
                        obtenerClaseEstadoBackup(
                            $estadoBackups['nivel']
                                ?? 'sin-datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        ucfirst(
                            $estadoBackups['nivel']
                                ?? 'Sin datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <div class="metricas-resumen">
                <div>
                    <span>Configuración</span>
                    <strong>
                        <?= ucfirst(
                            $estadoBackups['configuracion']['nivel']
                                ?? 'Sin datos'
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Docker</span>
                    <strong>
                        <?= ucfirst(
                            $estadoBackups['docker']['nivel']
                                ?? 'Sin datos'
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Checksum</span>
                    <strong>
                        <?= !empty(
                            $estadoBackups['docker'][
                                'checksum_valido'
                            ]
                        )
                            ? 'Válido'
                            : 'Revisar' ?>
                    </strong>
                </div>

                <div>
                    <span>Manifiesto</span>
                    <strong>
                        <?= !empty(
                            $estadoBackups['docker'][
                                'manifiesto_existe'
                            ]
                        )
                            ? 'Presente'
                            : 'Faltante' ?>
                    </strong>
                </div>
            </div>

            <div class="tarjeta-resumen__pie">
                <span>
                    <?= htmlspecialchars(
                        $estadoBackups['mensaje']
                            ?? 'Sin información',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <a href="/centro-servidor/backups.php">
                    Ver backups
                </a>
            </div>
        </article>

        <article class="tarjeta-resumen tarjeta-resumen--red">
            <div class="tarjeta-resumen__encabezado">
                <div>
                    <span>Conectividad</span>
                    <h3>Red</h3>
                </div>

                <span
                    class="insignia-resumen insignia-resumen--<?= htmlspecialchars(
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
                            $estadoRed['nivel']
                                ?? 'Sin datos'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <div class="metricas-resumen">
                <div>
                    <span>IP</span>

                    <strong>
                        <?= htmlspecialchars(
                            (string) (
                                $estadoRed['interfaz']['ip']
                                ?? 'Sin datos'
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Wi-Fi</span>

                    <strong>
                        <?= (int) (
                            $estadoRed['wifi'][
                                'senal_porcentaje'
                            ] ?? 0
                        ) ?> %
                    </strong>
                </div>

                <div>
                    <span>Internet</span>

                    <strong>
                        <?= !empty(
                            $estadoRed['internet'][
                                'disponible'
                            ]
                        )
                            ? 'Disponible'
                            : 'Sin acceso' ?>
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

            <div class="tarjeta-resumen__pie">
                <span>
                    <?= htmlspecialchars(
                        $estadoRed['mensaje']
                            ?? 'Sin información',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <a href="/centro-servidor/red.php">
                    Ver red
                </a>
            </div>
        </article>
    </div>

    <p class="nota-resumen-centro">
        El inicio muestra únicamente indicadores consolidados.
        Cada sección contiene su diagnóstico completo.
    </p>
</section>
