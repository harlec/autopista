        </div>
    </main>
    
    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <script>
        // Función para mostrar notificaciones toast
        function showToast(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            const toast = document.createElement('div');
            toast.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform transition-all duration-300 translate-x-full`;
            toast.innerHTML = `
                <i class="fas ${icons[type]} text-xl"></i>
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.getElementById('toast-container').appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
        
        // Función para confirmar eliminación
        function confirmDelete(message = '¿Estás seguro de eliminar este elemento?') {
            return confirm(message);
        }
        
        // Función para formatear números como moneda
        function formatCurrency(amount) {
            return 'S/ ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
    </script>
</body>
</html>
