<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/seguridad_panel.php';
require_once __DIR__ . '/includes/gestor_proyectos.php';

use CentroDesarrollo\ExcepcionValidacion;
use function CentroDesarrollo\crearProyecto;
use function CentroDesarrollo\guardarMensajePanel;
use function CentroDesarrollo\iniciarSesionPanel;
use function CentroDesarrollo\renovarTokenCsrf;
use function CentroDesarrollo\validarTokenCsrf;

iniciarSesionPanel();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

$plantillaRecibida = (string) ($_POST['plantilla'] ?? '');

$datosFormulario = [
    'nombre' => mb_substr(
        trim((string) ($_POST['nombre'] ?? '')),
        0,
        60
    ),
    'descripcion' => mb_substr(
        trim((string) ($_POST['descripcion'] ?? '')),
        0,
        240
    ),
    'plantilla' => in_array(
        $plantillaRecibida,
        ['php', 'html', 'vacio'],
        true
    )
        ? $plantillaRecibida
        : 'php',
];

$token = isset($_POST['token_csrf'])
    ? (string) $_POST['token_csrf']
    : null;

if (!validarTokenCsrf($token)) {
    renovarTokenCsrf();

    guardarMensajePanel(
        'error',
        'La solicitud venció o no es válida. Volvé a intentarlo.'
    );

    header('Location: index.php#proyectos', true, 303);
    exit;
}

try {
    $proyecto = crearProyecto($_POST);

    renovarTokenCsrf();

    $mensaje = 'El proyecto “'
        . $proyecto['nombre']
        . '” fue creado correctamente.';

    if (!$proyecto['registro_correcto']) {
        $mensaje .= ' No se pudo guardar el registro de auditoría.';
    }

    guardarMensajePanel(
        'exito',
        $mensaje,
        [
            'slug' => $proyecto['slug'],
            'url' => $proyecto['url'],
        ]
    );
} catch (ExcepcionValidacion $error) {
    guardarMensajePanel(
        'error',
        $error->getMessage(),
        [
            'formulario' => $datosFormulario,
        ]
    );
} catch (Throwable $error) {
    error_log(
        'Centro de Desarrollo: error al crear proyecto: '
        . $error->getMessage()
    );

    guardarMensajePanel(
        'error',
        'No fue posible crear el proyecto. No quedó ningún cambio incompleto.',
        [
            'formulario' => $datosFormulario,
        ]
    );
}

header('Location: index.php#proyectos', true, 303);
exit;
