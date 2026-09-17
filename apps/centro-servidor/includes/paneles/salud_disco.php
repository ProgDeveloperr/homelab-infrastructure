<section id="panel-salud-disco" class="panel panel-dispositivos panel-smart">
    <div class="panel__encabezado">
        <div>
            <p class="etiqueta">Diagnóstico preventivo</p>
            <h2>Salud del disco</h2>
        </div>

        <span
            class="estado-smart estado-smart--<?= htmlspecialchars(
                $estadoSmart['nivel'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
            <?= htmlspecialchars(
                ucfirst(
                    str_replace(
                        '-',
                        ' ',
                        $estadoSmart['nivel']
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>
    </div>

    <?php if (!$estadoSmart['disponible']): ?>
        <div class="mensaje-flotante mensaje-flotante--error">
            No se pudo leer el estado SMART generado por el servidor.
        </div>
    <?php elseif ($estadoSmart['nivel'] !== 'normal'): ?>
        <div class="mensaje-flotante mensaje-flotante--error">
            <?= htmlspecialchars(
                $estadoSmart['mensaje'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <div class="rejilla-smart">
        <article class="tarjeta-smart tarjeta-smart--principal">
            <span>Estado general</span>

            <strong>
                <?= htmlspecialchars(
                    $estadoSmart['smart_general'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

            <small>
                <?= htmlspecialchars(
                    $estadoSmart['mensaje'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </small>
        </article>

        <article class="tarjeta-smart">
            <span>Temperatura</span>

            <strong>
                <?php if ($estadoSmart['temperatura_c'] !== null): ?>
                    <?= $estadoSmart['temperatura_c'] ?> °C
                <?php else: ?>
                    Sin datos
                <?php endif; ?>
            </strong>

            <small>Temperatura actual del HDD</small>
        </article>

        <article class="tarjeta-smart">
            <span>Horas encendido</span>

            <strong>
                <?= htmlspecialchars(
                    formatearHorasSmart(
                        $estadoSmart['horas_encendido']
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

            <small>Uso acumulado del disco</small>
        </article>

        <article class="tarjeta-smart">
            <span>Sectores reasignados</span>

            <strong>
                <?= $estadoSmart['sectores_reasignados'] ?? '—' ?>
            </strong>

            <small>Deben permanecer en cero</small>
        </article>

        <article class="tarjeta-smart">
            <span>Sectores pendientes</span>

            <strong>
                <?= $estadoSmart['sectores_pendientes'] ?? '—' ?>
            </strong>

            <small>Lecturas que requieren seguimiento</small>
        </article>

        <article class="tarjeta-smart">
            <span>No corregibles</span>

            <strong>
                <?= $estadoSmart[
                    'sectores_no_corregibles'
                ] ?? '—' ?>
            </strong>

            <small>Errores que el disco no pudo recuperar</small>
        </article>

        <article class="tarjeta-smart">
            <span>Errores CRC SATA</span>

            <strong>
                <?= $estadoSmart['errores_crc'] ?? '—' ?>
            </strong>

            <small>
                <?php if (
                    ($estadoSmart['errores_crc_nuevos'] ?? 0) > 0
                ): ?>
                    +<?= $estadoSmart['errores_crc_nuevos'] ?>
                    desde el último control
                <?php else: ?>
                    Sin incrementos recientes
                <?php endif; ?>
            </small>
        </article>

        <article class="tarjeta-smart">
            <span>Command timeout</span>

            <strong>
                <?= $estadoSmart['command_timeout'] ?? '—' ?>
            </strong>

            <small>
                <?php if (
                    ($estadoSmart[
                        'command_timeout_nuevos'
                    ] ?? 0) > 0
                ): ?>
                    +<?= $estadoSmart[
                        'command_timeout_nuevos'
                    ] ?>
                    desde el último control
                <?php else: ?>
                    Sin incrementos recientes
                <?php endif; ?>
            </small>
        </article>
    </div>

    <div class="detalle-smart">
        <div>
            <span>Último autotest</span>

            <strong>
                <?= htmlspecialchars(
                    $estadoSmart['ultimo_autotest'],
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
                        $estadoSmart['antiguedad_segundos']
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

            <?php if ($estadoSmart['actualizado_unix'] > 0): ?>
                <small>
                    <?= date(
                        'd/m/Y H:i:s',
                        $estadoSmart['actualizado_unix']
                    ) ?>
                </small>
            <?php endif; ?>
        </div>
    </div>

    <p class="nota-smart">
        El diagnóstico es generado por systemd cada 30 minutos.
        PHP solo consulta el archivo de estado en modo lectura.
    </p>
</section>
        <section
