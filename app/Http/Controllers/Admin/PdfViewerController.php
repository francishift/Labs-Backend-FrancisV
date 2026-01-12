<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PdfViewerController extends Controller
{
    /**
     * Show the PDF viewer.
     */
    public function show(Request $request)
    {
        $request->validate([
            'url' => 'required|string',
            'title' => 'nullable|string',
            'backUrl' => 'nullable|string',
        ]);

        return Inertia::render('Admin/Common/PdfViewer', [
            'pdfUrl' => $request->query('url'),
            'title' => $request->query('title', 'Visor de Documento'),
            'backUrl' => $request->query('backUrl', route('dashboard')),
        ]);
    }
}
