<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		\App\Console\Commands\HRV_MSA_PROCESS_TR_DELIVERY_H::class
		// \App\Console\Commands\INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D::class,
		// \App\Console\Commands\INS_MSA_AUTH_TM_USER_AUTH::class,
		// \App\Console\Commands\INS_MSA_FINDING_TR_FINDING::class,
		// \App\Console\Commands\INS_MSA_INSPECTION_TR_INSPECTION_GENBA::class,
		// \App\Console\Commands\INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_H::class,
		// \App\Console\Commands\INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_D::class,
		// \App\Console\Commands\INS_MSA_INSPECTION_TR_TRACK_INSPECTION::class
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		// $schedule->command('inspire')
		//          ->hourly();
	}

	/**
	 * Register the Closure based commands for the application.
	 *
	 * @return void
	 */
	protected function commands()
	{
		require base_path('routes/console.php');
	}
}
