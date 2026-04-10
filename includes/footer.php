        </main><!-- /.flex-grow-1 main -->
    </div><!-- /.flex-grow-1 content column -->
</div><!-- /.d-flex sidebar layout -->

<footer class="admin-footer">
    <div class="footer-inner">
        <small class="text-muted">
            &copy; <?php echo date('Y'); ?> <?php echo defined('APP_NAME') ? APP_NAME : 'Azienda Agricola'; ?> &mdash; Gestionale Agricolo
        </small>
        <small class="text-muted">
            <i class="fas fa-building me-1"></i>P.IVA: 01234567890
        </small>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle mobile
(function() {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-open');
        });
    }
})();
</script>
<script src="/js/main.js"></script>
<script src="/js/giacenze.js"></script>
<?php if (isset($extraJS)): foreach ($extraJS as $js): ?>
    <script src="<?php echo htmlspecialchars($js); ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
