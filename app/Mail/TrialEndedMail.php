<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialEndedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Empresa $empresa,
        public User $user
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Tu prueba de AgroSys ha terminado')
            ->view('emails.trial_ended');
    }
}

