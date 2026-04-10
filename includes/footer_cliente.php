    </div><!-- /.container -->
</main><!-- /.cliente-main -->

<footer class="cliente-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <i class="fas fa-leaf text-success me-1"></i>
                <strong><?php echo defined('APP_NAME') ? APP_NAME : 'Azienda Agricola'; ?></strong>
            </div>
            <div class="footer-copy">
                &copy; <?php echo date('Y'); ?> &mdash; Tutti i diritti riservati
            </div>
            <div class="footer-piva">
                <i class="fas fa-building me-1"></i>P.IVA: 01234567890
            </div>
        </div>
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
