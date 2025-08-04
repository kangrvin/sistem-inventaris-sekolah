        </div> <!-- Content Wrapper -->
    </div> <!-- Main Content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.0/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.0/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Mobile Navigation
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileToggle');
            const sidebar = document.querySelector('.sidebar');
            const mobileOverlay = document.getElementById('mobileOverlay');
            
            if (mobileToggle && sidebar && mobileOverlay) {
                // Toggle sidebar
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    mobileOverlay.classList.toggle('show');
                });
                
                // Close sidebar when clicking overlay
                mobileOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    mobileOverlay.classList.remove('show');
                });
                
                // Close sidebar when clicking on a link
                const sidebarLinks = sidebar.querySelectorAll('a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        sidebar.classList.remove('show');
                        mobileOverlay.classList.remove('show');
                    });
                });
                
                // Close sidebar on window resize
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('show');
                        mobileOverlay.classList.remove('show');
                    }
                });
            }
        });
        
        // DataTables initialization
        $(document).ready(function() {
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.0/i18n/id.json'
                    },
                    responsive: true,
                    pageLength: 10,
                    order: [[0, 'desc']]
                });
            }
            
            // Auto hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 3000);
            
            // Confirm delete
            $('.btn-delete').click(function(e) {
                if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                    e.preventDefault();
                }
            });
            
            // Image preview
            $('input[type="file"]').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image-preview').attr('src', e.target.result).show();
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html> 