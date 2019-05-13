<?php

namespace Statamic\Addons\WordpressImport;

use Statamic\Extend\Tasks;
use Illuminate\Console\Scheduling\Schedule;

class WordpressImportTasks extends Tasks
{
    /**
     * Define the task schedule
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    public function schedule(Schedule $schedule)
    {
        // $schedule->command('cache:clear')->weekly();
    }
}
