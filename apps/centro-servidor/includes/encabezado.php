<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars(
            $tituloPagina ?? 'Centro de Control del Servidor',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

    <link
        rel="stylesheet"
        href="recursos/css/estilos.css"
    >
</head>

<body>
    <header class="encabezado">
        <div>
            <p class="etiqueta">
                Servidor ARK-3360
            </p>

            <h1>
                <?= htmlspecialchars(
                    $tituloPagina
                        ?? 'Centro de Control del Servidor',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <p class="subtitulo">
                <?= htmlspecialchars(
                    $subtituloPagina
                        ?? 'Hardware, recursos, servicios y mantenimiento.',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        </div>

        <div class="estado-servidor">
            <span class="indicador"></span>
            Sistema disponible
        </div>
    </header>
