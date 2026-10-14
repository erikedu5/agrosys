<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Empresa $empresa,
        public User $user,
        public int $daysLeft
    ) {
    }

    public function build()
    {
        $subjectDays = $this->daysLeft === 1 ? '1 dia' : "{$this->daysLeft} dias";

        return $this
            ->subject("Recordatorio: tu prueba de AgroSys termina en {$subjectDays}")
            ->view('emails.trial_reminder');
    }
}

