<?php

declare(strict_types=1);

function obtenerProyectos(string $rutaProyectos): array
{
    $rutaBase = realpath($rutaProyectos);

    if ($rutaBase === false || !is_dir($rutaBase)) {
        return [];
    }

    $nombres = scandir($rutaBase);

    if ($nombres === false) {
        return [];
    }

    $proyectos = [];

    foreach ($nombres as $nombre) {
        if (
            $nombre === '.'
            || $nombre === '..'
            || str_starts_with($nombre, '.')
        ) {
            continue;
        }

        $rutaProyecto = realpath($rutaBase . DIRECTORY_SEPARATOR . $nombre);

        if (
            $rutaProyecto === false
            || !is_dir($rutaProyecto)
            || dirname($rutaProyecto) !== $rutaBase
        ) {
            continue;
        }

        $proyectos[] = analizarProyecto($nombre, $rutaProyecto);
    }

    usort(
        $proyectos,
        static function (array $primero, array $segundo): int {
            if ($primero['nombre_carpeta'] === 'centro-desarrollo') {
                return -1;
            }

            if ($segundo['nombre_carpeta'] === 'centro-desarrollo') {
                return 1;
            }

            return strnatcasecmp(
                $primero['nombre_visible'],
                $segundo['nombre_visible']
            );
        }
    );

    return $proyectos;
}

function analizarProyecto(string $nombre, string $ruta): array
{
    $estadisticas = obtenerEstadisticasProyecto($ruta);
    $tecnologias = detectarTecnologias(
        $ruta,
        $estadisticas['extensiones'],
        $estadisticas['archivos_especiales']
    );

    $archivoEntrada = null;

    foreach (['index.php', 'index.html', 'index.htm'] as $candidato) {
        if (is_file($ruta . DIRECTORY_SEPARATOR . $candidato)) {
            $archivoEntrada = $candidato;
            break;
        }
    }

    return [
        'nombre_carpeta' => $nombre,
        'nombre_visible' => obtenerNombreVisible($nombre),
        'descripcion' => obtenerDescripcionProyecto($nombre),
        'ruta' => $ruta,
        'url' => '/' . rawurlencode($nombre) . '/',
        'disponible' => $archivoEntrada !== null,
        'archivo_entrada' => $archivoEntrada,
        'cantidad_archivos' => $estadisticas['cantidad_archivos'],
        'tamano_bytes' => $estadisticas['tamano_bytes'],
        'ultima_modificacion' => $estadisticas['ultima_modificacion'],
        'tecnologias' => $tecnologias,
        'usa_base_datos' => detectarBaseDatos(
            $estadisticas['extensiones'],
            $estadisticas['archivos_especiales']
        ),
        'git' => obtenerEstadoGit($ruta),
    ];
}

function obtenerEstadisticasProyecto(string $ruta): array
{
    $cantidadArchivos = 0;
    $tamanoBytes = 0;
    $ultimaModificacion = (int) (filemtime($ruta) ?: 0);
    $extensiones = [];
    $archivosEspeciales = [];

    try {
        $directorio = new RecursiveDirectoryIterator(
            $ruta,
            FilesystemIterator::SKIP_DOTS
        );

        $filtrado = new RecursiveCallbackFilterIterator(
            $directorio,
            static function (SplFileInfo $elemento): bool {
                if (!$elemento->isDir()) {
                    return true;
                }

                return !in_array(
                    $elemento->getFilename(),
                    ['.git', 'node_modules', 'vendor'],
                    true
                );
            }
        );

        $iterador = new RecursiveIteratorIterator(
            $filtrado,
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterador as $archivo) {
            if (!$archivo->isFile() || $archivo->isLink()) {
                continue;
            }

            $cantidadArchivos++;
            $tamanoBytes += max(0, (int) $archivo->getSize());
            $ultimaModificacion = max(
                $ultimaModificacion,
                (int) $archivo->getMTime()
            );

            $extension = strtolower($archivo->getExtension());

            if ($extension !== '') {
                $extensiones[$extension] = true;
            }

            $archivosEspeciales[
                strtolower($archivo->getFilename())
            ] = true;
        }
    } catch (UnexpectedValueException) {
        // El proyecto seguirá apareciendo aunque alguna ruta no sea legible.
    }

    return [
        'cantidad_archivos' => $cantidadArchivos,
        'tamano_bytes' => $tamanoBytes,
        'ultima_modificacion' => $ultimaModificacion,
        'extensiones' => array_keys($extensiones),
        'archivos_especiales' => array_keys($archivosEspeciales),
    ];
}

function detectarTecnologias(
    string $ruta,
    array $extensiones,
    array $archivosEspeciales
): array {
    $tecnologias = [];

    $reglasExtensiones = [
        'php' => 'PHP',
        'html' => 'HTML',
        'htm' => 'HTML',
        'css' => 'CSS',
        'js' => 'JavaScript',
        'mjs' => 'JavaScript',
        'sql' => 'SQL',
        'py' => 'Python',
    ];

    foreach ($reglasExtensiones as $extension => $tecnologia) {
        if (in_array($extension, $extensiones, true)) {
            $tecnologias[$tecnologia] = true;
        }
    }

    $reglasArchivos = [
        'composer.json' => 'Composer',
        'package.json' => 'Node.js',
        'dockerfile' => 'Docker',
        'compose.yml' => 'Docker',
        'compose.yaml' => 'Docker',
        'docker-compose.yml' => 'Docker',
        'docker-compose.yaml' => 'Docker',
    ];

    foreach ($reglasArchivos as $archivo => $tecnologia) {
        if (in_array($archivo, $archivosEspeciales, true)) {
            $tecnologias[$tecnologia] = true;
        }
    }

    if (is_dir($ruta . '/.git')) {
        $tecnologias['Git'] = true;
    }

    return array_keys($tecnologias);
}

function detectarBaseDatos(
    array $extensiones,
    array $archivosEspeciales
): bool {
    if (in_array('sql', $extensiones, true)) {
        return true;
    }

    foreach ($archivosEspeciales as $archivo) {
        if (
            str_contains($archivo, 'conexion_bd')
            || str_contains($archivo, 'database')
        ) {
            return true;
        }
    }

    return false;
}

function obtenerEstadoGit(string $ruta): array
{
    $rutaGit = $ruta . '/.git';
    $archivoHead = $rutaGit . '/HEAD';

    if (!is_dir($rutaGit) || !is_file($archivoHead)) {
        return [
            'activo' => false,
            'rama' => null,
            'commit' => null,
        ];
    }

    $contenidoHead = trim((string) file_get_contents($archivoHead));
    $rama = null;
    $commit = null;

    if (str_starts_with($contenidoHead, 'ref: ')) {
        $referencia = substr($contenidoHead, 5);
        $rama = basename($referencia);
        $archivoReferencia = $rutaGit . '/' . $referencia;

        if (is_file($archivoReferencia)) {
            $commit = substr(
                trim((string) file_get_contents($archivoReferencia)),
                0,
                7
            );
        } else {
            $archivoPackedRefs = $rutaGit . '/packed-refs';

            if (is_file($archivoPackedRefs)) {
                foreach (file($archivoPackedRefs) ?: [] as $linea) {
                    $linea = trim($linea);

                    if (
                        $linea === ''
                        || str_starts_with($linea, '#')
                        || str_starts_with($linea, '^')
                    ) {
                        continue;
                    }

                    [$hash, $ref] = array_pad(
                        preg_split('/\s+/', $linea, 2) ?: [],
                        2,
                        null
                    );

                    if ($ref === $referencia && is_string($hash)) {
                        $commit = substr($hash, 0, 7);
                        break;
                    }
                }
            }
        }
    } elseif (preg_match('/^[a-f0-9]{40}$/i', $contenidoHead)) {
        $commit = substr($contenidoHead, 0, 7);
    }

    return [
        'activo' => true,
        'rama' => $rama,
        'commit' => $commit,
    ];
}

function obtenerNombreVisible(string $nombre): string
{
    $nombre = str_replace(['-', '_'], ' ', $nombre);

    return mb_convert_case(
        $nombre,
        MB_CASE_TITLE,
        'UTF-8'
    );
}

function obtenerDescripcionProyecto(string $nombre): string
{
    $descripciones = [
        'centro-desarrollo' =>
            'Administración central de proyectos y herramientas de desarrollo.',
        'centro-camaras' =>
            'Control, biblioteca y mantenimiento del sistema de cámaras.',
        'centro-servidor' =>
            'Estado, servicios, red y mantenimiento del servidor.',
        'Landingpage-Estructurado' =>
            'Landing page estructurada con contenido multimedia.',
        'prueba-php' =>
            'Entorno simple utilizado para pruebas de PHP.',
    ];

    return $descripciones[$nombre]
        ?? 'Proyecto alojado en el laboratorio web del servidor.';
}
