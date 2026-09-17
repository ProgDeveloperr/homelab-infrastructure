<?php

declare(strict_types=1);

$tituloCompleto = isset($tituloPagina)
    ? $tituloPagina . ' | homelab-server'
    : 'Centro de Desarrollo | homelab-server';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Panel central de proyectos del laboratorio web homelab-server."
    >

    <meta name="color-scheme" content="dark">

    <title><?= escapar($tituloCompleto) ?></title>

    <link
        rel="stylesheet"
        href="recursos/css/estilos.css?v=2"
    >

    <?php if (
        isset($hojaEstilosAdicional)
        && is_string($hojaEstilosAdicional)
        && $hojaEstilosAdicional !== ''
    ): ?>
        <link
            rel="stylesheet"
            href="<?= escapar($hojaEstilosAdicional) ?>"
        >
    <?php endif; ?>
</head>

<body>
<a class="saltar-contenido" href="#titulo-principal">
    Ir al contenido
</a>
