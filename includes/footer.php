    </div> <!-- .container -->
</main>

<footer class="site-footer">
    <div class="container footer-container">
        <div class="footer-info">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME ?? 'Azienda Agricola'; ?>. Tutti i diritti riservati.</p>
        </div>
        <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Termini e Condizioni</a>
            <a href="#">Contatti</a>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script src="/js/main.js"></script>
<?php if (isset($extraJS)): ?>
    <?php foreach ($extraJS as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
