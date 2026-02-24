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
        $('.nav-sidebar .has-treeview > .nav-link').on('click', function(e) {
            if ($(this).attr('href') === '#') {
                e.preventDefault();
            }
            var $parent = $(this).parent();
            if ($parent.hasClass('menu-open')) {
                $parent.removeClass('menu-open menu-is-opening');
                $parent.find('> .nav-treeview').slideUp(200);
            } else {
                $parent.addClass('menu-is-opening menu-open');
                $parent.find('> .nav-treeview').slideDown(200);
            }
        });
    });
    </script>
    <script src="/assets/js/error-handler.js"></script>
    <script src="/assets/js/pre-cadastros.js"></script>
</body>
</html>
