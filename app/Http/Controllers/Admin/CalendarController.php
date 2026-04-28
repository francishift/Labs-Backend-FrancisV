<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Services\CalendarEventService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    private CalendarEventService $calendarService;

    public function __construct(CalendarEventService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function index()
    {
        return Inertia::render('Admin/Calendar/Index');
    }

    public function events(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $eventosCombinados = $this->calendarService->obtenerEventosCombinados($start, $end, auth()->id());

        return response()->json($eventosCombinados);
    }

    public function store(StoreCalendarEventRequest $request)
    {
        try {
            $eventoLocal = $this->calendarService->crearEventoLocalYGoogle($request->validated(), auth()->id());
            return response()->json($eventoLocal, 201);
        } catch (Exception $e) {
            // El error es explícito ahora, le indicamos al frontend que falló la parte de Google
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id, UpdateCalendarEventRequest $request)
    {
        try {
            $eventoLocal = $this->calendarService->actualizarEventoExistente($id, $request->validated(), auth()->id());
            return response()->json($eventoLocal);
        } catch (Exception $e) {
            $codigo = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $codigo);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            $updateMode = $request->input('update_mode', 'single');
            $recurringId = $request->input('recurring_event_id');

            $this->calendarService->eliminarEvento($id, $updateMode, $recurringId, auth()->id());

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            $codigo = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $codigo);
        }
    }
}
