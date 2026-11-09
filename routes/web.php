<?php
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\EstudianteController;
use App\Http\Controllers\Admin\PreguntaController;
use App\Http\Controllers\Admin\CuponController;
use App\Http\Controllers\Admin\PagoController;
use App\Http\Controllers\Admin\ExamenGeneradoController;
use App\Http\Controllers\Admin\InteraccionCallCenterController;
use App\Http\Controllers\Admin\AsignaturaController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\ExamenRealizadoController;
use App\Http\Controllers\Admin\ClaseController;
use App\Http\Controllers\Alumno\AlumnoController;
use App\Http\Controllers\Alumno\DocumentoEstudianteController;
use App\Http\Controllers\NotificacionController;



// ============================================
// RUTAS PÚBLICAS
// ============================================
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('estudiante.dashboard');
    }

    // Sin landing: se va directo al formulario de inicio de sesión
    // (reflash para no perder mensajes como "sesión expirada").
    session()->reflash();
    return redirect()->route('login');
})->name('home');

Route::get('/terminos-y-condiciones', function () {
    return \Inertia\Inertia::render('Terms');
})->name('terms');

// Páginas de autenticación (pantalla completa, azul institucional)
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => \Inertia\Inertia::render('Auth/Login'))->name('login');
    Route::get('/crear-cuenta', fn () => \Inertia\Inertia::render('Auth/Register'))->name('registro');
    Route::get('/recuperar-contrasena', fn () => \Inertia\Inertia::render('Auth/ForgotPassword'))->name('password.request');
});

// Procesar login/registro
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Validación de la CURP en tiempo real (registro): autocompleta nombre y fecha de nacimiento
// Recuperación de contraseña por correo (sólo administradores)
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');

Route::get('/curp/validar', [AuthController::class, 'validarCurp'])->middleware('throttle:30,1')->name('curp.validar');

// Heartbeat público
Route::post('/heartbeat', function() {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
})->name('heartbeat');

// ============================================
// PANEL DE ADMINISTRADOR (con middleware admin)
// ============================================
Route::prefix('administrador')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    
    // ========== DASHBOARD ==========
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // ========== API PARA NOTIFICACIONES ==========
    Route::get('/api/notificaciones', [AdminController::class, 'getNotificacionesApi'])->name('api.notificaciones');
    Route::post('/api/notificaciones/marcar/{id}', [AdminController::class, 'marcarNotificacionVista'])->name('api.notificaciones.marcar');
    Route::get('/api/actividad-reciente', [AdminController::class, 'getActividadRecienteApi'])->name('api.actividad');
    Route::get('/api/top-estudiantes', [AdminController::class, 'getTopEstudiantesApi'])->name('api.top.estudiantes');
    
    // ========== GESTIÓN DE ESTUDIANTES ==========
    Route::prefix('estudiantes')->name('estudiantes.')->group(function () {
        // PRIMERO: Todas las rutas estáticas (sin parámetros variables)
        Route::get('/crear', [EstudianteController::class, 'create'])->name('create');
        Route::post('/', [EstudianteController::class, 'store'])->name('store');
        Route::get('/get-municipios-prepa', [EstudianteController::class, 'getMunicipiosPrepa'])->name('get.municipios.prepa');
        Route::get('/get-localidades-prepa', [EstudianteController::class, 'getLocalidadesPrepa'])->name('get.localidades.prepa');
        Route::get('/get-preparatorias', [EstudianteController::class, 'getPreparatorias'])->name('get.preparatorias');
        Route::get('/get-municipios-universidad', [EstudianteController::class, 'getMunicipiosUniversidad'])->name('get.municipios.universidad');
        Route::get('/get-localidades-universidad', [EstudianteController::class, 'getLocalidadesUniversidad'])->name('get.localidades.universidad');
        Route::get('/get-universidades', [EstudianteController::class, 'getUniversidades'])->name('get.universidades');
        Route::get('/exportar/excel', [EstudianteController::class, 'exportarExcel'])->name('exportar.excel');
        Route::get('/{id}/reporte-pdf', [EstudianteController::class, 'generarReportePDF'])->name('pdf');
        Route::get('/{id}/documentos-pdf', [EstudianteController::class, 'exportarDocumentosPDF'])->name('documentos-pdf');
        Route::post('/{id}/generar-certificado', [EstudianteController::class, 'generarCertificado'])->name('generar-certificado');
        Route::get('/{id}/certificado', [EstudianteController::class, 'descargarCertificado'])->name('certificado.descargar');
        Route::post('/validar-cupon', [EstudianteController::class, 'validarCupon'])->name('validar.cupon');
        Route::get('/cupones-disponibles', [EstudianteController::class, 'getCuponesDisponibles'])->name('cupones.disponibles');

        // ============================================================
        // DOCUMENTOS DEL ESTUDIANTE (ADMIN)
        // Al aprobar/rechazar se notifica al estudiante dentro del sistema.
        // ============================================================
        Route::prefix('{id}/documentos')->name('documentos.')->group(function () {
            Route::get('/',                        [EstudianteController::class, 'documentosIndex'])->name('index');
            Route::post('/{documentoId}/aprobar',  [EstudianteController::class, 'aprobarDocumento'])->name('aprobar');
            Route::post('/{documentoId}/rechazar', [EstudianteController::class, 'rechazarDocumento'])->name('rechazar');
            Route::get('/{documentoId}/ver',       [EstudianteController::class, 'verDocumento'])->name('ver');
            Route::get('/{documentoId}/descargar', [EstudianteController::class, 'descargarDocumento'])->name('descargar');
        });
        
        // ÚLTIMO: Rutas con parámetros (después de todas las estáticas)
        Route::get('/', [EstudianteController::class, 'index'])->name('index');
        Route::get('/{id}', [EstudianteController::class, 'show'])->name('show');
        Route::get('/{id}/editar', [EstudianteController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EstudianteController::class, 'update'])->name('update');
        Route::delete('/{id}', [EstudianteController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/reset-password', [EstudianteController::class, 'resetPassword'])->name('reset-password');
    });
    
    // ========== GESTIÓN DE ADMINISTRADORES ==========
    Route::prefix('administradores')->name('administradores.')->group(function () {
        Route::get('/', [AdminController::class, 'administradores'])->name('index');
        Route::get('/crear', [AdminController::class, 'createAdmin'])->name('create');
        Route::post('/', [AdminController::class, 'storeAdmin'])->name('store');
        Route::get('/{id}/editar', [AdminController::class, 'editAdmin'])->name('edit');
        Route::put('/{id}', [AdminController::class, 'updateAdmin'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'eliminarAdmin'])->name('destroy');
    });
    
    // ========== GESTIÓN DE PREGUNTAS ==========
    Route::prefix('preguntas')->name('preguntas.')->group(function () {
        Route::get('/', [PreguntaController::class, 'indexPreguntas'])->name('index');
        Route::get('/crear', [PreguntaController::class, 'createPregunta'])->name('create');
        Route::post('/', [PreguntaController::class, 'storePregunta'])->name('store');
        Route::get('/exportar-excel', [PreguntaController::class, 'exportarExcel'])->name('exportar-excel');
        Route::get('/exportar-csv', [PreguntaController::class, 'exportarCsv'])->name('exportar-csv');
        Route::get('/plantilla', [PreguntaController::class, 'plantilla'])->name('plantilla');
        Route::post('/importar/analizar', [PreguntaController::class, 'analizarImportacion'])->name('importar.analizar');
        Route::post('/importar', [PreguntaController::class, 'importar'])->name('importar');
        Route::get('/{id}', [PreguntaController::class, 'showPregunta'])->name('show');
        Route::get('/{id}/editar', [PreguntaController::class, 'editPregunta'])->name('edit');
        Route::put('/{id}', [PreguntaController::class, 'updatePregunta'])->name('update');
        Route::delete('/{id}', [PreguntaController::class, 'destroyPregunta'])->name('destroy');
    });
    
    Route::prefix('cupones')->name('cupones.')->group(function () {
        // ========== RUTAS ESTÁTICAS (primero) ==========
        Route::get('/', [CuponController::class, 'index'])->name('index');
        Route::get('/crear', [CuponController::class, 'create'])->name('create');
        Route::post('/', [CuponController::class, 'store'])->name('store');
        Route::post('/regenerar', [CuponController::class, 'regenerarCodigo'])->name('regenerar');
        Route::post('/masivo', [CuponController::class, 'generarMasivo'])->name('masivo');
        Route::get('/generar-masivo', [CuponController::class, 'generarMasivoForm'])->name('generar-masivo'); // ⬅️ IMPORTANTE: antes de /{id}

        // ========== RUTAS CON PARÁMETROS (después) ==========
        Route::get('/{id}', [CuponController::class, 'show'])->name('show');
        Route::get('/{id}/editar', [CuponController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CuponController::class, 'update'])->name('update');
        Route::delete('/{id}', [CuponController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/regenerar', [CuponController::class, 'regenerarCodigo'])->name('regenerar.id');
    });
        
    // ========== GESTIÓN DE PAGOS ==========
    Route::prefix('pagos')->name('pagos.')->group(function () {
        // PRIMERO: Rutas estáticas
        Route::get('/', [PagoController::class, 'index'])->name('index');
        Route::get('/crear', [PagoController::class, 'create'])->name('create');
        Route::post('/', [PagoController::class, 'store'])->name('store');
        Route::get('/dashboard', [PagoController::class, 'dashboard'])->name('dashboard');
        Route::get('/exportar/csv', [PagoController::class, 'exportar'])->name('exportar');
        Route::get('/alumno/{alumnoId}', [PagoController::class, 'getPagosByAlumno'])->name('alumno.pagos');
        
        // ÚLTIMO: Rutas con parámetros
        Route::get('/{id}', [PagoController::class, 'show'])->name('show');
        Route::get('/{id}/editar', [PagoController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PagoController::class, 'update'])->name('update');
        Route::delete('/{id}', [PagoController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/aprobar', [PagoController::class, 'aprobar'])->name('aprobar');
        Route::post('/{id}/rechazar', [PagoController::class, 'rechazar'])->name('rechazar');
        Route::put('/{id}/cambiar-estado', [PagoController::class, 'cambiarEstado'])->name('cambiar.estado');
    });
    
    Route::prefix('examenes-realizados')->name('examenes-realizados.')->group(function () {
        Route::get('/', [ExamenRealizadoController::class, 'index'])->name('index');
        Route::get('/estadisticas', [ExamenRealizadoController::class, 'estadisticas'])->name('estadisticas'); // 👈 NUEVA RUTA
        Route::get('/{id}', [ExamenRealizadoController::class, 'show'])->name('show');
        Route::put('/{id}', [ExamenRealizadoController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExamenRealizadoController::class, 'destroy'])->name('destroy');
    });

    // ========== GESTIÓN DE EXÁMENES ==========
    Route::prefix('examenes')->name('examenes.')->group(function () {
        // PRIMERO: Todas las rutas ESTÁTICAS
        Route::get('/dashboard', [ExamenGeneradoController::class, 'dashboard'])->name('dashboard');
        Route::get('/crear', [ExamenGeneradoController::class, 'create'])->name('create');
        Route::post('/generar-automatico', [ExamenGeneradoController::class, 'generarAutomatico'])->name('generar.automatico');
        Route::get('/', [ExamenGeneradoController::class, 'index'])->name('index');
        Route::post('/', [ExamenGeneradoController::class, 'store'])->name('store');
        
        // ÚLTIMO: Todas las rutas con parámetros
        Route::post('/{id}/duplicar', [ExamenGeneradoController::class, 'duplicar'])->name('duplicar');
        Route::get('/{id}/editar', [ExamenGeneradoController::class, 'edit'])->name('edit');
        Route::get('/{id}', [ExamenGeneradoController::class, 'show'])->name('show');
        Route::put('/{id}', [ExamenGeneradoController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExamenGeneradoController::class, 'destroy'])->name('destroy');
    });
    
    // ========== CALL CENTER ==========
    Route::prefix('callcenter')->name('callcenter.')->group(function () {
        // PRIMERO: Rutas estáticas
        Route::get('/', [InteraccionCallCenterController::class, 'index'])->name('index');
        Route::get('/crear', [InteraccionCallCenterController::class, 'create'])->name('create');
        Route::post('/', [InteraccionCallCenterController::class, 'store'])->name('store');
        
        // ÚLTIMO: Rutas con parámetros
        Route::get('/{id}', [InteraccionCallCenterController::class, 'show'])->name('show');
        Route::get('/{id}/editar', [InteraccionCallCenterController::class, 'edit'])->name('edit');
        Route::put('/{id}', [InteraccionCallCenterController::class, 'update'])->name('update');
        Route::delete('/{id}', [InteraccionCallCenterController::class, 'destroy'])->name('destroy');
    });

    // ========== GESTIÓN DE ASIGNATURAS (MATERIAS) ==========
    Route::prefix('asignaturas')->name('asignaturas.')->group(function () {
        Route::get('/', [AsignaturaController::class, 'index'])->name('index');
        Route::get('/create', [AsignaturaController::class, 'create'])->name('create');
        Route::get('/{asignatura}/edit', [AsignaturaController::class, 'edit'])->name('edit');  // Cambiado {id} a {asignatura}
        Route::post('/', [AsignaturaController::class, 'store'])->name('store');
        Route::put('/{asignatura}', [AsignaturaController::class, 'update'])->name('update');  // Cambiado {id} a {asignatura}
        Route::delete('/{asignatura}', [AsignaturaController::class, 'destroy'])->name('destroy');  // Cambiado {id} a {asignatura}
    });
    
    // ========== GESTIÓN DE VIDEOS ==========
    Route::prefix('videos')->name('videos.')->group(function () {
        Route::get('/', [VideoController::class, 'index'])->name('index');
        Route::get('/duracion', [VideoController::class, 'obtenerDuracion'])->name('duracion');
        Route::get('/create', [VideoController::class, 'create'])->name('create');
        Route::post('/', [VideoController::class, 'store'])->name('store');
        Route::get('/{id}', [VideoController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [VideoController::class, 'edit'])->name('edit');
        Route::put('/{id}', [VideoController::class, 'update'])->name('update');
        Route::delete('/{id}', [VideoController::class, 'destroy'])->name('destroy');
    });
    
    // ========== GESTIÓN DE CLASES ==========
    Route::prefix('clases')->name('clases.')->group(function () {
        Route::get('/', [ClaseController::class, 'index'])->name('index');
        Route::get('/create', [ClaseController::class, 'create'])->name('create');
        Route::post('/', [ClaseController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ClaseController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ClaseController::class, 'update'])->name('update');
        Route::get('/{id}', [ClaseController::class, 'show'])->name('show');
        Route::delete('/{id}', [ClaseController::class, 'destroy'])->name('destroy');
        Route::get('/api/by-asignatura/{asignaturaId}', [ClaseController::class, 'getClasesByAsignaturaApi'])->name('api.by-asignatura');
        Route::get('/siguiente-numero/{asignaturaId}', [ClaseController::class, 'getSiguienteNumero'])->name('siguiente-numero');
    });
    
    // ========== PERFIL ==========
    Route::get('/perfil', [AdminController::class, 'perfil'])->name('perfil');
    Route::put('/perfil', [AdminController::class, 'updatePerfil'])->name('perfil.update');
    Route::put('/perfil/password', [AdminController::class, 'updatePassword'])->name('perfil.password');
});


// Comprobantes de pago y fotos de perfil servidos por Laravel (sin depender de public/storage).
Route::middleware('auth')->prefix('archivos')->name('archivos.')->group(function () {
    Route::get('/comprobante/{pago}', [\App\Http\Controllers\ArchivoPrivadoController::class, 'comprobante'])->name('comprobante');
    Route::get('/foto/{estudiante}', [\App\Http\Controllers\ArchivoPrivadoController::class, 'foto'])->name('foto');
});

// Autocompletado de domicilio por código postal (registro del alumno y formularios del admin).
Route::get('/codigos-postales/{cp}', [\App\Http\Controllers\CodigoPostalController::class, 'show'])
    ->middleware(['auth', 'throttle:60,1'])->name('codigos-postales.show');

Route::middleware(['auth'])->prefix('estudiante')->name('estudiante.')->group(function () {
    // Vistas principales
    Route::get('/dashboard', [AlumnoController::class, 'dashboard'])->name('dashboard');
    Route::get('/progreso', [AlumnoController::class, 'progreso'])->name('progreso');
    Route::get('/simulador', [AlumnoController::class, 'simulador'])->name('simulador');
    Route::get('/certificacion', [AlumnoController::class, 'certificacion'])->name('certificacion');
    Route::get('/examenes', function () { return \Inertia\Inertia::render('Estudiante/Examenes'); })->name('examenes');
    Route::get('/clases-premium', [AlumnoController::class, 'clasesPremium'])->name('clases-premium');

    // Perfil
    Route::get('/perfil', [AlumnoController::class, 'perfil'])->name('perfil');
    Route::put('/perfil/actualizar', [AlumnoController::class, 'actualizarPerfil'])->name('perfil.actualizar');
    Route::post('/perfil/cambiar-password', [AlumnoController::class, 'cambiarPassword'])->name('perfil.cambiar-password');
    Route::post('/subir-foto', [AlumnoController::class, 'subirFoto'])->name('subir.foto');
    Route::get('/get-foto', [AlumnoController::class, 'getFoto'])->name('get.foto');
    Route::delete('/eliminar-foto', [AlumnoController::class, 'eliminarFoto'])->name('eliminar.foto');

    // ============================================================
    // Documentos del estudiante (PDF)
    // ============================================================
    Route::prefix('documentos')->name('documentos.')->group(function () {
        Route::get('/',        [DocumentoEstudianteController::class, 'index'])->name('index');
        Route::post('/',       [DocumentoEstudianteController::class, 'store'])->name('store');
        Route::get('/{id}',    [DocumentoEstudianteController::class, 'show'])->name('show');
        Route::get('/{id}/descargar', [DocumentoEstudianteController::class, 'descargar'])->name('descargar');
        Route::delete('/{id}', [DocumentoEstudianteController::class, 'destroy'])->name('destroy');
    });

    // Certificado de finalización
    Route::get('/certificado', [AlumnoController::class, 'descargarCertificado'])->name('certificado.descargar');

    // Completar perfil (primera vez)
    Route::get('/completar-perfil', [AlumnoController::class, 'completarPerfilForm'])->name('completar-perfil');
    Route::post('/completar-perfil', [AlumnoController::class, 'completarPerfil'])->name('completar.perfil');

    // Pagos y checkout
    Route::get('/checkout', [AlumnoController::class, 'checkout'])->name('checkout');
    Route::post('/aplicar-cupon', [AlumnoController::class, 'aplicarCupon'])->name('aplicar-cupon');
    Route::delete('/eliminar-cupon', [AlumnoController::class, 'eliminarCupon'])->name('eliminar-cupon');
    Route::post('/procesar-solicitud-pago', [AlumnoController::class, 'procesarSolicitudPago'])->name('procesar-solicitud-pago');
    Route::get('/ficha-pago/{pago}', [AlumnoController::class, 'mostrarFichaPago'])->name('ficha-pago');
    Route::get('/descargar-ficha/{pago}', [AlumnoController::class, 'descargarFichaPago'])->name('descargar-ficha');
    Route::post('/subir-comprobante', [AlumnoController::class, 'subirComprobantePago'])->name('subir-comprobante');
    Route::get('/pago-exito/{pago}', [AlumnoController::class, 'pagoExito'])->name('pago-exito');
    Route::get('/mis-pagos', [AlumnoController::class, 'misPagos'])->name('mis-pagos');

    // Exámenes
    Route::get('/examen-materia/{examenId}', [AlumnoController::class, 'examenMateria'])->name('examen.materia');
    Route::post('/responder-examen-materia', [AlumnoController::class, 'responderExamenMateria'])->name('responder.examen.materia');
    Route::get('/examen-curso/{examenId}', [AlumnoController::class, 'examenCurso'])->name('examen-curso');
    Route::post('/responder-examen-curso', [AlumnoController::class, 'responderExamenCurso'])->name('responder.examen-curso');

    // Simulador
    Route::post('/simulador/responder', [AlumnoController::class, 'responderSimulador'])->name('simulador.responder');
    Route::get('/resultados/{id}', [AlumnoController::class, 'resultados'])->name('resultados');
    Route::get('/simulador/{id}', [AlumnoController::class, 'cargarSimulador'])->name('simulador.cargar');

    // Progreso y APIs
    Route::post('/registrar-progreso-video', [AlumnoController::class, 'registrarProgresoVideo'])->name('registrar.progreso.video');
    Route::post('/heartbeat', [AlumnoController::class, 'heartbeat'])->name('heartbeat');
    Route::get('/get-estudiante', [AlumnoController::class, 'getEstudiante'])->name('get.estudiante');
    Route::get('/historial-examenes', [AlumnoController::class, 'getHistorialExamenes'])->name('historial.examenes');
    Route::get('/api/progreso', [AlumnoController::class, 'getProgresoApi'])->name('api.progreso');
    Route::get('/api/estadisticas', [AlumnoController::class, 'getEstadisticas'])->name('api.estadisticas');
    Route::get('/api/ultimos-examenes', [AlumnoController::class, 'getUltimosExamenes'])->name('api.ultimos-examenes');
    Route::get('/api/tiempo-estudio', [AlumnoController::class, 'getTiempoEstudio'])->name('api.tiempo-estudio');

    Route::get('/recomendaciones', [AlumnoController::class, 'getRecomendacionesUniversidades'])->name('recomendaciones');
    Route::get('/clase-recursos/{claseId}', [AlumnoController::class, 'getRecursosClase'])->name('clase.recursos');
    Route::get('/universidades', [AlumnoController::class, 'getUniversidades'])->name('universidades');

    Route::post('/validar-cupon', [AlumnoController::class, 'validarCupon'])->name('validar.cupon');
    Route::get('checkout-pendiente/{pagoId?}', [AlumnoController::class, 'checkoutPendiente'])->name('checkout-pendiente');
    Route::post('/pago/mercadopago/crear', [App\Http\Controllers\Alumno\AlumnoController::class, 'crearPreferenciaMercadoPago'])
        ->name('pago.mercadopago.crear');

    Route::get('/pago/mercadopago/success', [App\Http\Controllers\Alumno\AlumnoController::class, 'pagoExitoMercadoPago'])
        ->name('pago.mercadopago.success');

    Route::get('/pago/mercadopago/failure', [App\Http\Controllers\Alumno\AlumnoController::class, 'pagoFallidoMercadoPago'])
        ->name('pago.mercadopago.failure');

    Route::get('/pago/mercadopago/pending', [App\Http\Controllers\Alumno\AlumnoController::class, 'pagoPendienteMercadoPago'])
        ->name('pago.mercadopago.pending');

    // El webhook lo llama Mercado Pago (servidor a servidor): sin sesión ni CSRF.
    Route::post('/pago/mercadopago/webhook', [App\Http\Controllers\Alumno\AlumnoController::class, 'webhookMercadoPago'])
        ->name('pago.mercadopago.webhook')
        ->withoutMiddleware(['auth']);
});

// ============================================
// NOTIFICACIONES (estudiante + administrador)
// ============================================
Route::middleware(['auth'])->prefix('notificaciones')->name('notificaciones.')->group(function () {
    Route::get('/', [NotificacionController::class, 'index'])->name('index');
    Route::get('/feed', [NotificacionController::class, 'feed'])->name('feed');
    Route::post('/{id}/leer', [NotificacionController::class, 'marcarLeida'])->name('leer');
    Route::post('/leer-todas', [NotificacionController::class, 'marcarTodas'])->name('leer-todas');
    Route::delete('/leidas', [NotificacionController::class, 'eliminarLeidas'])->name('eliminar-leidas');
    Route::delete('/{id}', [NotificacionController::class, 'eliminar'])->name('eliminar');
});