<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ProyectoController;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\MantenimientoController;
use App\Http\Controllers\Admin\MantenimientoServicioController;
use App\Http\Controllers\Admin\SoftwareController;
use App\Http\Controllers\Admin\PdfViewerController;
use App\Http\Controllers\Admin\ResumenHoraController;
use App\Http\Controllers\Admin\NotaController;
use App\Http\Controllers\Admin\PushSubscriptionController;



Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Mantenemos la ruta /admin por compatibilidad, redirigiendo al dashboard
    Route::get('/admin', function () {
        return redirect()->route('dashboard');
    })->name('admin.dashboard');

    // API Dropdowns (Lazy Loading)
    Route::prefix('api/dropdown')->group(function () {
        Route::get('clientes', [\App\Http\Controllers\Api\DropdownController::class, 'clientes'])->name('api.dropdown.clientes');
        Route::get('proyectos', [\App\Http\Controllers\Api\DropdownController::class, 'proyectos'])->name('api.dropdown.proyectos');
        Route::get('mantenimientos', [\App\Http\Controllers\Api\DropdownController::class, 'mantenimientos'])->name('api.dropdown.mantenimientos');
        Route::get('extensiones', [\App\Http\Controllers\Api\DropdownController::class, 'extensiones'])->name('api.dropdown.extensiones');
    });

    // Rutas exclusivas de Admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/usuarios', [UserController::class, 'index'])->name('admin.usuarios.index');
        Route::post('/admin/usuarios', [UserController::class, 'store'])->name('admin.usuarios.store');
        Route::patch('/admin/usuarios/{user}/role', [UserController::class, 'updateRole'])->name('admin.usuarios.role');
        Route::patch('/admin/usuarios/{user}', [UserController::class, 'update'])->name('admin.usuarios.update');
        Route::delete('/admin/usuarios/{user}', [UserController::class, 'destroy'])->name('admin.usuarios.destroy'); 

        // Rutas VPN
        Route::post('/admin/usuarios/{user}/network-access', [\App\Http\Controllers\Admin\VpnController::class, 'store'])->name('admin.vpn.store');
        Route::get('/admin/sys/logs', [\App\Http\Controllers\Admin\VpnLogsController::class, 'index'])->name('admin.logs.index');
        Route::delete('/admin/sys/logs/clear', [\App\Http\Controllers\Admin\VpnLogsController::class, 'clear'])->name('admin.logs.clear');
        Route::post('/admin/sys/d/{id}', [\App\Http\Controllers\Admin\VpnController::class, 'destroy'])->name('admin.vpn.destroy');

        Route::get('/admin/clientes', [ClientController::class, 'index'])->name('admin.clientes.index');
        Route::get('/admin/clientes/{client}', [ClientController::class, 'show'])->name('admin.clientes.show');
        Route::get('/admin/clientes/{client}/export-pdf', [ClientController::class, 'exportPdf'])->name('admin.clientes.pdf');
        Route::post('/admin/clientes', [ClientController::class, 'store'])->name('admin.clientes.store');
        Route::patch('/admin/clientes/{client}', [ClientController::class, 'update'])->name('admin.clientes.update');
        Route::delete('/admin/clientes/{client}', [ClientController::class, 'destroy'])->name('admin.clientes.destroy');
        // ... (resto dei port)
        Route::post('/admin/clientes/import', [ClientController::class, 'import'])->name('admin.clientes.import');

        // NUEVAS RESTRICCIONES (Antes eran admin|coordinador)
        Route::get('/admin/extensiones', [ExtensionController::class, 'index'])->name('admin.extensiones.index');
        Route::post('/admin/extensiones', [ExtensionController::class, 'store'])->name('admin.extensiones.store');
        Route::patch('/admin/extensiones/{extensione}', [ExtensionController::class, 'update'])->name('admin.extensiones.update');
        Route::delete('/admin/extensiones/{extensione}', [ExtensionController::class, 'destroy'])->name('admin.extensiones.destroy');

        Route::get('/admin/configuracion', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::patch('/admin/configuracion', [SettingsController::class, 'update'])->name('admin.settings.update');

        Route::get('/admin/mantenimientos', [MantenimientoController::class, 'index'])->name('admin.mantenimientos.index');
        Route::get('/admin/mantenimientos/{mantenimiento}', [MantenimientoController::class, 'show'])->name('admin.mantenimientos.show');
        Route::post('/admin/mantenimientos', [MantenimientoController::class, 'store'])->name('admin.mantenimientos.store');
        Route::patch('/admin/mantenimientos/{mantenimiento}', [MantenimientoController::class, 'update'])->name('admin.mantenimientos.update');
        Route::delete('/admin/mantenimientos/{mantenimiento}', [MantenimientoController::class, 'destroy'])->name('admin.mantenimientos.destroy');

        Route::get('/admin/mantenimiento-servicios', [MantenimientoServicioController::class, 'index'])->name('admin.mantenimiento-servicios.index');
        Route::post('/admin/mantenimiento-servicios', [MantenimientoServicioController::class, 'store'])->name('admin.mantenimiento-servicios.store');
        Route::patch('/admin/mantenimiento-servicios/{mantenimientoServicio}', [MantenimientoServicioController::class, 'update'])->name('admin.mantenimiento-servicios.update');
        Route::delete('/admin/mantenimiento-servicios/{mantenimientoServicio}', [MantenimientoServicioController::class, 'destroy'])->name('admin.mantenimiento-servicios.destroy');

        Route::get('/admin/visor-pdf', [PdfViewerController::class, 'show'])->name('admin.visor-pdf');
        Route::get('/admin/resumen-horas', [ResumenHoraController::class, 'index'])->name('admin.resumen-horas.index');
        Route::get('/admin/resumen-horas/detalle/{month}', [ResumenHoraController::class, 'detalle'])->name('admin.resumen-horas.detalle');

        Route::get('/admin/purchase-facturas', [\App\Http\Controllers\Admin\PurchaseFacturaController::class, 'index'])->name('admin.purchase-facturas.index');
        Route::post('/admin/purchase-facturas', [\App\Http\Controllers\Admin\PurchaseFacturaController::class, 'store'])->name('admin.purchase-facturas.store');
        Route::get('/admin/purchase-facturas/{purchaseFactura}/pdf', [\App\Http\Controllers\Admin\PurchaseFacturaController::class, 'showPdf'])->name('admin.purchase-facturas.pdf');
        Route::put('/admin/purchase-facturas/{purchaseFactura}', [\App\Http\Controllers\Admin\PurchaseFacturaController::class, 'update'])->name('admin.purchase-facturas.update');
        Route::delete('/admin/purchase-facturas/{purchaseFactura}', [\App\Http\Controllers\Admin\PurchaseFacturaController::class, 'destroy'])->name('admin.purchase-facturas.destroy');
        Route::post('/admin/purchase-facturas/{purchaseFactura}/overwrite', [\App\Http\Controllers\Admin\PurchaseFacturaController::class, 'confirmOverwrite'])->name('admin.purchase-facturas.overwrite');
        Route::patch('/admin/purchase-facturas/{purchaseFactura}/status', [\App\Http\Controllers\Admin\PurchaseFacturaController::class, 'updateStatus'])->name('admin.purchase-facturas.update-status');
        
        // Presupuestos Nativos
        Route::get('/admin/presupuestos', [\App\Http\Controllers\Admin\PresupuestoController::class, 'index'])->name('admin.presupuestos.index');
        Route::get('/admin/presupuestos/create', [\App\Http\Controllers\Admin\PresupuestoController::class, 'create'])->name('admin.presupuestos.create');
        Route::post('/admin/presupuestos', [\App\Http\Controllers\Admin\PresupuestoController::class, 'store'])->name('admin.presupuestos.store');
        Route::get('/admin/presupuestos/{presupuesto}', [\App\Http\Controllers\Admin\PresupuestoController::class, 'show'])->name('admin.presupuestos.show');
        Route::get('/admin/presupuestos/{presupuesto}/edit', [\App\Http\Controllers\Admin\PresupuestoController::class, 'edit'])->name('admin.presupuestos.edit');
        Route::patch('/admin/presupuestos/{presupuesto}', [\App\Http\Controllers\Admin\PresupuestoController::class, 'update'])->name('admin.presupuestos.update');
        Route::delete('/admin/presupuestos/{presupuesto}', [\App\Http\Controllers\Admin\PresupuestoController::class, 'destroy'])->name('admin.presupuestos.destroy');
        Route::get('/admin/presupuestos/{presupuesto}/export-pdf', [\App\Http\Controllers\Admin\PresupuestoController::class, 'exportPdf'])->name('admin.presupuestos.pdf');
        Route::post('/admin/presupuestos/{presupuesto}/send-pdf', [\App\Http\Controllers\Admin\PresupuestoController::class, 'sendPdfEmail'])->name('admin.presupuestos.send-pdf');
        Route::patch('/admin/presupuestos/{presupuesto}/reactivate', [\App\Http\Controllers\Admin\PresupuestoController::class, 'reactivate'])->name('admin.presupuestos.reactivate');
        Route::patch('/admin/presupuestos/{presupuesto}/status', [\App\Http\Controllers\Admin\PresupuestoController::class, 'updateStatus'])->name('admin.presupuestos.update-status');
        Route::post('/admin/presupuestos/{presupuesto}/convertir-a-factura', [\App\Http\Controllers\Admin\PresupuestoController::class, 'convertirAFactura'])->name('admin.presupuestos.convertir-a-factura');


        // Facturas de Ventas Nativas
        Route::get('/admin/facturas', [\App\Http\Controllers\Admin\FacturaController::class, 'index'])->name('admin.facturas.index');
        Route::get('/admin/facturas/create', [\App\Http\Controllers\Admin\FacturaController::class, 'create'])->name('admin.facturas.create');
        Route::post('/admin/facturas', [\App\Http\Controllers\Admin\FacturaController::class, 'store'])->name('admin.facturas.store');
        Route::get('/admin/facturas/{factura}', [\App\Http\Controllers\Admin\FacturaController::class, 'show'])->name('admin.facturas.show');
        Route::get('/admin/facturas/{factura}/edit', [\App\Http\Controllers\Admin\FacturaController::class, 'edit'])->name('admin.facturas.edit');
        Route::patch('/admin/facturas/{factura}', [\App\Http\Controllers\Admin\FacturaController::class, 'update'])->name('admin.facturas.update');
        Route::delete('/admin/facturas/{factura}', [\App\Http\Controllers\Admin\FacturaController::class, 'destroy'])->name('admin.facturas.destroy');
        Route::get('/admin/facturas/{factura}/export-pdf', [\App\Http\Controllers\Admin\FacturaController::class, 'exportPdf'])->name('admin.facturas.pdf');
        Route::post('/admin/facturas/{factura}/send-pdf', [\App\Http\Controllers\Admin\FacturaController::class, 'sendPdfEmail'])->name('admin.facturas.send-pdf');
        Route::patch('/admin/facturas/{factura}/status', [\App\Http\Controllers\Admin\FacturaController::class, 'updateStatus'])->name('admin.facturas.update-status');
        Route::post('/admin/facturas/{factura}/duplicate', [\App\Http\Controllers\Admin\FacturaController::class, 'duplicate'])->name('admin.facturas.duplicate');
    });

    // Rutas accesibles por Admin y Coordinador
    Route::middleware('role:admin|coordinador')->group(function () {
        Route::get('/admin/clientes/{client}/presupuestos', [ClientController::class, 'getPresupuestos'])->name('admin.clientes.presupuestos');
        Route::get('/admin/clientes/{client}/facturas', [ClientController::class, 'getFacturas'])->name('admin.clientes.facturas');
        Route::get('/admin/proyectos', [ProyectoController::class, 'index'])->name('admin.proyectos.index');
        Route::get('/admin/proyectos/{proyecto}', [ProyectoController::class, 'show'])->name('admin.proyectos.show');
        Route::get('/admin/proyectos/{proyecto}/export-pdf', [ProyectoController::class, 'exportPdf'])->name('admin.proyectos.pdf');
        Route::post('/admin/proyectos/{proyecto}/send-pdf', [ProyectoController::class, 'sendPdfEmail'])->name('admin.proyectos.send-pdf');
        Route::get('/admin/mantenimientos/{mantenimiento}/export-pdf', [MantenimientoController::class, 'exportPdf'])->name('admin.mantenimientos.pdf');
        Route::post('/admin/mantenimientos/{mantenimiento}/send-pdf', [MantenimientoController::class, 'sendPdfEmail'])->name('admin.mantenimientos.send-pdf');
        Route::post('/admin/proyectos', [ProyectoController::class, 'store'])->name('admin.proyectos.store');
        Route::patch('/admin/proyectos/{proyecto}', [ProyectoController::class, 'update'])->name('admin.proyectos.update');
        Route::delete('/admin/proyectos/{proyecto}', [ProyectoController::class, 'destroy'])->name('admin.proyectos.destroy');

        Route::get('/admin/servicios', [ServicioController::class, 'index'])->name('admin.servicios.index'); 
        Route::post('/admin/servicios', [ServicioController::class, 'store'])->name('admin.servicios.store');
        Route::patch('/admin/servicios/{servicio}', [ServicioController::class, 'update'])->name('admin.servicios.update');
        Route::delete('/admin/servicios/{servicio}', [ServicioController::class, 'destroy'])->name('admin.servicios.destroy');

        Route::get('/admin/software-hosting', [SoftwareController::class, 'index'])->name('admin.softwares.index');
        Route::post('/admin/software-hosting', [SoftwareController::class, 'store'])->name('admin.softwares.store');
        Route::patch('/admin/software-hosting/{software}', [SoftwareController::class, 'update'])->name('admin.softwares.update');
        Route::delete('/admin/software-hosting/{software}', [SoftwareController::class, 'destroy'])->name('admin.softwares.destroy');

        Route::get('/admin/calendar', [\App\Http\Controllers\Admin\CalendarController::class, 'index'])->name('admin.calendar.index');
        Route::get('/admin/calendar/events', [\App\Http\Controllers\Admin\CalendarController::class, 'events'])->name('admin.calendar.events');
        Route::post('/admin/calendar', [\App\Http\Controllers\Admin\CalendarController::class, 'store'])->name('admin.calendar.store');
        Route::put('/admin/calendar/{id}', [\App\Http\Controllers\Admin\CalendarController::class, 'update'])->name('admin.calendar.update');
        Route::delete('/admin/calendar/{id}', [\App\Http\Controllers\Admin\CalendarController::class, 'destroy'])->name('admin.calendar.destroy');

        // Notificaciones
        Route::get('/admin/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('admin.notifications.index');
        Route::get('/admin/api/notifications/unread', [\App\Http\Controllers\Admin\NotificationController::class, 'fetchUnread'])->name('admin.notifications.fetchUnread');
        Route::patch('/admin/api/notifications/{id}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('admin.notifications.markAsRead');
        Route::post('/admin/notifications/mark-all-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('admin.notifications.markAllRead');
        Route::delete('/admin/api/notifications/{id}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('admin.notifications.destroy');
        Route::delete('/admin/notifications/destroy-all', [\App\Http\Controllers\Admin\NotificationController::class, 'destroyAll'])->name('admin.notifications.destroyAll');

        Route::post('/admin/push-subscriptions', [PushSubscriptionController::class, 'store'])
            ->name('admin.push-subscriptions.store');

        Route::post('/admin/push-subscriptions/delete', [PushSubscriptionController::class, 'destroy'])
            ->name('admin.push-subscriptions.destroy');

    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';