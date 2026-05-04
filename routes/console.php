<?php

use Illuminate\Console\Scheduling\Schedule;

app(Schedule::class)->command('auth:clear-resets')->daily();

app(Schedule::class)->command('app:generate-recurring-tasks')
    ->daily()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/recurring-tasks.log'));

app(Schedule::class)->command('app:archive-expired-recurring-tasks')->daily();
