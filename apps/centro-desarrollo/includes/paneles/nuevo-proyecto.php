<?php

declare(strict_types=1);

$nombreFormulario = (string) ($datosFormulario['nombre'] ?? '');
$descripcionFormulario = (string) ($datosFormulario['descripcion'] ?? '');
$plantillaFormulario = (string) ($datosFormulario['plantilla'] ?? 'php');

if (!in_array($plantillaFormulario, ['php', 'html', 'vacio'], true)) {
    $plantillaFormulario = 'php';
}
?>

<dialog
    class="modal modal-proyecto"
    id="modal-nuevo-proyecto"
    aria-labelledby="titulo-nuevo-proyecto"
>
    <form
        class="formulario-proyecto"
        id="formulario-nuevo-proyecto"
        action="crear-proyecto.php"
        method="post"
    >
        <input
            type="hidden"
            name="token_csrf"
            value="<?= escapar($tokenCsrf) ?>"
        >

        <div class="modal-cabecera">
            <div>
                <span class="seccion-etiqueta">Nuevo entorno</span>
                <h2 id="titulo-nuevo-proyecto">Crear proyecto</h2>
                <p>
                    Prepará una estructura inicial segura dentro del servidor.
                </p>
            </div>

            <button
                class="modal-cerrar"
                id="cerrar-modal-proyecto"
                type="button"
                aria-label="Cerrar formulario"
            >
                ×
            </button>
        </div>

        <div class="formulario-cuerpo">
            <label class="campo-formulario" for="nombre-proyecto">
                <span class="campo-etiqueta">
                    <strong>Nombre del proyecto</strong>
                    <small>
                        <span id="contador-nombre">0</span>/60
                    </small>
                </span>

                <input
                    id="nombre-proyecto"
                    name="nombre"
                    type="text"
                    value="<?= escapar($nombreFormulario) ?>"
                    minlength="3"
                    maxlength="60"
                    placeholder="Ejemplo: Panel de inventario"
                    autocomplete="off"
                    spellcheck="false"
                    required
                >

                <small>
                    Se convertirá en una carpeta segura:
                    <code id="vista-slug">nuevo-proyecto</code>
                </small>
            </label>

            <label class="campo-formulario" for="descripcion-proyecto">
                <span class="campo-etiqueta">
                    <strong>Descripción</strong>
                    <small>
                        <span id="contador-descripcion">0</span>/240
                    </small>
                </span>

                <textarea
                    id="descripcion-proyecto"
                    name="descripcion"
                    maxlength="240"
                    rows="4"
                    placeholder="¿Qué función tendrá este proyecto?"
                ><?= escapar($descripcionFormulario) ?></textarea>
            </label>

            <fieldset class="selector-plantillas">
                <legend>Plantilla inicial</legend>

                <div class="cuadricula-plantillas">
                    <label class="tarjeta-plantilla">
                        <input
                            type="radio"
                            name="plantilla"
                            value="php"
                            <?= $plantillaFormulario === 'php'
                                ? 'checked'
                                : '' ?>
                            required
                        >

                        <span class="plantilla-icono">&lt;?&gt;</span>
                        <strong>PHP</strong>
                        <small>
                            PHP, HTML, CSS y JavaScript organizados.
                        </small>
                        <span class="plantilla-marca">Recomendada</span>
                    </label>

                    <label class="tarjeta-plantilla">
                        <input
                            type="radio"
                            name="plantilla"
                            value="html"
                            <?= $plantillaFormulario === 'html'
                                ? 'checked'
                                : '' ?>
                        >

                        <span class="plantilla-icono">HTML</span>
                        <strong>Frontend</strong>
                        <small>
                            HTML, CSS y JavaScript sin backend.
                        </small>
                        <span class="plantilla-marca">Estática</span>
                    </label>

                    <label class="tarjeta-plantilla">
                        <input
                            type="radio"
                            name="plantilla"
                            value="vacio"
                            <?= $plantillaFormulario === 'vacio'
                                ? 'checked'
                                : '' ?>
                        >

                        <span class="plantilla-icono">＋</span>
                        <strong>Proyecto vacío</strong>
                        <small>
                            Solo la carpeta y sus metadatos internos.
                        </small>
                        <span class="plantilla-marca">Avanzada</span>
                    </label>
                </div>
            </fieldset>

            <aside class="aviso-seguridad">
                <span aria-hidden="true">◆</span>

                <p>
                    <strong>Creación protegida.</strong>
                    El nombre, la ruta, la plantilla y el token de seguridad
                    se validan nuevamente en el servidor.
                </p>
            </aside>
        </div>

        <div class="formulario-acciones">
            <button
                class="boton boton-secundario"
                id="cancelar-proyecto"
                type="button"
            >
                Cancelar
            </button>

            <button
                class="boton boton-primario boton-crear"
                id="confirmar-proyecto"
                type="submit"
            >
                Crear proyecto
                <span aria-hidden="true">→</span>
            </button>
        </div>
    </form>
</dialog>
