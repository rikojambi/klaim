<?php
/**
 * footer.php
 * Menutup tag <main>, <div.main-wrapper>, dan <body> serta menambah script JS ringan.
 */
?>
    </main><!-- /.content-area -->

    <footer class="app-footer">
        <p>&copy; <?= date('Y') ?> Sistem Perekaman Klaim BPJS Ketenagakerjaan &mdash; Proyek Referensi Kelas 12 RPL</p>
    </footer>

</div><!-- /.main-wrapper -->

<!-- ===================== JAVASCRIPT RINGAN (INLINE) ===================== -->
<script>
(function () {
    // Toggle sidebar (mobile & desktop)
    var toggle  = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
            document.body.classList.toggle('sidebar-active');
        });
    }

    // Auto-hide alert/notifikasi setelah 4 detik
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 4000);
    });

    // Konfirmasi hapus
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var msg = this.getAttribute('data-confirm') || 'Yakin ingin menghapus data ini?';
            if (!confirm(msg)) e.preventDefault();
        });
    });
})();
</script>

</body>
</html>
