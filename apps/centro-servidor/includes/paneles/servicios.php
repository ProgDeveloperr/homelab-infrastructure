       <section     id="panel-servicios-servidor"
            class="panel panel-dispositivos panel-servicios"
        >
            <div class="panel__encabezado">
                <div>
                    <p class="etiqueta">
                        Docker y aplicaciones
                    </p>

                    <h2>Servicios del servidor</h2>
                </div>

                <span
                    class="estado-servicios estado-servicios--<?= htmlspecialchars(
                        obtenerClaseEstadoServicio(
                            $estadoServicios['nivel']
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
                                $estadoServicios['nivel']
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <?php if (!$estadoServicios['disponible']): ?>
                <div class="mensaje-flotante mensaje-flotante--error">
                    No se pudo leer el estado de los servicios.
                </div>
            <?php elseif (
                $estadoServicios['nivel'] !== 'normal'
            ): ?>
                <div class="mensaje-flotante mensaje-flotante--error">
                    <?= htmlspecialchars(
                        $estadoServicios['mensaje'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <div class="resumen-servicios">
                <div>
                    <span>Total</span>

                    <strong>
                        <?= (int) (
                            $estadoServicios['resumen']['total']
                            ?? 0
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Activos</span>

                    <strong>
                        <?= (int) (
                            $estadoServicios['resumen']['activos']
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
                    <span>Sin healthcheck</span>

                    <strong>
                        <?= (int) (
                            $estadoServicios['resumen'][
                                'sin_healthcheck'
                            ] ?? 0
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

            <div class="rejilla-servicios">
                <?php foreach (
                    $estadoServicios['servicios']
                    as $servicio
                ): ?>
                    <?php
                    $nivelServicio =
                        obtenerClaseEstadoServicio(
                            (string) (
                                $servicio['nivel']
                                ?? 'sin-datos'
                            )
                        );

                    $urlServicio = trim(
                        (string) (
                            $servicio['url'] ?? ''
                        )
                    );
                    ?>

                    <article
                        class="tarjeta-servicio tarjeta-servicio--<?= htmlspecialchars(
                            $nivelServicio,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <div class="tarjeta-servicio__encabezado">
                            <div>
                                <span class="servicio-contenedor">
                                    <?= htmlspecialchars(
                                        (string) (
                                            $servicio['nombre']
                                            ?? 'desconocido'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                                <h3>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $servicio[
                                                'nombre_visible'
                                            ] ?? 'Servicio'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $servicio[
                                                'descripcion'
                                            ] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>
                            </div>

                            <span
                                class="insignia-servicio insignia-servicio--<?= htmlspecialchars(
                                    $nivelServicio,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    formatearEstadoContenedor(
                                        (string) (
                                            $servicio['estado']
                                            ?? 'desconocido'
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </div>

                        <dl class="detalle-servicio">
                            <div>
                                <dt>Salud</dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        formatearSaludServicio(
                                            (string) (
                                                $servicio['salud']
                                                ?? 'sin-datos'
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>

                            <div>
                                <dt>Tiempo activo</dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        formatearUptimeServicio(
                                            $servicio[
                                                'uptime_segundos'
                                            ] ?? null
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>

                            <div>
                                <dt>Puerto</dt>

                                <dd>
                                    <?php if (
                                        !empty(
                                            $servicio['publicado']
                                        )
                                    ): ?>
                                        <?= (int) (
                                            $servicio['puerto']
                                            ?? 0
                                        ) ?>
                                    <?php else: ?>
                                        Interno:
                                        <?= (int) (
                                            $servicio['puerto']
                                            ?? 0
                                        ) ?>
                                    <?php endif; ?>
                                </dd>
                            </div>

                            <div>
                                <dt>Reinicio</dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $servicio[
                                                'politica_reinicio'
                                            ] ?? 'desconocido'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>

                            <div>
                                <dt>Imagen</dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $servicio['imagen']
                                            ?? 'desconocida'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>
                        </dl>

                        <div class="tarjeta-servicio__pie">
                            <span>
                                <?= htmlspecialchars(
                                    (string) (
                                        $servicio['mensaje']
                                        ?? 'Sin datos'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <?php if ($urlServicio !== ''): ?>
                                <a
                                    href="<?= htmlspecialchars(
                                        $urlServicio,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Abrir
                                </a>
                            <?php else: ?>
                                <span class="servicio-interno">
                                    Solo red Docker
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="pie-servicios">
                <span>
                    <?= htmlspecialchars(
                        $estadoServicios['mensaje'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <strong>
                    Actualizado
                    <?= htmlspecialchars(
                        formatearAntiguedadSmart(
                            $estadoServicios[
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
