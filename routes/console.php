<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('app:make-admin {email}', function () {
    $user = \App\Models\User::where('email', strtolower(trim($this->argument('email'))))->first();
    if (! $user) { $this->error('Register this account first.'); return 1; }
    $user->is_admin = true;
    $user->save();
    $this->info('Admin access enabled for '.$user->email);
})->purpose('Grant admin access to an existing registered account');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
