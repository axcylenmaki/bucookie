</main>

    <footer style="padding:16px 32px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:.75rem;color:var(--text-muted)">
        <span>&copy; <?= date('Y') ?> Bucookie. All rights reserved.</span>
        <div style="display:flex;gap:16px">
            <a href="<?= BASE_URL ?>pages/about.php" style="color:var(--text-muted);text-decoration:none">Tentang</a>
            <a href="<?= BASE_URL ?>pages/contact.php" style="color:var(--text-muted);text-decoration:none">Kontak</a>
        </div>
    </footer>

</div><!-- END .main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>

<?php if (isset($extra_js)) echo $extra_js; ?>

</body>
</html>