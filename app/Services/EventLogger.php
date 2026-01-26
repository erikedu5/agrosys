<?php

namespace App\Services;

use App\Models\EventLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EventLogger
{
    protected string $eventId;
    protected string $eventType;
    protected array $context = [];
    protected array $steps = [];
    protected ?EventLog $dbLog = null;
    protected float $startTime;

    /**
     * Iniciar un nuevo evento para tracking
     */
    public static function start(string $eventType, array $context = []): self
    {
        $logger = new self();
        $logger->eventId = Str::uuid()->toString();
        $logger->eventType = $eventType;
        $logger->startTime = microtime(true);
        $logger->context = array_merge($context, [
            'event_id' => $logger->eventId,
            'user_id' => auth()->id(),
            'sucursal_id' => session('sucursal_activa'),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Log en archivo
        Log::channel('transactions')->info("🚀 INICIO: {$eventType}", $logger->context);

        // Guardar en base de datos
        try {
            $logger->dbLog = EventLog::create([
                'event_id' => $logger->eventId,
                'event_type' => $eventType,
                'status' => 'started',
                'user_id' => auth()->id(),
                'sucursal_id' => session('sucursal_activa'),
                'initial_context' => $context,
                'steps' => [],
                'steps_count' => 0,
                'started_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Si falla la BD, solo loggeamos en archivo
            Log::warning("No se pudo guardar evento en BD: {$e->getMessage()}");
        }

        return $logger;
    }

    /**
     * Registrar un paso del proceso
     */
    public function step(string $step, array $data = []): self
    {
        $stepData = [
            'step' => $step,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->steps[] = $stepData;

        $stepContext = array_merge($this->context, $stepData);

        // Log en archivo
        Log::channel('transactions')->info("📍 PASO: {$this->eventType} - {$step}", $stepContext);

        // Actualizar en base de datos
        $this->updateDbLog([
            'steps' => $this->steps,
            'steps_count' => count($this->steps),
        ]);

        return $this;
    }

    /**
     * Registrar datos adicionales
     */
    public function addContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Registrar que el evento fue exitoso
     */
    public function success(array $result = []): void
    {
        $duration = $this->getDuration();

        $successContext = array_merge($this->context, [
            'result' => $result,
            'duration' => $duration,
        ]);

        // Log en archivo
        Log::channel('transactions')->info("✅ ÉXITO: {$this->eventType}", $successContext);

        // Actualizar en base de datos
        $this->updateDbLog([
            'status' => 'success',
            'result' => $result,
            'duration' => $duration,
            'completed_at' => now(),
        ]);
    }

    /**
     * Registrar un error en el evento
     */
    public function error(Throwable $exception, array $additionalContext = []): void
    {
        $duration = $this->getDuration();

        $errorContext = array_merge($this->context, $additionalContext, [
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'duration' => $duration,
        ]);

        // Log en archivo
        Log::channel('transactions')->error("❌ ERROR: {$this->eventType}", $errorContext);

        // También loggear en el canal principal
        Log::error("ERROR en {$this->eventType} [Event ID: {$this->eventId}]", [
            'event_id' => $this->eventId,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        // Actualizar en base de datos
        $this->updateDbLog([
            'status' => 'error',
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'error_trace' => $exception->getTraceAsString(),
            'duration' => $duration,
            'completed_at' => now(),
        ]);
    }

    /**
     * Registrar una advertencia
     */
    public function warning(string $message, array $data = []): self
    {
        $warningData = [
            'warning' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->steps[] = $warningData;

        $warningContext = array_merge($this->context, $warningData);

        // Log en archivo
        Log::channel('transactions')->warning("⚠️ ADVERTENCIA: {$this->eventType} - {$message}", $warningContext);

        // Actualizar en base de datos
        $this->updateDbLog([
            'status' => 'warning',
            'steps' => $this->steps,
            'steps_count' => count($this->steps),
        ]);

        return $this;
    }

    /**
     * Obtener el ID del evento para referencia
     */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * Calcular duración desde el inicio del evento
     */
    protected function getDuration(): float
    {
        return round(microtime(true) - $this->startTime, 2);
    }

    /**
     * Actualizar el registro en base de datos
     */
    protected function updateDbLog(array $data): void
    {
        if ($this->dbLog) {
            try {
                $this->dbLog->update($data);
            } catch (\Exception $e) {
                // Si falla la actualización, solo loggeamos
                Log::debug("No se pudo actualizar evento en BD: {$e->getMessage()}");
            }
        }
    }

    /**
     * Helper para logging rápido de eventos simples
     */
    public static function logEvent(string $eventType, string $message, array $context = []): void
    {
        $fullContext = array_merge([
            'event_id' => Str::uuid()->toString(),
            'user_id' => auth()->id(),
            'sucursal_id' => session('sucursal_activa'),
        ], $context);

        Log::channel('transactions')->info("{$eventType}: {$message}", $fullContext);

        // Guardar en BD
        try {
            EventLog::create([
                'event_id' => $fullContext['event_id'],
                'event_type' => $eventType,
                'status' => 'success',
                'user_id' => $fullContext['user_id'],
                'sucursal_id' => $fullContext['sucursal_id'],
                'initial_context' => $context,
                'steps' => [['step' => $message, 'timestamp' => now()->toIso8601String()]],
                'steps_count' => 1,
                'started_at' => now(),
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::debug("No se pudo guardar evento simple en BD: {$e->getMessage()}");
        }
    }

    /**
     * Helper para logging rápido de errores
     */
    public static function logError(string $eventType, Throwable $exception, array $context = []): void
    {
        $errorContext = array_merge([
            'event_id' => Str::uuid()->toString(),
            'user_id' => auth()->id(),
            'sucursal_id' => session('sucursal_activa'),
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
        ], $context);

        Log::channel('transactions')->error("❌ {$eventType}", $errorContext);
        Log::error("{$eventType}: {$exception->getMessage()}");

        // Guardar en BD
        try {
            EventLog::create([
                'event_id' => $errorContext['event_id'],
                'event_type' => $eventType,
                'status' => 'error',
                'user_id' => $errorContext['user_id'],
                'sucursal_id' => $errorContext['sucursal_id'],
                'initial_context' => $context,
                'error_message' => $exception->getMessage(),
                'error_file' => $exception->getFile(),
                'error_line' => $exception->getLine(),
                'error_trace' => $exception->getTraceAsString(),
                'started_at' => now(),
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::debug("No se pudo guardar error en BD: {$e->getMessage()}");
        }
    }
}
