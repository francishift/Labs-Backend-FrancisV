<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VpnAccessLog;
use Inertia\Inertia;

class VpnLogsController extends Controller
{
    public function index()
    {
        $logs = VpnAccessLog::with('user')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
        ]);
    }
}
