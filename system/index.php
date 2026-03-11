<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atrestaurants | Control de Costos</title>
    
    <!-- Librerías de Estilos e Iconos (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Vinculación al archivo CSS externo en la carpeta css/ -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="text-slate-900 min-h-screen flex flex-col">

    <!-- PANTALLA DE ACCESO (LOGIN) -->
    <div id="login-screen" class="flex-1 flex items-center justify-center p-4">
        <div class="bg-white p-6 md:p-10 rounded-[2.5rem] shadow-2xl w-full max-w-md border border-slate-100">
            <div class="text-center mb-8">
                <div class="inline-flex p-5 bg-indigo-600 rounded-3xl shadow-xl mb-6 text-white">
                    <i data-lucide="shield-check" size="40"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Atrestaurants</h1>
                <p class="text-slate-400 font-medium text-sm">Control Contable de Costos</p>
            </div>
            
            <form id="form-login" class="space-y-5">
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase ml-1">Usuario</label>
                    <input type="text" id="user" required class="w-full px-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="Usuario">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase ml-1">Contraseña</label>
                    <input type="password" id="pass" required class="w-full px-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="••••••••">
                </div>
                <p id="login-error" class="hidden text-red-500 text-xs font-bold text-center italic"></p>
                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black shadow-lg hover:bg-indigo-700 transition-all uppercase tracking-widest">Entrar</button>
            </form>
        </div>
    </div>

    <!-- APLICACIÓN PRINCIPAL (SIDEBAR + CONTENIDO) -->
    <div id="main-app" class="hidden flex flex-col lg:flex-row min-h-screen overflow-hidden relative">
        
        <!-- CABECERA PARA MÓVILES -->
        <header class="lg:hidden bg-white border-b border-slate-200 p-4 flex justify-between items-center z-40 shadow-sm">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                    <i data-lucide="activity" size="18"></i>
                </div>
                <span class="font-black text-lg text-slate-800 tracking-tighter uppercase">Atrestaurants</span>
            </div>
            <button onclick="toggleMobileSidebar()" class="p-2 bg-slate-50 rounded-xl text-slate-600">
                <i data-lucide="menu"></i>
            </button>
        </header>

        <!-- BARRA LATERAL (SIDEBAR) -->
        <aside id="mobile-sidebar" class="fixed lg:static inset-y-0 left-0 w-80 bg-white border-r border-slate-200 flex flex-col p-8 space-y-10 z-50 lg:translate-x-0 sidebar-hidden shadow-2xl lg:shadow-none">
            <div class="flex items-center justify-between lg:justify-start gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                        <i data-lucide="activity" size="24"></i>
                    </div>
                    <div class="font-black text-2xl text-slate-800 tracking-tighter">ATRESTAURANTS</div>
                </div>
                <button onclick="toggleMobileSidebar()" class="lg:hidden p-2 text-slate-400">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <nav class="flex-1 space-y-3">
                <button onclick="handleNav('cargar')" id="nav-cargar" class="nav-item active w-full flex items-center gap-4 px-5 py-4 rounded-2xl font-bold transition-all text-left">
                    <i data-lucide="layout-grid" size="20"></i> Cargar Costos
                </button>
                <button onclick="handleNav('revisar')" id="nav-revisar" class="nav-item w-full flex items-center gap-4 px-5 py-4 rounded-2xl font-bold transition-all text-slate-400 hover:bg-slate-50 text-left">
                    <i data-lucide="database" size="20"></i> Revisar Base
                </button>
            </nav>
            <div class="pt-6 border-t border-slate-100">
                <button onclick="window.location.reload()" class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-red-500 hover:bg-red-50 transition-all text-left">
                    <i data-lucide="log-out" size="20"></i> Cerrar Sesión
                </button>
            </div>
        </aside>

        <!-- Capa de fondo para el menú móvil -->
        <div id="sidebar-overlay" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden"></div>

        <!-- ÁREA DE CONTENIDO -->
        <main class="flex-1 p-4 lg:p-10 overflow-y-auto custom-scroll">
            
            <!-- VISTA: CARGAR COSTOS -->
            <section id="view-cargar" class="view-section active w-full max-w-6xl mx-auto">
                <header class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-10 gap-6">
                    <div>
                        <h2 class="text-3xl lg:text-4xl font-black text-slate-800 tracking-tight">Cargar <span class="text-indigo-600 font-normal">Materia Prima</span></h2>
                        <p class="text-slate-400 font-medium italic">Captura de COGS mensual.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-200 w-full xl:w-auto">
                        <div class="flex items-center gap-3 px-4 py-2 border-b sm:border-b-0 sm:border-r border-slate-100">
                            <i data-lucide="map-pin" class="text-indigo-500" size="18"></i>
                            <select id="select-sucursal" class="font-bold text-sm outline-none bg-transparent w-full sm:w-48 cursor-pointer">
                                <option value="">Sucursal...</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3 px-4 py-2">
                            <i data-lucide="calendar-days" class="text-indigo-500" size="18"></i>
                            <input type="month" id="select-mes" class="font-bold text-sm outline-none bg-transparent w-full sm:w-auto cursor-pointer">
                        </div>
                    </div>
                </header>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    <div class="lg:col-span-8 bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden relative min-h-[400px]">
                        <div id="lock-overlay" class="absolute inset-0 bg-white/95 backdrop-blur-[4px] z-30 flex flex-col items-center justify-center text-center p-10">
                            <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center text-amber-500 mb-6"><i data-lucide="unlock" size="32"></i></div>
                            <h3 class="text-xl font-black text-slate-800 uppercase">Selección Obligatoria</h3>
                            <p class="text-slate-400 mt-2 text-sm max-w-xs">Define Sucursal y Mes arriba para habilitar la captura.</p>
                        </div>
                        <div id="grid-categorias" class="grid grid-cols-1 md:grid-cols-2 gap-px bg-slate-100"></div>
                    </div>
                    <div class="lg:col-span-4 space-y-8">
                        <div class="bg-white p-8 rounded-[2rem] shadow-xl border border-slate-100">
                            <h3 class="font-black text-slate-400 mb-6 uppercase text-[10px] tracking-widest italic">Costo Total MP</h3>
                            <div class="p-8 rounded-3xl bg-slate-900 text-white mb-6 shadow-2xl relative overflow-hidden text-center">
                                <span id="total-display" class="text-5xl lg:text-6xl font-black tracking-tight">0.00%</span>
                            </div>
                            <div class="space-y-3 mb-8 text-sm italic border-t pt-4">
                                <div class="flex justify-between"><span>Unidad:</span> <span id="label-suc" class="font-bold text-indigo-600">---</span></div>
                                <div class="flex justify-between"><span>Periodo:</span> <span id="label-mes" class="font-bold text-indigo-600">---</span></div>
                            </div>
                            <button id="btn-save" disabled class="w-full py-5 rounded-2xl font-black bg-slate-100 text-slate-300 cursor-not-allowed uppercase tracking-widest text-sm shadow-inner transition-all">Guardar Datos</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- VISTA: REVISAR BASE DE DATOS -->
            <section id="view-revisar" class="view-section w-full max-w-6xl mx-auto">
                <header class="mb-10">
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-800 tracking-tight">Revisar <span class="text-indigo-600 font-normal">Base de Datos</span></h2>
                    <p class="text-slate-400 font-medium italic">Historial de capturas en MySQL.</p>
                </header>
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-x-auto">
                    <table class="w-full text-left min-w-[600px]">
                        <thead class="bg-slate-50/50 border-b border-slate-100">
                            <tr>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400">Sucursal</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Mes</th>
                                <th class="p-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Total COGS</th>
                                <th class="p-6 text-[10px] font-black uppercase text-slate-400 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-revisar-body"></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Vinculación al archivo JS externo en la carpeta js/ -->
    <script src="js/app.js"></script>
</body>
</html>