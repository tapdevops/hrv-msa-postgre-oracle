<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use RdKafka;

class KafkaController extends Controller {

    public function __construct() {
		$this->eharvesting_oracle = DB::connection( 'eharvesting_oracle' );
		$this->eharvesting_pgsql = DB::connection( 'eharvesting_pgsql' );
	}
	
	public function test() {
		$eharvesting_oracle = $this->eharvesting_oracle->select( "SELECT * FROM TR_DELIVERY_H WHERE ID < 300" );
		$eharvesting_pgsql = $this->eharvesting_pgsql->select( "SELECT * FROM \"TR_DELIVERY_H\" WHERE \"ID\" < 300" );
		dd($eharvesting_oracle,$eharvesting_pgsql);
	}
	
	public function cek_offset_payload( $topic ) {
		$get = $this->eharvesting_oracle->select( "SELECT * FROM TM_KAFKA_PAYLOADS WHERE TOPIC_NAME = '$topic'" );
		if ( count( $get ) ) {
			return $get[0]->offset;
		} 
		else {
			return false;
		}
	}
	
	# PHP Kafka HRV_MSA_PROCESS_TRANSACTION
	public function HRV_MSA_PROCESS_TRANSACTION($topic) {
		// Kafka Config
		$conf = new RdKafka\Conf();
		$conf->set( 'group.id', 'MSA_INTERNAL_GROUP' );
		// $conf->set('security.protocol', 'sasl_plaintext');//sasl_plaintext SASL_SSL
		// $conf->set('sasl.mechanisms', 'PLAIN');
		// $conf->set('sasl.username', 'admin' );
		// $conf->set('sasl.password', '12345' );
		$Kafka = new RdKafka\Consumer( $conf );

		//$Kafka->addBrokers( config('app.kafkahost') );
		$Kafka->addBrokers( env('KAFKA_BROKER') );

		$topicConf = new RdKafka\TopicConf();
		$topicConf->set( 'auto.commit.interval.ms', 100 );
		$topicConf->set( 'auto.offset.reset', 'smallest' );

		$Topic = $Kafka->newTopic( $topic, $topicConf );
		$Topic->consumeStart( 0, RD_KAFKA_OFFSET_BEGINNING );

		while ( true ) {
			$message = $Topic->consume( 0, 1000 );
			if ( null === $message ) {
				continue;
			} 
			else if ( $message->err ) {
				echo $message->errstr(), "\n";
				break;
			} 
			else {
				$payload = json_decode( $message->payload, true );
				// print $message->payload.PHP_EOL;
				$last_offset = $this->cek_offset_payload( $topic );
				if ( $last_offset !== false ) {
					if ( $last_offset ==null) {
						if( (int)$message->offset >= $last_offset ){
							echo $this->HRV_MSA_PROCESS_TRANSACTION_STATEMENT( $payload, (int)$message->offset, $topic, str_replace('HRV_MSA_PROCESS_', '', $topic) );
						}	
					} else {
						if ( (int)$message->offset > $last_offset ){
							echo $this->HRV_MSA_PROCESS_TRANSACTION_STATEMENT( $payload, (int)$message->offset, $topic, str_replace('HRV_MSA_PROCESS_', '', $topic) );
						}	
					}
				}
			}
		}
	}

	# PHP Query HRV_MSA_PROCESS_TRANSACTION_STATEMENT
	public function HRV_MSA_PROCESS_TRANSACTION_STATEMENT( $payload, $offset, $topic, $table ) {
		//update offset payloads
		$this->eharvesting_oracle->statement( "
			UPDATE 
				EHARVESTING.TM_KAFKA_PAYLOADS
			SET
				OFFSET = $offset,
				EXECUTE_DATE = SYSDATE
			WHERE
				TOPIC_NAME = '$topic'
		" );
		$this->eharvesting_oracle->commit();

		// dd($payload);
		$table = $table.'_TEST';
		try {
			if(ISSET($payload['ID']))
			{
				$insert_into = ''; $insert_value = ''; $update_set = '';
				foreach ($payload as $field => $value) 
				{
					$insert_into .= $insert_into==''?$field:','.$field;
					$insert_value .= $insert_value==''?"'".$value."'":",'".$value."'";
					$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
				}
				$sql = "BEGIN
							INSERT INTO EHARVESTING.$table ($insert_into) 
							VALUES ($insert_value);
						EXCEPTION
							WHEN dup_val_on_index THEN
							UPDATE EHARVESTING.$table
							SET $update_set
							WHERE ID='{$payload['ID']}';
						END;";
				$this->eharvesting_oracle->statement($sql);
				$this->eharvesting_oracle->commit();
				return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - SUCCESS '.PHP_EOL;
			}
			else 
			{
				return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - FAILED '.PHP_EOL;
			}
		}
		catch ( \Throwable $e ) {
			return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - FAILED '.$e->getMessage().PHP_EOL;
		}
		catch ( \Exception $e ) {
			return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - FAILED '.$e->getMessage().PHP_EOL;
		}
		
	}

	# PHP Kafka HRV_MSA_PROCESS_SAP
	public function HRV_MSA_PROCESS_T_STATUS_TO_SAP($topic){

	}
}