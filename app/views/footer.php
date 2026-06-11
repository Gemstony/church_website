</main>
<footer class="bg-light text-center text-muted py-3 mt-5">
    <div class="container">
        <?php 
        $footerText = Setting::get('footer_text', '© 2025 Our Church. All rights reserved.');
        echo Security::escape($footerText);
        ?>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>