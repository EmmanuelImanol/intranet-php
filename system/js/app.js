/**
 * Atrestaurants - Lógica de Control de Costos
 * Este archivo maneja la interactividad, cálculos y peticiones API.
 */

// --- 1. CONFIGURACIÓN DE CATEGORÍAS ---
// Si necesitas agregar una nueva categoría, solo añádela a esta lista.
const CATEGORIAS = [
    { id: 'Queso, Pollo, etc.', label: 'Queso, Pollo, etc.', color: 'bg-blue-500' },
    { id: 'Carne', label: 'Carne', color: 'bg-red-500' },
    { id: 'Tocino', label: 'Tocino', color: 'bg-orange-500' },
    { id: 'Pan', label: 'Pan', color: 'bg-yellow-600' },
    { id: 'Refrescos (Propimex)', label: 'Refrescos (Propimex)', color: 'bg-indigo-500' },
    { id: 'Jugos', label: 'Jugos', color: 'bg-green-500' },
    { id: 'Papa', label: 'Papa', color: 'bg-amber-700' },
    { id: 'Cerveza (Modelo)', label: 'Cerveza (Modelo)', color: 'bg-yellow-500' },
    { id: 'Vinos', label: 'Vinos', color: 'bg-purple-600' },
    { id: 'Helado', label: 'Helado', color: 'bg-pink-400' },
    { id: 'Verdura', label: 'Verdura', color: 'bg-emerald-500' },
    { id: 'Abarrotes (Superama, SAMS, etc)', label: 'Abarrotes', color: 'bg-slate-600' },
    { id: 'Empaque', label: 'Empaque', color: 'bg-cyan-600' },
    { id: 'Grupo Beyond', label: 'Grupo Beyond', color: 'bg-zinc-800', isNegative: true }
];

const API_FILENAME = "guardar_costos.php";
let valores = {}; // Almacena temporalmente lo que el usuario escribe

// --- 2. INICIALIZACIÓN ---
window.onload = async () => {
    // Inicializa los iconos de Lucide
    lucide.createIcons();
    // Carga las sucursales desde la DB al select
    await cargarSucursales();
    // Dibuja los campos de entrada en el HTML
    generarInputs();
};

// --- 3. FUNCIONES DE NAVEGACIÓN Y UI ---

/**
 * Abre o cierra el menú lateral en dispositivos móviles.
 */
function toggleMobileSidebar() {
    const sidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('sidebar-hidden');
    sidebar.classList.toggle('sidebar-visible');
    overlay.classList.toggle('hidden');
}

/**
 * Maneja el clic en el menú para cambiar de sección.
 */
function handleNav(view) {
    switchView(view);
    // Si estamos en móvil, cerramos el menú después de elegir
    if (window.innerWidth < 1024) {
        toggleMobileSidebar();
    }
}

/**
 * Cambia la visibilidad entre las secciones de Cargar y Revisar.
 */
function switchView(view) {
    // Ocultar todas las secciones
    document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
    // Desactivar todos los botones del menú
    document.querySelectorAll('.nav-item').forEach(n => {
        n.classList.remove('active', 'text-white');
        n.classList.add('text-slate-400');
    });
    
    // Activar sección y botón seleccionado
    document.getElementById('view-' + view).classList.add('active');
    const nav = document.getElementById('nav-' + view);
    nav.classList.add('active', 'text-white');
    nav.classList.remove('text-slate-400');

    // Si entramos a revisión, cargamos los datos frescos de la DB
    if(view === 'revisar') cargarRevision();
}

// --- 4. LÓGICA DE DATOS Y API ---

/**
 * Construye URLs con parámetros de consulta.
 */
const getUrl = (params = {}) => {
    const searchParams = new URLSearchParams(params).toString();
    return API_FILENAME + (searchParams ? '?' + searchParams : '');
};

/**
 * Obtiene las sucursales desde MySQL y las inserta en el <select>.
 */
const cargarSucursales = async () => {
    try {
        const res = await fetch(getUrl({ action: 'sucursales' }));
        const sucursales = await res.json();
        const select = document.getElementById('select-sucursal');
        
        sucursales.forEach(suc => {
            const opt = document.createElement('option');
            opt.value = suc.id_sucursal;
            opt.textContent = suc.alias_comercial;
            select.appendChild(opt);
        });
    } catch (e) { 
        console.error("Error cargando sucursales desde la base de datos:", e); 
    }
};

/**
 * Crea dinámicamente los campos de entrada basados en el array CATEGORIAS.
 */
const generarInputs = () => {
    const grid = document.getElementById('grid-categorias');
    grid.innerHTML = ''; // Limpiar antes de generar
    CATEGORIAS.forEach(cat => {
        const div = document.createElement('div');
        div.className = `p-5 md:p-6 flex items-center justify-between bg-white border-b border-slate-50 hover:bg-slate-50/50 transition-colors`;
        div.innerHTML = `
            <div class="flex items-center gap-3 md:gap-4">
                <div class="w-1.5 h-10 rounded-full ${cat.color} opacity-80"></div>
                <div class="max-w-[120px] md:max-w-none">
                    <span class="text-xs md:text-sm font-black text-slate-700 block mb-0.5">${cat.label}</span>
                    ${cat.isNegative ? '<span class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Crédito / Resta</span>' : ''}
                </div>
            </div>
            <div class="relative group">
                ${cat.isNegative ? '<span class="absolute -left-4 top-1/2 -translate-y-1/2 font-black text-zinc-900">-</span>' : ''}
                <input type="number" step="0.01" placeholder="0.00" 
                       class="w-20 md:w-24 text-right font-mono font-bold text-lg md:text-xl outline-none bg-transparent border-b-2 border-slate-100 focus:border-indigo-500 transition-all py-1" 
                       oninput="actualizarValor('${cat.id}', this.value)">
                <span class="text-xs font-black text-slate-300 ml-2">%</span>
            </div>
        `;
        grid.appendChild(div);
    });
};

/**
 * Registra el valor ingresado y dispara el recálculo del total.
 */
const actualizarValor = (id, val) => {
    valores[id] = parseFloat(val) || 0;
    calcularTotal();
};

/**
 * Calcula el COGS total sumando ordinarios y restando negativos (Beyond).
 */
const calcularTotal = () => {
    let total = 0;
    CATEGORIAS.forEach(cat => {
        const v = valores[cat.id] || 0;
        total += cat.isNegative ? -v : v;
    });
    const display = document.getElementById('total-display');
    display.textContent = total.toFixed(2) + "%";
    display.className = "text-4xl md:text-6xl font-black " + (Math.abs(total) > 0 ? "text-white" : "text-white/40");
};

/**
 * Obtiene el historial de capturas para la tabla de revisión.
 */
const cargarRevision = async () => {
    try {
        const res = await fetch(getUrl({ action: 'revision' }));
        const data = await res.json();
        const body = document.getElementById('tabla-revisar-body');
        body.innerHTML = '';

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = "border-b border-slate-50 hover:bg-slate-50/50 transition-all";
            tr.innerHTML = `
                <td class="p-4 md:p-6 font-black text-slate-700 text-xs md:text-sm">${row.alias_comercial}</td>
                <td class="p-4 md:p-6 text-slate-400 text-[10px] md:text-xs font-bold uppercase tracking-tighter">${row.anio_mes.substring(0, 7)}</td>
                <td class="p-4 md:p-6 font-mono font-black text-indigo-600 text-base md:text-lg">${parseFloat(row.total_cogs).toFixed(2)}%</td>
                <td class="p-4 md:p-6 text-right">
                    <button onclick="eliminar('${row.id_sucursal}', '${row.anio_mes}')" class="p-2 md:p-3 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-2xl transition-all">
                        <i data-lucide="trash-2" size="20"></i>
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });
        lucide.createIcons();
    } catch (e) { console.error("Error al cargar la tabla de revisión:", e); }
};

/**
 * Elimina una captura completa (sucursal + mes) de MySQL.
 */
const eliminar = async (id, mes) => {
    if(!confirm("¿Estás seguro de que deseas eliminar permanentemente esta captura?")) return;
    try {
        await fetch(getUrl({ id_sucursal: id, anio_mes: mes }), { method: 'DELETE' });
        cargarRevision();
    } catch (e) {
        alert("Error al eliminar el registro.");
    }
};

// --- 5. MANEJO DE EVENTOS Y FORMULARIOS ---

/**
 * Procesa el acceso al sistema.
 */
document.getElementById('form-login').onsubmit = (e) => {
    e.preventDefault();
    const u = document.getElementById('user').value;
    const p = document.getElementById('pass').value;

    if(u === 'admin' && p === 'Atrestaurants2025') {
        document.getElementById('login-screen').classList.add('hidden');
        document.getElementById('main-app').classList.remove('hidden');
        lucide.createIcons();
    } else {
        const err = document.getElementById('login-error');
        err.textContent = "Acceso Denegado: Credenciales Inválidas";
        err.classList.remove('hidden');
    }
};

/**
 * Verifica si se ha seleccionado sucursal y mes para habilitar la captura.
 */
const checkLock = () => {
    const s = document.getElementById('select-sucursal');
    const m = document.getElementById('select-mes');
    if(s.value && m.value) {
        document.getElementById('lock-overlay').classList.add('hidden');
        document.getElementById('label-suc').textContent = s.options[s.selectedIndex].text;
        document.getElementById('label-mes').textContent = m.value;
        
        const btn = document.getElementById('btn-save');
        btn.disabled = false;
        btn.className = "w-full py-4 md:py-5 rounded-2xl font-black bg-indigo-600 text-white shadow-xl hover:bg-indigo-700 active:scale-95 transition-all uppercase tracking-widest text-sm md:text-base";
    }
};

document.getElementById('select-sucursal').onchange = checkLock;
document.getElementById('select-mes').onchange = checkLock;

/**
 * Envía los datos capturados al servidor PHP.
 */
document.getElementById('btn-save').onclick = async () => {
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.textContent = "Sincronizando...";

    const payload = {
        id_sucursal: document.getElementById('select-sucursal').value,
        anio_mes: document.getElementById('select-mes').value + "-01",
        categorias: valores
    };

    try {
        const response = await fetch(API_FILENAME, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload) 
        });
        
        if(response.ok) {
            alert("¡Datos guardados con éxito en la base de datos!");
            window.location.reload(); // Recargar para limpiar
        } else {
            throw new Error("Error en la respuesta del servidor");
        }
    } catch (e) {
        alert("Error de conexión al servidor MySQL. Revisa tu conexión.");
        btn.disabled = false;
        btn.textContent = "Guardar Datos";
    }
};