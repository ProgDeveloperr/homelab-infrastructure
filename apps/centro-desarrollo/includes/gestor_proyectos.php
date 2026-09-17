<?php

declare(strict_types=1);

namespace CentroDesarrollo;

if (!defined('RUTA_PROYECTOS')) {
    define(
        'RUTA_PROYECTOS',
        getenv('HOMELAB_PROJECTS_PATH') ?: '/var/www/html'
    );
}
const RUTA_DATOS_PANEL = __DIR__ . '/../datos';

final class ExcepcionValidacion extends \RuntimeException
{
}

function longitudTexto(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }

    $resultado = preg_match_all('/./us', $texto, $coincidencias);

    return $resultado === false
        ? strlen($texto)
        : count($coincidencias[0]);
}

function generarSlugProyecto(string $nombre): string
{
    $reemplazos = [
        'Á' => 'A',
        'É' => 'E',
        'Í' => 'I',
        'Ó' => 'O',
        'Ú' => 'U',
        'Ü' => 'U',
        'Ñ' => 'N',
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
    ];

    $slug = strtr(trim($nombre), $reemplazos);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug;
}

function prepararDatosProyecto(array $entrada): array
{
    $nombre = trim((string) ($entrada['nombre'] ?? ''));
    $descripcion = trim((string) ($entrada['descripcion'] ?? ''));
    $plantilla = trim((string) ($entrada['plantilla'] ?? ''));

    $longitudNombre = longitudTexto($nombre);

    if ($longitudNombre < 3 || $longitudNombre > 60) {
        throw new ExcepcionValidacion(
            'El nombre debe tener entre 3 y 60 caracteres.'
        );
    }

    if (
        preg_match(
            '/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]'
            . '[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 _-]*'
            . '[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]$/u',
            $nombre
        ) !== 1
    ) {
        throw new ExcepcionValidacion(
            'El nombre solo puede contener letras, números, espacios, guiones y guiones bajos.'
        );
    }

    if (longitudTexto($descripcion) > 240) {
        throw new ExcepcionValidacion(
            'La descripción no puede superar los 240 caracteres.'
        );
    }

    if (
        preg_match(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
            $descripcion
        ) === 1
    ) {
        throw new ExcepcionValidacion(
            'La descripción contiene caracteres no permitidos.'
        );
    }

    $plantillasPermitidas = ['php', 'html', 'vacio'];

    if (!in_array($plantilla, $plantillasPermitidas, true)) {
        throw new ExcepcionValidacion(
            'Seleccioná una plantilla válida.'
        );
    }

    $slug = generarSlugProyecto($nombre);

    if (
        strlen($slug) < 3
        || strlen($slug) > 60
        || preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])$/', $slug) !== 1
    ) {
        throw new ExcepcionValidacion(
            'No fue posible generar un nombre de carpeta válido.'
        );
    }

    $reservados = [
        'centro-desarrollo',
        'cgi-bin',
        'icons',
        'server-status',
    ];

    if (in_array($slug, $reservados, true)) {
        throw new ExcepcionValidacion(
            'Ese nombre está reservado por el sistema.'
        );
    }

    return [
        'nombre' => $nombre,
        'slug' => $slug,
        'descripcion' => $descripcion,
        'plantilla' => $plantilla,
    ];
}

function existeProyecto(
    string $rutaProyectos,
    string $slug
): bool {
    $elementos = scandir($rutaProyectos);

    if ($elementos === false) {
        throw new \RuntimeException(
            'No fue posible inspeccionar los proyectos.'
        );
    }

    foreach ($elementos as $elemento) {
        if (
            $elemento !== '.'
            && $elemento !== '..'
            && strcasecmp($elemento, $slug) === 0
        ) {
            return true;
        }
    }

    return false;
}

function contenidoBaseHtml(
    string $nombre,
    string $descripcion
): string {
    $nombreSeguro = htmlspecialchars(
        $nombre,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $descripcionSegura = htmlspecialchars(
        $descripcion !== ''
            ? $descripcion
            : 'Proyecto en construcción.',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $plantilla = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title>{{NOMBRE}}</title>
    <link rel="stylesheet" href="recursos/css/estilos.css">
</head>
<body>
    <main class="portada">
        <span class="etiqueta">Proyecto homelab-server</span>

        <h1>{{NOMBRE}}</h1>

        <p>{{DESCRIPCION}}</p>

        <div class="estado">
            <span></span>
            Entorno inicial preparado
        </div>
    </main>

    <script src="recursos/js/app.js"></script>
</body>
</html>
HTML;

    return str_replace(
        ['{{NOMBRE}}', '{{DESCRIPCION}}'],
        [$nombreSeguro, $descripcionSegura],
        $plantilla
    );
}

function contenidoBasePhp(
    string $nombre,
    string $descripcion
): string {
    $html = contenidoBaseHtml($nombre, $descripcion);

    return "<?php\n\n"
        . "declare(strict_types=1);\n"
        . "?>\n"
        . $html;
}

function contenidoCssInicial(): string
{
    return <<<'CSS'
:root {
    color-scheme: dark;
    --fondo: #07101f;
    --superficie: #101e33;
    --borde: rgba(148, 163, 184, 0.18);
    --texto: #eef6ff;
    --texto-suave: #9dafc7;
    --azul: #38bdf8;
    --violeta: #a78bfa;
    --verde: #34d399;
}

* {
    box-sizing: border-box;
}

body {
    min-width: 320px;
    min-height: 100vh;
    margin: 0;
    display: grid;
    place-items: center;
    padding: 24px;
    color: var(--texto);
    background:
        radial-gradient(
            circle at 15% 0%,
            rgba(14, 165, 233, 0.16),
            transparent 28rem
        ),
        radial-gradient(
            circle at 95% 20%,
            rgba(167, 139, 250, 0.13),
            transparent 30rem
        ),
        var(--fondo);
    font-family:
        Inter,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

.portada {
    width: min(100%, 760px);
    padding: clamp(32px, 7vw, 70px);
    border: 1px solid var(--borde);
    border-radius: 24px;
    background: rgba(16, 30, 51, 0.82);
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.28);
}

.etiqueta {
    color: var(--azul);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

h1 {
    margin: 18px 0 0;
    font-size: clamp(42px, 9vw, 74px);
    line-height: 1;
    letter-spacing: -0.055em;
}

p {
    max-width: 600px;
    margin: 24px 0;
    color: var(--texto-suave);
    font-size: 17px;
    line-height: 1.7;
}

.estado {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--texto-suave);
    font-size: 13px;
    font-weight: 700;
}

.estado span {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--verde);
    box-shadow: 0 0 0 5px rgba(52, 211, 153, 0.1);
}
CSS;
}

function contenidoJavascriptInicial(): string
{
    return <<<'JS'
'use strict';

console.info('Proyecto cargado correctamente.');
JS;
}

function obtenerArchivosPlantilla(
    string $plantilla,
    string $nombre,
    string $descripcion
): array {
    $archivosComunes = [
        '.centro-desarrollo.json' => '',
    ];

    if ($plantilla === 'vacio') {
        return $archivosComunes;
    }

    $archivos = [
        'recursos/css/estilos.css' => contenidoCssInicial(),
        'recursos/js/app.js' => contenidoJavascriptInicial(),
    ];

    if ($plantilla === 'php') {
        $archivos['index.php'] = contenidoBasePhp(
            $nombre,
            $descripcion
        );
    }

    if ($plantilla === 'html') {
        $archivos['index.html'] = contenidoBaseHtml(
            $nombre,
            $descripcion
        );
    }

    return array_merge($archivosComunes, $archivos);
}

function escribirArchivoSeguro(
    string $rutaBase,
    string $rutaRelativa,
    string $contenido
): void {
    if (
        str_contains($rutaRelativa, '..')
        || str_starts_with($rutaRelativa, '/')
        || str_contains($rutaRelativa, '\\')
    ) {
        throw new \RuntimeException(
            'La plantilla contiene una ruta no permitida.'
        );
    }

    $rutaCompleta = $rutaBase . '/' . $rutaRelativa;
    $directorio = dirname($rutaCompleta);

    if (
        !is_dir($directorio)
        && !mkdir($directorio, 0770, true)
        && !is_dir($directorio)
    ) {
        throw new \RuntimeException(
            'No fue posible crear la estructura del proyecto.'
        );
    }

    $bytes = file_put_contents(
        $rutaCompleta,
        $contenido,
        LOCK_EX
    );

    if ($bytes === false) {
        throw new \RuntimeException(
            'No fue posible escribir un archivo del proyecto.'
        );
    }

    chmod($rutaCompleta, 0660);
}

function eliminarRutaTemporal(string $ruta): void
{
    if (is_link($ruta) || is_file($ruta)) {
        @unlink($ruta);
        return;
    }

    if (!is_dir($ruta)) {
        return;
    }

    $elementos = new \FilesystemIterator(
        $ruta,
        \FilesystemIterator::SKIP_DOTS
    );

    foreach ($elementos as $elemento) {
        eliminarRutaTemporal($elemento->getPathname());
    }

    @rmdir($ruta);
}

function registrarOperacion(array $datos): bool
{
    $rutaDatos = realpath(RUTA_DATOS_PANEL);

    if ($rutaDatos === false || !is_dir($rutaDatos)) {
        return false;
    }

    $registro = array_merge(
        [
            'fecha' => date(DATE_ATOM),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        ],
        $datos
    );

    try {
        $linea = json_encode(
            $registro,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        ) . PHP_EOL;
    } catch (\JsonException) {
        return false;
    }

    $archivo = @fopen(
        $rutaDatos . '/operaciones.php',
        'c+b'
    );

    if ($archivo === false) {
        return false;
    }

    $correcto = false;

    if (flock($archivo, LOCK_EX)) {
        $correcto = fseek($archivo, 0, SEEK_END) === 0;
        $posicionFinal = $correcto ? ftell($archivo) : false;
        $correcto = $correcto && $posicionFinal !== false;

        if ($correcto && $posicionFinal === 0) {
            $cabecera = "<?php\n\n"
                . "http_response_code(404);\n"
                . "exit;\n\n"
                . "__halt_compiler();\n";

            $bytesCabecera = fwrite($archivo, $cabecera);
            $correcto = $bytesCabecera === strlen($cabecera);
        }

        if ($correcto) {
            $bytesLinea = fwrite($archivo, $linea);
            $correcto = $bytesLinea === strlen($linea);
        }

        if ($correcto) {
            $correcto = fflush($archivo);
        }

        flock($archivo, LOCK_UN);
    }

    fclose($archivo);

    return $correcto;
}

function crearProyecto(array $entrada): array
{
    $datos = prepararDatosProyecto($entrada);
    $rutaProyectos = realpath(RUTA_PROYECTOS);

    if (
        $rutaProyectos === false
        || !is_dir($rutaProyectos)
        || !is_writable($rutaProyectos)
    ) {
        throw new \RuntimeException(
            'La carpeta de proyectos no está disponible para escritura.'
        );
    }

    $rutaDatos = realpath(RUTA_DATOS_PANEL);

    if (
        $rutaDatos === false
        || !is_dir($rutaDatos)
        || !is_writable($rutaDatos)
    ) {
        throw new \RuntimeException(
            'La carpeta de datos del panel no está disponible.'
        );
    }

    $archivoBloqueo = fopen(
        $rutaDatos . '/creacion-proyectos.lock',
        'c+'
    );

    if ($archivoBloqueo === false) {
        throw new \RuntimeException(
            'No fue posible iniciar el control de concurrencia.'
        );
    }

    if (!flock($archivoBloqueo, LOCK_EX)) {
        fclose($archivoBloqueo);

        throw new \RuntimeException(
            'No fue posible bloquear temporalmente la creación.'
        );
    }

    $rutaTemporal = null;
    $umaskAnterior = umask(0002);

    try {
        if (existeProyecto($rutaProyectos, $datos['slug'])) {
            throw new ExcepcionValidacion(
                'Ya existe un proyecto con ese nombre.'
            );
        }

        $rutaFinal = $rutaProyectos . '/' . $datos['slug'];

        if (
            dirname($rutaFinal) !== $rutaProyectos
            || file_exists($rutaFinal)
            || is_link($rutaFinal)
        ) {
            throw new ExcepcionValidacion(
                'El destino del proyecto no está disponible.'
            );
        }

        $rutaTemporal = $rutaProyectos
            . '/.creando-'
            . $datos['slug']
            . '-'
            . bin2hex(random_bytes(6));

        if (!mkdir($rutaTemporal, 0770)) {
            throw new \RuntimeException(
                'No fue posible crear el directorio temporal.'
            );
        }

        $archivos = obtenerArchivosPlantilla(
            $datos['plantilla'],
            $datos['nombre'],
            $datos['descripcion']
        );

        $metadatos = [
            'version' => 1,
            'nombre' => $datos['nombre'],
            'slug' => $datos['slug'],
            'descripcion' => $datos['descripcion'],
            'plantilla' => $datos['plantilla'],
            'creado_en' => date(DATE_ATOM),
            'creado_por' => 'Centro de Desarrollo',
        ];

        $archivos['.centro-desarrollo.json'] = json_encode(
            $metadatos,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        foreach ($archivos as $rutaRelativa => $contenido) {
            escribirArchivoSeguro(
                $rutaTemporal,
                $rutaRelativa,
                $contenido
            );
        }

        chmod($rutaTemporal, 0770);

        if (!rename($rutaTemporal, $rutaFinal)) {
            throw new \RuntimeException(
                'No fue posible completar la creación atómica.'
            );
        }

        $rutaTemporal = null;

        $registroCorrecto = registrarOperacion([
            'accion' => 'crear_proyecto',
            'resultado' => 'correcto',
            'proyecto' => $datos['slug'],
            'nombre' => $datos['nombre'],
            'plantilla' => $datos['plantilla'],
        ]);

        return [
            'nombre' => $datos['nombre'],
            'slug' => $datos['slug'],
            'url' => '/' . rawurlencode($datos['slug']) . '/',
            'registro_correcto' => $registroCorrecto,
        ];
    } catch (\Throwable $error) {
        if (is_string($rutaTemporal)) {
            eliminarRutaTemporal($rutaTemporal);
        }

        registrarOperacion([
            'accion' => 'crear_proyecto',
            'resultado' => 'error',
            'proyecto' => $datos['slug'],
            'plantilla' => $datos['plantilla'],
            'detalle' => $error->getMessage(),
        ]);

        throw $error;
    } finally {
        umask($umaskAnterior);
        flock($archivoBloqueo, LOCK_UN);
        fclose($archivoBloqueo);
    }
}
