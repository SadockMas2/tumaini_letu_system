<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Système de Crédit</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .sidebar:hover {
            width: 16rem;
        }
        .sidebar {
            transition: width 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-hand-holding-usd text-blue-600 text-2xl mr-3"></i>
                    <span class="font-bold text-xl text-gray-800">CreditPro</span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Bienvenue, Admin</span>
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('credits.dashboard') }}" 
                           class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-600">
                            <i class="fas fa-tachometer-alt w-6"></i>
                            <span>Tableau de Bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-50">
                            <i class="fas fa-users w-6"></i>
                            <span>Clients</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-50">
                            <i class="fas fa-file-invoice-dollar w-6"></i>
                            <span>Crédits</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-50">
                            <i class="fas fa-chart-line w-6"></i>
                            <span>Rapports</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>
</html>