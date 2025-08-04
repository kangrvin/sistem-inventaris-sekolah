        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.0/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.0/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Mobile navigation toggle
        document.getElementById('mobileToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.getElementById('mobileOverlay').classList.toggle('show');
        });
        
        document.getElementById('mobileOverlay').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            this.classList.remove('show');
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // DataTable initialization
        $(document).ready(function() {
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    responsive: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.0/i18n/id.json'
                    }
                });
            }
        });
        
        // Image preview for file inputs
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).src = e.target.result;
                    document.getElementById(previewId).style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Confirm delete
        function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
            return confirm(message);
        }
        
        // Auto-submit form on select change
        function autoSubmitForm(formId) {
            document.getElementById(formId).submit();
        }
        
        // Show loading spinner
        function showLoading() {
            const spinner = document.createElement('div');
            spinner.className = 'position-fixed top-50 start-50 translate-middle';
            spinner.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            `;
            spinner.style.zIndex = '9999';
            document.body.appendChild(spinner);
        }
        
        // Hide loading spinner
        function hideLoading() {
            const spinner = document.querySelector('.position-fixed');
            if (spinner) {
                spinner.remove();
            }
        }
    </script>
</body>
</html> 