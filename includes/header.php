<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistema de Gestión de Facturas'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --color-primary: #72BF44;
            --color-primary-dark: #2BB458;
            --color-secondary: #FFDD00;
            --color-accent: #F99B1C;
            --color-info: #00BBE7;
            --color-dark: #6F605A;
        }
        
        .bg-primary { background-color: var(--color-primary); }
        .bg-primary-dark { background-color: var(--color-primary-dark); }
        .bg-secondary { background-color: var(--color-secondary); }
        .bg-accent { background-color: var(--color-accent); }
        .bg-info { background-color: var(--color-info); }
        
        .text-primary { color: var(--color-primary); }
        .text-primary-dark { color: var(--color-primary-dark); }
        .text-secondary { color: var(--color-secondary); }
        .text-accent { color: var(--color-accent); }
        .text-info { color: var(--color-info); }
        
        .border-primary { border-color: var(--color-primary); }
        
        .hover\:bg-primary-dark:hover { background-color: var(--color-primary-dark); }
        
        .btn-primary {
            background-color: var(--color-primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--color-primary-dark);
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #4B5563;
            text-decoration: none;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .sidebar-link:hover {
            background-color: #F3F4F6;
            color: var(--color-primary);
        }
        
        .sidebar-link.active {
            background-color: #72BF44;
            color: white;
        }
        
        .sidebar-link i {
            width: 1.5rem;
            margin-right: 0.75rem;
        }
        
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            border-left: 4px solid var(--color-primary);
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#72BF44',
                        'primary-dark': '#2BB458',
                        secondary: '#FFDD00',
                        accent: '#F99B1C',
                        info: '#00BBE7',
                        dark: '#6F605A'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    
    <!-- Sidebar -->
    <aside class="fixed top-0 left-0 w-64 h-full bg-white shadow-lg z-50">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-invoice-dollar text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Sistema TI</h1>
                    <p class="text-xs text-gray-500">Gestión de Facturas</p>
                </div>
            </div>
        </div>
        
        <nav class="p-4 space-y-1">
            <a href="/index.php" class="sidebar-link <?php echo ($current_page ?? '') == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="/modules/facturas/lista.php" class="sidebar-link <?php echo ($current_page ?? '') == 'facturas' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Facturas</span>
            </a>
            
            <a href="/modules/facturas/nueva.php" class="sidebar-link <?php echo ($current_page ?? '') == 'nueva-factura' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Nueva Factura</span>
            </a>
            
            <a href="/modules/proveedores/lista.php" class="sidebar-link <?php echo ($current_page ?? '') == 'proveedores' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>Proveedores</span>
            </a>
			<a href="/modules/proveedores/plantillas.php" class="sidebar-link">
               <i class="fas fa-magic"></i>
               <span>Plantillas PDF</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-gray-200">
                <a href="#" class="sidebar-link">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
                
                <a href="#" class="sidebar-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
            </div>
        </nav>
        
        <div class="absolute bottom-0 w-full p-4 border-t border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-gray-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">Área TI</p>
                    <p class="text-xs text-gray-500">Administrador</p>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="ml-64 min-h-screen">
        <div class="p-8">
