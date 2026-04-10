    </div><!-- /.container -->
</main>

<footer class="border-top py-3 bg-white mt-auto">
    <div class="container d-flex justify-content-between align-items-center">
        <small class="text-muted">
            &copy; <?php echo date('Y'); ?> <?php echo defined('APP_NAME') ? APP_NAME : 'Azienda Agricola'; ?>.
            Tutti i diritti riservati.
        </small>
        <small class="text-muted d-none d-sm-block">
            <i class="fas fa-leaf text-success me-1"></i>Gestionale agricolo
        </small>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/main.js"></script>
<?php if (isset($extraJS)): foreach ($extraJS as $js): ?>
    <script src="<?php echo htmlspecialchars($js); ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
