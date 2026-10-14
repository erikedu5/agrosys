<?php

namespace App\Console\Commands;

use App\Mail\TrialEndedMail;
use App\Mail\TrialReminderMail;
use App\Models\Empresa;
use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialReminders extends Command
{
    protected $signature = 'subscriptions:send-trial-reminders {--dry-run}';

    protected $description = 'Send trial ending reminders (7/3/1 days) and trial ended emails.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $reminderDays = SubscriptionPlans::reminderDays();
        $maxDays = !empty($reminderDays) ? max($reminderDays) : 7;

        $empresas = Empresa::query()
            ->whereNotNull('plan_code')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now()->addDays($maxDays)->endOfDay())
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($empresas as $empresa) {
            $trialEndsAt = $empresa->trial_ends_at;
            if (!$trialEndsAt) {
                $skipped++;
                continue;
            }

            $secondsLeft = now()->diffInSeconds($trialEndsAt, false);
            $daysLeft = (int) ceil($secondsLeft / 86400);

            $admins = User::query()
                ->where('id_empresa', $empresa->id)
                ->where('tipo', 'adminEmpresa')
                ->whereNull('deleted_at')
                ->get();

            if ($admins->isEmpty()) {
                $skipped++;
                continue;
            }

            if ($secondsLeft <= 0) {
                if ($empresa->trial_ended_sent_at) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("[dry-run] Trial ended email -> Empresa {$empresa->id} ({$empresa->nombre})");
                } else {
                    foreach ($admins as $admin) {
                        Mail::to($admin->email)->send(new TrialEndedMail($empresa, $admin));
                    }
                    $empresa->trial_ended_sent_at = now();
                    $empresa->save();
                    $sent++;
                }

                continue;
            }

            if (!in_array($daysLeft, $reminderDays, true)) {
                $skipped++;
                continue;
            }

            $column = match ($daysLeft) {
                7 => 'trial_reminder_7d_sent_at',
                3 => 'trial_reminder_3d_sent_at',
                1 => 'trial_reminder_1d_sent_at',
                default => null,
            };

            if (!$column) {
                $skipped++;
                continue;
            }

            if ($empresa->{$column}) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] Trial reminder {$daysLeft}d -> Empresa {$empresa->id} ({$empresa->nombre})");
                continue;
            }

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new TrialReminderMail($empresa, $admin, $daysLeft));
            }

            $empresa->{$column} = now();
            $empresa->save();
            $sent++;
        }

        $this->info("Done. sent={$sent}, skipped={$skipped}, scanned={$empresas->count()}");

        return self::SUCCESS;
    }
}

