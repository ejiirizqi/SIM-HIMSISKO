</main>
<script>
(function () {
    var t = document.getElementById('publik-nav-toggle');
    var d = document.getElementById('publik-mobile-drawer');
    if (!t || !d) return;
    t.addEventListener('click', function () {
        var open = d.classList.toggle('hidden') === false;
        t.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    d.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            d.classList.add('hidden');
            t.setAttribute('aria-expanded', 'false');
        });
    });
})();
</script>
<footer class="mt-auto border-t bg-white">
    <div class="max-w-6xl mx-auto px-4 py-6 text-center text-xs text-slate-500">
        SIM HIMSISKO — Manajemen Kegiatan, Dokumentasi, dan Transparansi Keuangan
    </div>
</footer>
</body>
</html>
