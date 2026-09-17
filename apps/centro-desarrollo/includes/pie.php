<?php

declare(strict_types=1);
?>

<footer class="pie-principal">
    <div class="pie-contenido">
        <div>
            <strong>Centro de Desarrollo</strong>
            <span>Laboratorio web del servidor homelab-server</span>
        </div>

        <span>
            <?= date('Y') ?> · Administración segura
        </span>
    </div>
</footer>

<?php if (!isset($cargarAppPrincipal) || $cargarAppPrincipal): ?>
    <script src="recursos/js/app.js?v=2"></script>
<?php endif; ?>
</body>
</html>
