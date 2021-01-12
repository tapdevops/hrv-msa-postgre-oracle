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
		\App\Console\Commands\HRV_MSA_PROCESS_TR_DELIVERY_H::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_DELIVERY_ITEM_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_DELIVERY_USER_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_DELIVERY_VEHICLE_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_H::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_ITEM_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_HARVEST_REPORT_H::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_HARVEST_REPORT_ITEM_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_HARVEST_REPORT_USER_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_LHM_REPORT_H::class,
		\App\Console\Commands\HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D::class,
		\App\Console\Commands\HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN::class,
		\App\Console\Commands\HRV_MSA_PROCESS_T_STATUS_TO_SAP_EBCC::class,
		\App\Console\Commands\HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB::class,
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
