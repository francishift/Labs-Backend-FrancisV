<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ResumenHoraController extends Controller
{
    /**
     * Display the resumen horas page.
     */
    public function index()
    {
        return Inertia::render('Admin/Horas/Index');
    }
}
