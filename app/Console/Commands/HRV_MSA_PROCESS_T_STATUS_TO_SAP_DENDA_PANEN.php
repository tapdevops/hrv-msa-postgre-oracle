<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exe = app('App\Http\Controllers\KafkaController')->HRV_MSA_PROCESS_T_STATUS_TO_SAP('HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN');
		echo $exe;
    }
}
