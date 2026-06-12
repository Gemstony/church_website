</main>
<footer class="bg-light text-muted py-3 mt-5">
    <div class="container">
        <?php 
        $footerContent = Setting::get('footer_text', '© 2025 Our Church. All rights reserved.');
        // Do NOT escape – allow HTML (admin trusted)
        echo $footerContent;
        ?>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>