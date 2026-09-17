    <main class="contenido">
        <nav class="enlaces navegacion-centros">
            <?php
            $enlacesInternos = [
                'resumen' => [
                    'texto' => 'Resumen',
                    'url' => '/centro-servidor/',
                ],
                'disco' => [
                    'texto' => 'Disco',
                    'url' => '/centro-servidor/disco.php',
                ],
                'servicios' => [
                    'texto' => 'Servicios',
                    'url' => '/centro-servidor/servicios.php',
                ],
                'actividad' => [
                    'texto' => 'Actividad',
                    'url' => '/centro-servidor/actividad.php',
                ],
                'backups' => [
                    'texto' => 'Backups',
                    'url' => '/centro-servidor/backups.php',
                ],
                'red' => [
                    'texto' => 'Red',
                    'url' => '/centro-servidor/red.php',
                ],
            ];

            $hostSolicitud = $_SERVER['HTTP_HOST']
                ?? (getenv('HOMELAB_SERVER_HOST') ?: 'homelab-server.local');

            $hostAcceso = parse_url(
                'http://' . $hostSolicitud,
                PHP_URL_HOST
            );

            if (
                !is_string($hostAcceso)
                || $hostAcceso === ''
            ) {
                $hostAcceso = (getenv('HOMELAB_SERVER_HOST') ?: 'homelab-server.local');
            }

            $hostAccesoUrl = str_contains(
                $hostAcceso,
                ':'
            )
                ? '[' . $hostAcceso . ']'
                : $hostAcceso;

            $hostAccesoSeguro = htmlspecialchars(
                $hostAccesoUrl,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

            <?php foreach (
                $enlacesInternos as $clave => $enlace
            ): ?>
                <a
                    class="enlace-centro<?= (
                        $paginaActiva ?? ''
                    ) === $clave
                        ? ' enlace-centro--activo'
                        : '' ?>"
                    href="<?= htmlspecialchars(
                        $enlace['url'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        $enlace['texto'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </a>
            <?php endforeach; ?>

            <span
                class="separador-navegacion"
                aria-hidden="true"
            ></span>

            <a
                class="enlace-centro"
                href="/centro-camaras/"
            >
                Cámaras
            </a>

            <a
                class="enlace-centro"
                href="http://<?= $hostAccesoSeguro ?>:8081/"
                target="_blank"
                rel="noopener noreferrer"
            >
                Gatus
            </a>

            <a
                class="enlace-centro"
                href="http://<?= $hostAccesoSeguro ?>:8082/"
                target="_blank"
                rel="noopener noreferrer"
            >
                Archivos
            </a>

            <a
                class="enlace-centro"
                href="http://<?= $hostAccesoSeguro ?>:8084/"
                target="_blank"
                rel="noopener noreferrer"
            >
                phpMyAdmin
            </a>
        </nav>
