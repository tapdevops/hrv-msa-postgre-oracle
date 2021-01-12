<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TESTING_PRODUCER extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Kafka:TESTING_PRODUCER';

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
        $exe = app('App\Http\Controllers\KafkaProducerController')->RUN_TESTING_PRODUCER();
		echo $exe;
    }
}
