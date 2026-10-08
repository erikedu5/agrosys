<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('pos:prune', function () {
    if (!config('pos.enabled')) return;
    $snapshots = \Illuminate\Support\Facades\DB::table('pos_snapshots')->where('expires_at', '<=', now())->delete();
    $challenges = \Illuminate\Support\Facades\DB::table('pos_login_challenges')->where('expires_at', '<=', now())->delete();
    $this->info("Removed {$snapshots} expired snapshots and {$challenges} challenges.");
})->purpose('Remove expired native POS snapshots and login challenges');
