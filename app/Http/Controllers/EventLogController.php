<?php

namespace App\Http\Controllers;

use App\Models\EventLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventLogController extends Controller
{
    /**
     * Mostrar listado de eventos
     */
    public function index(Request $request)
    {
        $query = EventLog::with(['user', 'sucursal'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Búsqueda por event_id
        if ($request->filled('search')) {
            $query->where('event_id', 'like', "%{$request->search}%");
        }

        $events = $query->paginate(50);

        // Obtener tipos de eventos únicos para filtro
        $eventTypes = EventLog::select('event_type')
            ->distinct()
            ->pluck('event_type');

        return Inertia::render('Admin/EventLogs/Index', [
            'events' => $events,
            'eventTypes' => $eventTypes,
            'filters' => $request->only(['event_type', 'status', 'user_id', 'sucursal_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Mostrar detalle de un evento
     */
    public function show($id)
    {
        $event = EventLog::with(['user', 'sucursal'])->findOrFail($id);

        return Inertia::render('Admin/EventLogs/Show', [
            'event' => $event,
        ]);
    }

    /**
     * API: Obtener estadísticas de eventos
     */
    public function stats(Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days);

        $stats = [
            'total' => EventLog::where('created_at', '>=', $startDate)->count(),
            'successful' => EventLog::where('created_at', '>=', $startDate)
                ->where('status', 'success')
                ->count(),
            'failed' => EventLog::where('created_at', '>=', $startDate)
                ->where('status', 'error')
                ->count(),
            'warnings' => EventLog::where('created_at', '>=', $startDate)
                ->where('status', 'warning')
                ->count(),
            'by_type' => EventLog::where('created_at', '>=', $startDate)
                ->selectRaw('event_type, count(*) as count')
                ->groupBy('event_type')
                ->get(),
            'avg_duration' => EventLog::where('created_at', '>=', $startDate)
                ->whereNotNull('duration')
                ->avg('duration'),
            'recent_errors' => EventLog::where('created_at', '>=', $startDate)
                ->where('status', 'error')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * API: Buscar eventos por event_id
     */
    public function search(Request $request)
    {
        $eventId = $request->get('event_id');

        if (!$eventId) {
            return response()->json(['error' => 'event_id requerido'], 400);
        }

        $events = EventLog::where('event_id', 'like', "%{$eventId}%")
            ->with(['user', 'sucursal'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($events);
    }

    /**
     * Limpiar eventos antiguos
     */
    public function cleanup(Request $request)
    {
        $days = $request->get('days', 90); // Por defecto 90 días

        $deleted = EventLog::where('created_at', '<', now()->subDays($days))
            ->delete();

        return response()->json([
            'message' => "Se eliminaron {$deleted} eventos de más de {$days} días",
            'deleted' => $deleted,
        ]);
    }
}
