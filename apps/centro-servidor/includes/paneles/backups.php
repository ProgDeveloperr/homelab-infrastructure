         <section   id="panel-backups-servidor"
            class="panel panel-dispositivos panel-backups"
        >
            <div class="panel__encabezado">
                <div>
                    <p class="etiqueta">
                        Protección y recuperación
                    </p>

                    <h2>Backups del servidor</h2>
                </div>

                <span
                    class="estado-backups estado-backups--<?= htmlspecialchars(
                        obtenerClaseEstadoBackup(
                            $estadoBackups['nivel']
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
                                $estadoBackups['nivel']
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <?php if (!$estadoBackups['disponible']): ?>
                <div class="mensaje-flotante mensaje-flotante--error">
                    No se pudo leer el estado de los backups.
                </div>
            <?php elseif ($estadoBackups['nivel'] !== 'normal'): ?>
                <div class="mensaje-flotante mensaje-flotante--error">
                    <?= htmlspecialchars(
                        $estadoBackups['mensaje'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <?php
            $backupConfiguracion =
                $estadoBackups['configuracion'];

            $backupDocker =
                $estadoBackups['docker'];
            ?>

            <div class="rejilla-backups">
                <article class="tarjeta-backup">
                    <div class="tarjeta-backup__encabezado">
                        <div>
                            <span>Configuración</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $backupConfiguracion[
                                        'mensaje'
                                    ] ?? 'Sin datos',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>

                        <span
                            class="insignia-backup insignia-backup--<?= htmlspecialchars(
                                obtenerClaseEstadoBackup(
                                    (string) (
                                        $backupConfiguracion[
                                            'nivel'
                                        ] ?? 'sin-datos'
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <?= htmlspecialchars(
                                ucfirst(
                                    (string) (
                                        $backupConfiguracion[
                                            'nivel'
                                        ] ?? 'sin datos'
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>

                    <dl class="detalle-backup">
                        <div>
                            <dt>Último archivo</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    $backupConfiguracion[
                                        'nombre'
                                    ] ?? 'Sin datos',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Fecha</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    formatearFechaBackup(
                                        $backupConfiguracion[
                                            'timestamp'
                                        ] ?? null
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Tamaño</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    formatearBytes(
                                        (int) (
                                            $backupConfiguracion[
                                                'tamanio_bytes'
                                            ] ?? 0
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Integridad</dt>

                            <dd>
                                <?php if (
                                    !empty(
                                        $backupConfiguracion[
                                            'archivo_valido'
                                        ]
                                    )
                                    && !empty(
                                        $backupConfiguracion[
                                            'sha256_interno'
                                        ]
                                    )
                                ): ?>
                                    Archivo válido · SHA256SUMS presente
                                <?php else: ?>
                                    Requiere revisión
                                <?php endif; ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Servicio</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    (string) (
                                        $backupConfiguracion[
                                            'resultado_servicio'
                                        ] ?? 'desconocido'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Próxima ejecución</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    formatearProximaEjecucionBackup(
                                        $backupConfiguracion[
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

                <article class="tarjeta-backup">
                    <div class="tarjeta-backup__encabezado">
                        <div>
                            <span>Datos Docker</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $backupDocker[
                                        'mensaje'
                                    ] ?? 'Sin datos',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>

                        <span
                            class="insignia-backup insignia-backup--<?= htmlspecialchars(
                                obtenerClaseEstadoBackup(
                                    (string) (
                                        $backupDocker[
                                            'nivel'
                                        ] ?? 'sin-datos'
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <?= htmlspecialchars(
                                ucfirst(
                                    (string) (
                                        $backupDocker[
                                            'nivel'
                                        ] ?? 'sin datos'
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>

                    <dl class="detalle-backup">
                        <div>
                            <dt>Último archivo</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    $backupDocker[
                                        'nombre'
                                    ] ?? 'Sin datos',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Fecha</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    formatearFechaBackup(
                                        $backupDocker[
                                            'timestamp'
                                        ] ?? null
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Tamaño</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    formatearBytes(
                                        (int) (
                                            $backupDocker[
                                                'tamanio_bytes'
                                            ] ?? 0
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Integridad</dt>

                            <dd>
                                <?php if (
                                    !empty(
                                        $backupDocker[
                                            'archivo_valido'
                                        ]
                                    )
                                    && !empty(
                                        $backupDocker[
                                            'checksum_valido'
                                        ]
                                    )
                                    && !empty(
                                        $backupDocker[
                                            'manifiesto_existe'
                                        ]
                                    )
                                ): ?>
                                    Archivo, checksum y manifiesto correctos
                                <?php else: ?>
                                    Requiere revisión
                                <?php endif; ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Servicio</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    (string) (
                                        $backupDocker[
                                            'resultado_servicio'
                                        ] ?? 'desconocido'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Próxima ejecución</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    formatearProximaEjecucionBackup(
                                        $backupDocker[
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
            </div>

            <div class="resumen-backups">
                <div>
                    <span>Estado general</span>

                    <strong>
                        <?= htmlspecialchars(
                            $estadoBackups['mensaje'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Última actualización</span>

                    <strong>
                        <?= htmlspecialchars(
                            formatearAntiguedadSmart(
                                $estadoBackups[
                                    'antiguedad_segundos'
                                ]
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>
                </div>
            </div>

            <p class="nota-backups">
                La verificación comprueba archivos comprimidos,
                checksums, manifiestos, estado de systemd y
                antigüedad de cada copia.
            </p>
        </section>
