<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VpnAccessLog;
use Inertia\Inertia;

class VpnLogsController extends Controller
{
    public function index(Request $request)
    {
        $logs = VpnAccessLog::with('user')
            ->latest()
            ->paginate(15);

        if ($request->has('last_log_id')) {
            $lastLogId = $request->input('last_log_id');
            // If the last log ID is 0, it means the table was empty
            if ($lastLogId == 0) {
                $newLogsCount = $logs->total();
            } else {
                $newLogsCount = VpnAccessLog::where('id', '>', $lastLogId)->count();
            }

            if ($newLogsCount > 0) {
                $request->session()->flash('success', "Se han encontrado $newLogsCount logs nuevos.");
            } else {
                $request->session()->flash('success', 'No hay logs nuevos desde la última actualización.');
            }
        }

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
        ]);
    }

    public function clear()
    {
        VpnAccessLog::truncate();

        // Optional: Log that the admin cleared the logs
        VpnAccessLog::create([
            'user_id' => auth()->id(),
            'target_device_id' => null,
            'action' => 'DELETE_SUCCESS_DB',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => 'El administrador vació todo el historial de logs.',
        ]);

        return back()->with('success', 'Logs vaciados correctamente.');
    }
}
