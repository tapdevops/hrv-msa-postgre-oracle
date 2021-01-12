<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use RdKafka;

class KafkaProducerController extends Controller {

    public function __construct() {
		$this->db_mobile_ins = DB::connection( 'mobile_ins' );
	}

	public function RUN_TESTING_PRODUCER() {
		$conf = new RdKafka\Conf();
		$conf->set( 'metadata.broker.list', '149.129.221.137:9092' );
		//If you need to produce exactly once and want to keep the original produce order, uncomment the line below
		$conf->set( 'enable.idempotence', 'true' ); 

		$producer = new RdKafka\Producer( $conf );
		$producer->poll( 0 );
		$topic = $producer->newTopic( "test" );
		$topic->produce( RD_KAFKA_PARTITION_UA, 0, "Ihsan Nakal" );
		

		for ( $flushRetries = 0; $flushRetries < 10; $flushRetries++ ) {
			$result = $producer->flush( 10000 );
			if ( RD_KAFKA_RESP_ERR_NO_ERROR === $result ) {
				break;
			}
		}

		if ( RD_KAFKA_RESP_ERR_NO_ERROR !== $result ) {
			throw new \RuntimeException( 'Was unable to flush, messages might be lost!' );
		}
	}

}