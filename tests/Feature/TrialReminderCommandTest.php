<?php

namespace Tests\Feature;

use App\Mail\TrialEndedMail;
use App\Mail\TrialReminderMail;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrialReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_7_day_trial_reminder_and_marks_empresa(): void
    {
        Mail::fake();

        Carbon::setTestNow(Carbon::parse('2026-02-10 10:00:00'));

        $empresa = Empresa::factory()->create([
            'plan_code' => 'campo',
            'plan_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays(7),
        ]);

        $admin = User::factory()->create([
            'tipo' => 'adminEmpresa',
            'id_empresa' => $empresa->id,
        ]);

        Artisan::call('subscriptions:send-trial-reminders');

        $empresa->refresh();
        $this->assertNotNull($empresa->trial_reminder_7d_sent_at);

        Mail::assertSent(TrialReminderMail::class, function (TrialReminderMail $mail) use ($admin, $empresa) {
            return $mail->hasTo($admin->email) && $mail->empresa->id === $empresa->id && $mail->daysLeft === 7;
        });
    }

    public function test_command_sends_trial_ended_email_and_marks_empresa(): void
    {
        Mail::fake();

        Carbon::setTestNow(Carbon::parse('2026-02-10 10:00:00'));

        $empresa = Empresa::factory()->create([
            'plan_code' => 'campo',
            'plan_cycle' => 'monthly',
            'trial_ends_at' => now()->subDay(),
        ]);

        $admin = User::factory()->create([
            'tipo' => 'adminEmpresa',
            'id_empresa' => $empresa->id,
        ]);

        Artisan::call('subscriptions:send-trial-reminders');

        $empresa->refresh();
        $this->assertNotNull($empresa->trial_ended_sent_at);

        Mail::assertSent(TrialEndedMail::class, function (TrialEndedMail $mail) use ($admin, $empresa) {
            return $mail->hasTo($admin->email) && $mail->empresa->id === $empresa->id;
        });
    }
}

