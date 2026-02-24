        </div>
        <!-- /.content-wrapper -->
        
        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                Versão 2.0.0
            </div>
            <strong>Sistema de Controle de Acesso</strong> - Renner Coatings
        </footer>
    </div>
    <!-- ./wrapper -->
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script>
    $(document).ready(function() {
        setTimeout(function() {
            try { $('[data-widget="treeview"]').Treeview('init'); } catch(e) {}
        }, 100);
        
        $(document).on('click', '.nav-sidebar .has-treeview > .nav-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $li = $(this).parent('.has-treeview');
            var $sub = $li.find('> .nav-treeview');
            if ($li.hasClass('menu-open')) {
                $li.removeClass('menu-open menu-is-opening');
                $sub.css('display', 'none');
            } else {
                $li.addClass('menu-is-opening menu-open');
                $sub.css('display', 'block');
            }
            return false;
        });
    });
    </script>
    <script src="/assets/js/error-handler.js"></script>
    <script src="/assets/js/pre-cadastros.js"></script>
</body>
</html>
