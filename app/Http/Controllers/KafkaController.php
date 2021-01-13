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
		$Kafka = new RdKafka\Consumer( $conf );
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
				// return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - SUCCESS '.PHP_EOL;
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
		// Kafka Config
		$conf = new RdKafka\Conf();
		$conf->set( 'group.id', 'MSA_INTERNAL_GROUP' );
		$Kafka = new RdKafka\Consumer( $conf );
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
				$last_offset = $this->cek_offset_payload( $topic );
				if ( $last_offset !== false ) {
					if ( $last_offset ==null) {
						if( (int)$message->offset >= $last_offset ){
							echo $this->HRV_MSA_PROCESS_T_STATUS_TO_SAP_STATEMENT( $payload, (int)$message->offset, $topic, str_replace('HRV_MSA_PROCESS_', '', $topic) );
						}	
					} else {
						if ( (int)$message->offset > $last_offset ){
							echo $this->HRV_MSA_PROCESS_T_STATUS_TO_SAP_STATEMENT( $payload, (int)$message->offset, $topic, str_replace('HRV_MSA_PROCESS_', '', $topic) );
						}	
					}
				}
			}
		}
	}

	# PHP Query HRV_MSA_PROCESS_TRANSACTION_STATEMENT
	public function HRV_MSA_PROCESS_T_STATUS_TO_SAP_STATEMENT( $payload, $offset, $topic, $table ) {
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

		try {
				$insert_into = ''; $insert_value = ''; $update_set = ''; $where = '';
				foreach ($payload as $field => $value) 
				{
					if($field!='POST_STATUS' && $field!='POST_TIMESTAMP')
					{
						if($field=='JUMLAH')
						{
							$value = $value==null?0:$value;
						}
						$insert_into .= $insert_into==''?$field:','.$field;
						$insert_value .= $insert_value==''?"'".$value."'":",'".$value."'";
						if($topic=='HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN'){
							if($field=='COMP_CODE' || $field=='PROFILE_NAME' || $field=='NO_BCC' || $field=='KODE_DENDA_PANEN'){
								$where .= $where==''?$field."='".$value."'":" AND ".$field."='".$value."'";
							}else {
								$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
							}
						}
						if($topic=='HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB'){
							if($field=='COMP_CODE' || $field=='PROFILE_NAME' || $field=='ESTATE_CODE' || $field=='NO_NAB' || $field=='NO_BCC' || $field=='TANGGAL'){
								$where .= $where==''?$field."='".$value."'":" AND ".$field."='".$value."'";
							}else {
								$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
							}
						}
						if($topic=='HRV_MSA_PROCESS_T_STATUS_TO_SAP_EBCC'){
							if($field=='COMP_CODE' || $field=='PROFILE_NAME' || $field=='NO_BCC'){
								$where .= $where==''?$field."='".$value."'":" AND ".$field."='".$value."'";
							}else {
								$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
							}
						}
					}
				}
				$sql = "
						DECLARE
							n_count number;
						BEGIN
							SELECT count(*) into n_count 
							FROM EHARVESTING.$table
							WHERE $where;
							IF n_count > 0 THEN
								UPDATE EHARVESTING.$table
								SET $update_set
								WHERE $where;
							ELSE
								INSERT INTO EHARVESTING.$table ($insert_into) 
								VALUES ($insert_value);
							END IF;
						END;";
				$this->eharvesting_oracle->statement($sql);
				$this->eharvesting_oracle->commit();
				// return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - SUCCESS '.PHP_EOL;
		}
		catch ( \Throwable $e ) {
			return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - FAILED '.$e->getMessage().PHP_EOL;
		}
		catch ( \Exception $e ) {
			return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - FAILED '.$e->getMessage().PHP_EOL;
		}
	}
}