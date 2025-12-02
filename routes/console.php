<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('holds:expire')->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/contacts_scores.log'));
