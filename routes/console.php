<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sessions-livraison:actualiser')
    ->everyMinute()
    ->withoutOverlapping();
Schedule::command('commandes:envoyer-rappels')
    ->dailyAt('10:00')
    ->withoutOverlapping();
