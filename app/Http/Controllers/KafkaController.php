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
		$eharvesting_oracle = $this->eharvesting_pgsql->select( "SELECT * FROM TR_DELIVERY_H WHERE ID < 300" );
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
	
	# PHP Kafka HRV_MSA_PROCESS_TR_DELIVERY_H
	public function HRV_MSA_PROCESS_TR_DELIVERY_H() {
		// Kafka Config
		$conf = new RdKafka\Conf();
		$conf->set( 'group.id', 'MSA_INTERNAL_GROUP' );
		// $conf->set('security.protocol', 'sasl_plaintext');//sasl_plaintext SASL_SSL
		// $conf->set('sasl.mechanisms', 'PLAIN');
		// $conf->set('sasl.username', 'admin' );
		// $conf->set('sasl.password', '12345' );
		$topic = "HRV_MSA_PROCESS_TR_DELIVERY_H";
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
				$last_offset = 0;
				if ( $last_offset !== false ) {
					if ( $last_offset ==null) {
						if( (int)$message->offset >= $last_offset ){
							echo $this->HRV_MSA_PROCESS_TR_DELIVERY_H_SAVE( $payload, (int)$message->offset );
						}	
					} else {
						if ( (int)$message->offset > $last_offset ){
							echo $this->HRV_MSA_PROCESS_TR_DELIVERY_H_SAVE( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TR_EBCC_VALIDATION_H
	public function HRV_MSA_PROCESS_TR_DELIVERY_H_SAVE( $payload, $offset ) {
		// return '['.$offset.'] '.$payload['EBVTC'].PHP_EOL;
		
		//update offset payloads
		$this->eharvesting_oracle->statement( "
			UPDATE 
				EHARVESTING.TM_KAFKA_PAYLOADS
			SET
				OFFSET = $offset,
				EXECUTE_DATE = SYSDATE
			WHERE
				TOPIC_NAME = 'HRV_MSA_PROCESS_TR_DELIVERY_H'
		" );
		$this->eharvesting_oracle->commit();

		try {
			$INSTM = date( 'YmdHis', strtotime( $payload['INSTM'] ) );
			$STIME = date( 'YmdHis', strtotime( $payload['STIME'] ) );
			$sql = "INSERT INTO 
					EHARVESTING.HRV_MSA_PROCESS_TR_DELIVERY_H ( 
						ID, 
						HOLDING_NAME, 
						HOLDING_CODE, 
						REGION_NAME,
						REGION_CODE, 
						COMPANY_NAME, 
						COMPANY_CODE, 
						BA_NAME, 
						BA_CODE, 
						NAB_CODE, 
						NAB_REMARK, 
						IS_DOUBLE_HANDLING, 
						SYNC_STATUS, 
						SYNC_TIME, 
						SYNC_BY_ID, 
						SYNC_BY_NAME, 
						INSERT_TIME, 
						INSERT_BY_ID, 
						INSERT_BY_NAME, 
						UPDATE_TIME, 
						UPDATE_BY_ID, 
						UPDATE_BY_NAME, 
						TRANSACTION_TIME, 
						APP_VER, 
						STATUS 
				) 
				VALUES (
					'{$payload['ID']}', 
					'{$payload['HOLDING_NAME']}', 
					'{$payload['HOLDING_CODE']}', 
					'{$payload['REGION_NAME']}', 
					'{$payload['REGION_CODE']}', 
					'{$payload['COMPANY_NAME']}', 
					'{$payload['COMPANY_CODE']}', 
					'{$payload['BA_NAME']}', 
					'{$payload['BA_CODE']}', 
					'{$payload['NAB_REMARK']}', 
					'{$payload['IS_DOUBLE_HANDLING']}', 
					'{$payload['SYNC_STATUS']}', 
					'{$payload['SYNC_TIME']}', 
					'{$payload['SYNC_BY_ID']}', 
					'{$payload['SYNC_BY_NAME']}', 
					'{$payload['INSERT_TIME']}', 
					'{$payload['INSERT_BY_ID']}', 
					'{$payload['INSERT_BY_NAME']}', 
					'{$payload['UPDATE_TIME']}', 
					'{$payload['UPDATE_BY_ID']}', 
					'{$payload['UPDATE_BY_NAME']}', 
					'{$payload['TRANSACTION_TIME']}', 
					'{$payload['APP_VER']}', 
					'{$payload['STATUS']}'
				)";
			$this->eharvesting_oracle->statement($sql);
			$this->eharvesting_oracle->commit();
			return date( 'Y-m-d H:i:s' ).' - HRV_MSA_PROCESS_TR_DELIVERY_H - INSERT '.$payload['EBVTC'].' - SUCCESS '.PHP_EOL;
		}
		catch ( \Throwable $e ) {
			return date( 'Y-m-d H:i:s' ).' - HRV_MSA_PROCESS_TR_DELIVERY_H - INSERT '.$payload['EBVTC'].' - FAILED '.$e->getMessage().PHP_EOL;
		}
		catch ( \Exception $e ) {
			return date( 'Y-m-d H:i:s' ).' - HRV_MSA_PROCESS_TR_DELIVERY_H - INSERT '.$payload['EBVTC'].' - FAILED '.$e->getMessage().PHP_EOL;
		}
		
	}

	# PHP Kafka MOBILE_INSPECTION.TR_EBCC_VALIDATION_D
	public function RUN_INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D() {
		// Kafka Config
		$topic = "INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D";
		$Kafka = new RdKafka\Consumer();
		# $Kafka->setLogLevel(LOG_DEBUG);
		$Kafka->addBrokers( config('app.kafkahost') );
		$Topic = $Kafka->newTopic( $topic );
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
				if ( $last_offset !== false ){
					if ( $last_offset == null ) {
						if ( (int)$message->offset >= $last_offset ) {
							echo $this->INSERT_TR_EBCC_VALIDATION_D( $payload, (int)$message->offset );
						}	
					}
					else {
						if ( (int)$message->offset > $last_offset ) {
							echo $this->INSERT_TR_EBCC_VALIDATION_D( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TR_EBCC_VALIDATION_D
	public function INSERT_TR_EBCC_VALIDATION_D( $payload, $offset ) {

		echo 'Hehehehehe';

		$INSTM = ( (bool) strtotime( $payload['INSTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['INSTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$STIME = ( (bool) strtotime( $payload['STIME'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['STIME'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$check = collect( $this->db_mobile_ins->select( "
			SELECT 
				COUNT( * ) AS COUNT 
			FROM 
				TR_EBCC_VALIDATION_D
			WHERE
				EBCC_VALIDATION_CODE = '{$payload['EBVTC']}'
				AND ID_KUALITAS = '{$payload['IDKLT']}'
		" ) )->first();

		// Update offset payloads
		$this->db_mobile_ins->statement( "
			UPDATE 
				MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
			SET
				OFFSET = $offset,
				EXECUTE_DATE = SYSDATE
			WHERE
				TOPIC_NAME = 'INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D'
		" );
		$this->db_mobile_ins->commit();

		if ( $check->count == 0 ) {
			$sql = "
				INSERT INTO 
					MOBILE_INSPECTION.TR_EBCC_VALIDATION_D ( 
						EBCC_VALIDATION_CODE, 
						ID_KUALITAS, 
						JUMLAH, 
						INSERT_USER, 
						INSERT_TIME, 
						STATUS_SYNC, 
						SYNC_TIME 
					) 
				VALUES ( 
					'{$payload['EBVTC']}', 
					'{$payload['IDKLT']}', 
					'{$payload['JML']}', 
					'{$payload['INSUR']}', 
					$INSTM, 
					'{$payload['SSYNC']}',
					$STIME 
				)
			";
			
			try {
				$this->db_mobile_ins->statement( $sql );
				$this->db_mobile_ins->commit();
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D - INSERT '.$payload['EBVTC'].' - SUCCESS '.PHP_EOL;
			}
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D - INSERT '.$payload['EBVTC'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
			catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D - INSERT '.$payload['EBVTC'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
		else {
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D - INSERT '.$payload['EBVTC'].' - DUPLICATE '.PHP_EOL;
		}
	}

	# PHP Kafka MOBILE_INSPECTION.TM_USER_AUTH
	public function RUN_INS_MSA_AUTH_TM_USER_AUTH() {
		// Kafka Config
		$topic = "INS_MSA_AUTH_TM_USER_AUTH";
		$Kafka = new RdKafka\Consumer();
		# $Kafka->setLogLevel(LOG_DEBUG);
		$Kafka->addBrokers( config( 'app.kafkahost' ) );
		$Topic = $Kafka->newTopic( $topic );
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
				if ( $last_offset !== false ){
					if ( $last_offset == null ) {
						if ( (int)$message->offset >= $last_offset ) {
							echo $this->INSERT_TM_USER_AUTH( $payload, (int)$message->offset );
						}	
					}
					else {
						if ( (int)$message->offset > $last_offset ) {
							echo $this->INSERT_TM_USER_AUTH( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TM_USER_AUTH
	public function INSERT_TM_USER_AUTH( $payload, $offset ) {
		$INSTM = ( (bool) strtotime( $payload['INSTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['INSTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$UPTTM = ( (bool) strtotime( $payload['UPTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['UPTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$DLTTM = ( (bool) strtotime( $payload['DLTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['DLTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$sql = '';

		try {
			$check = collect( $this->db_mobile_ins->select( "
				SELECT
					COUNT( * ) AS COUNT 
				FROM 
					MOBILE_INSPECTION.TM_USER_AUTH
				WHERE
					USER_AUTH_CODE = '{$payload['URACD']}'
			" ) )->first();


			if ( $check->count == 0 ) {
				$sql = ( "INSERT INTO 
						MOBILE_INSPECTION.TM_USER_AUTH (
							USER_AUTH_CODE,
							EMPLOYEE_NIK,
							USER_ROLE,
							LOCATION_CODE,
							REF_ROLE,
							INSERT_USER,
							INSERT_TIME,
							UPDATE_USER,
							UPDATE_TIME,
							DELETE_USER,
							DELETE_TIME
						) 
					VALUES (
						'{$payload['URACD']}',
						'{$payload['EMNIK']}',
						'{$payload['URROL']}',
						'{$payload['LOCCD']}',
						'{$payload['RROLE']}',
						'{$payload['INSUR']}',
						$INSTM,
						'{$payload['UPTUR']}',
						$UPTTM,
						'{$payload['DLTUR']}',
						$DLTTM
				)" );
			}
			else {
				$sql = ( "UPDATE 
						MOBILE_INSPECTION.TM_USER_AUTH 
					SET
						EMPLOYEE_NIK = '{$payload['EMNIK']}',
						USER_ROLE = '{$payload['URROL']}',
						LOCATION_CODE = '{$payload['LOCCD']}',
						REF_ROLE = '{$payload['RROLE']}',
						INSERT_USER = '{$payload['INSUR']}',
						INSERT_TIME = $INSTM,
						UPDATE_USER = '{$payload['UPTUR']}',
						UPDATE_TIME = $UPTTM,
						DELETE_USER = '{$payload['DLTUR']}',
						DELETE_TIME = $DLTTM
					WHERE
						USER_AUTH_CODE = '{$payload['URACD']}'
				" );
			}

			$this->db_mobile_ins->statement( $sql );
			$this->db_mobile_ins->commit();

			// Update Kafka Offset Payloads			
			$this->db_mobile_ins->statement( "
				UPDATE 
					MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
				SET
					OFFSET = $offset,
					EXECUTE_DATE = SYSDATE
				WHERE
					TOPIC_NAME = 'INS_MSA_AUTH_TM_USER_AUTH'
			" );
			$this->db_mobile_ins->commit();
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_AUTH_TM_USER_AUTH - INSERT/UPDATE '.$payload['URACD'].' - SUCCESS '.PHP_EOL;
		} 
		catch ( \Throwable $e ) {
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_AUTH_TM_USER_AUTH - INSERT/UPDATE '.$payload['URACD'].' - FAILED '.$e->getMessage().PHP_EOL;
        }
        catch ( \Exception $e ) {
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_AUTH_TM_USER_AUTH - INSERT/UPDATE '.$payload['URACD'].' - FAILED '.$e->getMessage().PHP_EOL;
		}
	}

	# PHP Kafka MOBILE_INSPECTION.TR_FINDING
	public function RUN_INS_MSA_FINDING_TR_FINDING() {
		// Kafka Config
		$topic = "INS_MSA_FINDING_TR_FINDING";
		$Kafka = new RdKafka\Consumer();
		# $Kafka->setLogLevel(LOG_DEBUG);
		$Kafka->addBrokers( config('app.kafkahost') );
		$Topic = $Kafka->newTopic( $topic );
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
				if ( $last_offset !== false ){
					if ( $last_offset == null ) {
						if ( (int)$message->offset >= $last_offset ) {
							echo $this->INSERT_TR_FINDING( $payload, (int)$message->offset );
						}	
					}
					else {
						if ( (int)$message->offset > $last_offset ) {
							echo $this->INSERT_TR_FINDING( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TR_FINDING
	public function INSERT_TR_FINDING( $payload, $offset ) {
		$INSTM = ( (bool) strtotime( $payload['INSTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['INSTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$UPTTM = ( (bool) strtotime( $payload['UPTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['UPTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$DLTTM = ( (bool) strtotime( $payload['DLTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['DLTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$END_TIME = ( (bool) strtotime( $payload['END_TIME'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['END_TIME'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$DUE_DATE = ( (bool) strtotime( $payload['DUE_DATE'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['DUE_DATE'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$payload['PRGRS'] = ( $payload['PRGRS'] == null ? 0 : $payload['PRGRS'] );
		$payload['RTGVL'] = ( $payload['RTGVL'] == null ? 0 : $payload['RTGVL'] );


		if ( !isset( $payload['INSUR'] ) ) {
			$payload['INSUR'] = '';
		}

		$check = collect( $this->db_mobile_ins->select( "
			SELECT
				COUNT( * ) AS COUNT 
			FROM 
				TR_FINDING
			WHERE
				FINDING_CODE = '{$payload['FNDCD']}'
		" ) )->first();


		if ( $check->count == 0 ) {

			$sql = "INSERT INTO 
					MOBILE_INSPECTION.TR_FINDING (
						FINDING_CODE,
						WERKS,
						AFD_CODE,
						BLOCK_CODE,
						FINDING_CATEGORY,
						FINDING_DESC,
						FINDING_PRIORITY,
						DUE_DATE,
						ASSIGN_TO,
						PROGRESS,
						LAT_FINDING,
						LONG_FINDING,
						REFFERENCE_INS_CODE,
						INSERT_USER,
						INSERT_TIME,
						UPDATE_USER,
						UPDATE_TIME,
						DELETE_USER,
						DELETE_TIME,
						END_TIME,
						RATING_VALUE,
						RATING_MESSAGE
					) 
				VALUES (
					'{$payload['FNDCD']}',
					'{$payload['WERKS']}',
					'{$payload['AFD_CODE']}',
					'{$payload['BLOCK_CODE']}',
					'{$payload['FNDCT']}',
					'{$payload['FNDDS']}',
					'{$payload['FNDPR']}',
					$DUE_DATE,
					'{$payload['ASSTO']}',
					{$payload['PRGRS']},
					'{$payload['LATFN']}',
					'{$payload['LONFN']}',
					'{$payload['RFINC']}',
					'{$payload['INSUR']}',
					$INSTM,
					'{$payload['UPTUR']}',
					$UPTTM,
					'{$payload['DLTUR']}',
					$DLTTM,
					$END_TIME,
					{$payload['RTGVL']},
					'{$payload['RTGMS']}'
				)
			";

			try {
				$this->db_mobile_ins->statement( $sql );
				$this->db_mobile_ins->commit();
				
				// Update offset payloads
				$this->db_mobile_ins->statement( "
					UPDATE 
						MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
					SET
						OFFSET = $offset,
						EXECUTE_DATE = SYSDATE
					WHERE
						TOPIC_NAME = 'INS_MSA_FINDING_TR_FINDING'
				" );
				$this->db_mobile_ins->commit();
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_FINDING_TR_FINDING - INSERT '.$payload['FNDCD'].' - SUCCESS '.PHP_EOL;
			}
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_FINDING_TR_FINDING - INSERT '.$payload['FNDCD'].' - FAILED '.$e->getMessage().PHP_EOL;
	        }
	        catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_FINDING_TR_FINDING - INSERT '.$payload['FNDCD'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
		else {
			$sql = "UPDATE 
					MOBILE_INSPECTION.TR_FINDING 
				SET
					WERKS = '{$payload['WERKS']}',
					AFD_CODE = '{$payload['AFD_CODE']}',
					BLOCK_CODE = '{$payload['BLOCK_CODE']}',
					FINDING_CATEGORY = '{$payload['FNDCT']}',
					FINDING_DESC = '{$payload['FNDDS']}',
					FINDING_PRIORITY = '{$payload['FNDPR']}',
					DUE_DATE = $DUE_DATE,
					ASSIGN_TO = '{$payload['ASSTO']}',
					PROGRESS = {$payload['PRGRS']},
					LAT_FINDING = '{$payload['LATFN']}',
					LONG_FINDING = '{$payload['LONFN']}',
					REFFERENCE_INS_CODE = '{$payload['RFINC']}',
					UPDATE_USER = '{$payload['UPTUR']}',
					UPDATE_TIME = $UPTTM,
					END_TIME = $END_TIME,
					RATING_VALUE = {$payload['RTGVL']},
					RATING_MESSAGE = '{$payload['RTGMS']}'
				WHERE
					FINDING_CODE = '{$payload['FNDCD']}'
			";

			try {
				$this->db_mobile_ins->statement( $sql );
				$this->db_mobile_ins->commit();
				
				// Update offset payloads
				$this->db_mobile_ins->statement( "
					UPDATE 
						MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
					SET
						OFFSET = $offset,
						EXECUTE_DATE = SYSDATE
					WHERE
						TOPIC_NAME = 'INS_MSA_FINDING_TR_FINDING'
				" );
				$this->db_mobile_ins->commit();
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_FINDING_TR_FINDING - INSERT '.$payload['FNDCD'].' - SUCCESS '.PHP_EOL;
			}
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_FINDING_TR_FINDING - INSERT '.$payload['FNDCD'].' - FAILED '.$e->getMessage().PHP_EOL;
	        }
	        catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_FINDING_TR_FINDING - INSERT '.$payload['FNDCD'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
	}
	
	# PHP Kafka MOBILE_INSPECTION.TR_INSPECTION_GENBA
	public function RUN_INS_MSA_INSPECTION_TR_INSPECTION_GENBA() {
		// Kafka Config
		$topic = "INS_MSA_INS_TR_INSPECTION_GENBA";
		$Kafka = new RdKafka\Consumer();
		# $Kafka->setLogLevel(LOG_DEBUG);
		$Kafka->addBrokers( config('app.kafkahost') );
		$Topic = $Kafka->newTopic( $topic );
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
				if ( $last_offset !== false ){
					if ( $last_offset == null ) {
						if ( (int)$message->offset >= $last_offset ) {
							echo $this->INSERT_TR_INSPECTION_GENBA( $payload, (int)$message->offset );
						}	
					}
					else {
						if ( (int)$message->offset > $last_offset ) {
							echo $this->INSERT_TR_INSPECTION_GENBA( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TR_INSPECTION_GENBA
	public function INSERT_TR_INSPECTION_GENBA( $payload, $offset ) {
		$check = collect( $this->db_mobile_ins->select( "
			SELECT 
				COUNT( * ) AS COUNT 
			FROM 
				TR_INSPECTION_GENBA
			WHERE
				BLOCK_INSPECTION_CODE = '{$payload['BINCH']}'
				AND GENBA_USER = '{$payload['GNBUR']}'
		" ) )->first();

		if ( $check->count == 0 ) {

			$sql = "INSERT INTO 
					MOBILE_INSPECTION.TR_INSPECTION_GENBA (
						BLOCK_INSPECTION_CODE,
						GENBA_USER
					) 
				VALUES (
					'{$payload['BINCH']}',
					'{$payload['GNBUR']}'
				)
			";

			try {
				$this->db_mobile_ins->statement( $sql );
				$this->db_mobile_ins->commit();
				
				// Update offset payloads
				$this->db_mobile_ins->statement( "
					UPDATE 
						MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
					SET
						OFFSET = $offset,
						EXECUTE_DATE = SYSDATE
					WHERE
						TOPIC_NAME = 'INS_MSA_INS_TR_INSPECTION_GENBA'
				" );
				$this->db_mobile_ins->commit();
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_INSPECTION_GENBA - INSERT '.$payload['BINCH'].' - SUCCESS '.PHP_EOL;
			}
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_INSPECTION_GENBA - INSERT '.$payload['BINCH'].' - FAILED '.$e->getMessage().PHP_EOL;
	        }
	        catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_INSPECTION_GENBA - INSERT '.$payload['BINCH'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
		else {
			// Update offset payloads
			$this->db_mobile_ins->statement( "
				UPDATE 
					MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
				SET
					OFFSET = $offset,
					EXECUTE_DATE = SYSDATE
				WHERE
					TOPIC_NAME = 'INS_MSA_INS_TR_INSPECTION_GENBA'
			" );
			$this->db_mobile_ins->commit();
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_INSPECTION_GENBA - INSERT '.$payload['BINCH'].' - DUPLICATE '.PHP_EOL;
		}
	}

	# PHP Kafka MOBILE_INSPECTION.TR_BLOCK_INSPECTION_H
	public function RUN_INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_H() {

		// Kafka Config
		$topic = "INS_MSA_INS_TR_BLOCK_INSPECTION_H";
		$Kafka = new RdKafka\Consumer();
		# $Kafka->setLogLevel(LOG_DEBUG);
		$Kafka->addBrokers( config( 'app.kafkahost' ) );
		$Topic = $Kafka->newTopic( $topic );
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
				if ( $last_offset !== false ){
					if ( $last_offset == null ) {
						if ( (int)$message->offset >= $last_offset ) {
							echo $this->INSERT_TR_BLOCK_INSPECTION_H( $payload, (int)$message->offset );
						}	
					}
					else {
						if ( intval( $message->offset ) > intval( $last_offset ) ) {
							echo $this->INSERT_TR_BLOCK_INSPECTION_H( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TR_BLOCK_INSPECTION_H
	public function INSERT_TR_BLOCK_INSPECTION_H( $payload, $offset ) {

		$INSTM = ( (bool) strtotime( $payload['INSTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['INSTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$UPTTM = ( (bool) strtotime( $payload['UPTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['UPTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$DLTTM = ( (bool) strtotime( $payload['DLTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['DLTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$INSDT = ( (bool) strtotime( $payload['INSDT'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['INSDT'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$STIME = ( (bool) strtotime( $payload['STIME'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['STIME'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$STINS = ( (bool) strtotime( $payload['STINS'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['STINS'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$EDINS = ( (bool) strtotime( $payload['EDINS'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['EDINS'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$payload['INSSC'] = ( $payload['INSSC'] == null ? 0 : $payload['INSSC'] );
		$check = collect( $this->db_mobile_ins->select( "
			SELECT 
				COUNT( * ) AS COUNT 
			FROM 
				TR_BLOCK_INSPECTION_H
			WHERE
				BLOCK_INSPECTION_CODE = '{$payload['BINCH']}'
		" ) )->first();

		if ( $check->count == 0 ) {
			$sql = "INSERT INTO 
					MOBILE_INSPECTION.TR_BLOCK_INSPECTION_H (
						BLOCK_INSPECTION_CODE,
						WERKS,
						AFD_CODE,
						BLOCK_CODE,
						AREAL,
						INSPECTION_TYPE,
						INSPECTION_DATE,
						INSPECTION_SCORE,
						INSPECTION_RESULT,
						STATUS_SYNC,
						SYNC_TIME,
						START_INSPECTION,
						END_INSPECTION,
						LAT_START_INSPECTION,
						LONG_START_INSPECTION,
						LAT_END_INSPECTION,
						LONG_END_INSPECTION,
						INSERT_USER,
						INSERT_TIME,
						UPDATE_USER,
						UPDATE_TIME,
						DELETE_USER,
						DELETE_TIME
					) 
				VALUES (
					'{$payload['BINCH']}',
					'{$payload['WERKS']}',
					'{$payload['AFD_CODE']}',
					'{$payload['BLOCK_CODE']}',
					'{$payload['AREAL']}',
					'{$payload['INSTP']}',
					$INSDT,
					{$payload['INSSC']},
					'{$payload['INSRS']}',
					'{$payload['SSYNC']}',
					$STIME,
					$STINS,
					$EDINS,
					'{$payload['LATSI']}',
					'{$payload['LONSI']}',
					'{$payload['LATEI']}',
					'{$payload['LONEI']}',
					'{$payload['INSUR']}',
					$INSTM,
					'{$payload['UPTUR']}',
					$UPTTM,
					'{$payload['DLTUR']}',
					$DLTTM
				)
			";

			try {
				$this->db_mobile_ins->statement( $sql );
				$this->db_mobile_ins->commit();
				
				// Update offset payloads
				$this->db_mobile_ins->statement( "
					UPDATE 
						MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
					SET
						OFFSET = $offset,
						EXECUTE_DATE = SYSDATE
					WHERE
						TOPIC_NAME = 'INS_MSA_INS_TR_BLOCK_INSPECTION_H'
				" );
				$this->db_mobile_ins->commit();
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_H - INSERT '.$payload['BINCH'].' - SUCCESS '.PHP_EOL;
			}
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_H - INSERT '.$payload['BINCH'].' - FAILED '.$e->getMessage().PHP_EOL;
	        }
	        catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_H - INSERT '.$payload['BINCH'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
		else {
			// Update offset payloads
			$this->db_mobile_ins->statement( "
				UPDATE 
					MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
				SET
					OFFSET = $offset,
					EXECUTE_DATE = SYSDATE
				WHERE
					TOPIC_NAME = 'INS_MSA_INS_TR_BLOCK_INSPECTION_H'
			" );
			$this->db_mobile_ins->commit();
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_H - INSERT '.$payload['BINCH'].' - DUPLICATE '.PHP_EOL;
		}
	}

	# PHP Kafka MOBILE_INSPECTION.TR_BLOCK_INSPECTION_D
	public function RUN_INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_D() {
		// Kafka Config
		$topic = "INS_MSA_INS_TR_BLOCK_INSPECTION_D";
		$Kafka = new RdKafka\Consumer();
		# $Kafka->setLogLevel(LOG_DEBUG);
		$Kafka->addBrokers( config('app.kafkahost') );
		$Topic = $Kafka->newTopic( $topic );
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
				if ( $last_offset !== false ){
					if ( $last_offset == null ) {
						if ( (int)$message->offset >= $last_offset ) {
							echo $this->INSERT_TR_BLOCK_INSPECTION_D( $payload, (int)$message->offset );
						}	
					}
					else {
						if ( (int)$message->offset > $last_offset ) {
							echo $this->INSERT_TR_BLOCK_INSPECTION_D( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TR_BLOCK_INSPECTION_D
	public function INSERT_TR_BLOCK_INSPECTION_D( $payload, $offset ) {
		$INSTM = ( (bool) strtotime( $payload['INSTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['INSTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$UPTTM = ( (bool) strtotime( $payload['UPTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['UPTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$DLTTM = ( (bool) strtotime( $payload['DLTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['DLTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$STIME = ( (bool) strtotime( $payload['STIME'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['STIME'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$check = collect( $this->db_mobile_ins->select( "
			SELECT 
				COUNT( * ) AS COUNT 
			FROM 
				TR_BLOCK_INSPECTION_D
			WHERE
				BLOCK_INSPECTION_CODE = '{$payload['BINCH']}'
				AND BLOCK_INSPECTION_CODE_D = '{$payload['BINCH']}'
		" ) )->first();

		if ( $check->count == 0 ) {
			$sql = "INSERT INTO 
					MOBILE_INSPECTION.TR_BLOCK_INSPECTION_D (
						BLOCK_INSPECTION_CODE_D,
						BLOCK_INSPECTION_CODE,
						CONTENT_INSPECTION_CODE,
						VALUE,
						STATUS_SYNC,
						SYNC_TIME,
						INSERT_USER,
						INSERT_TIME,
						UPDATE_USER,
						UPDATE_TIME,
						DELETE_USER,
						DELETE_TIME
					) 
				VALUES (
					'{$payload['BINCD']}',
					'{$payload['BINCH']}',
					'{$payload['CTINC']}',
					'{$payload['VALUE']}',
					'{$payload['SSYNC']}',
					$STIME,
					'{$payload['INSUR']}',
					$INSTM,
					'{$payload['UPTUR']}',
					$UPTTM,
					'{$payload['DLTUR']}',
					$DLTTM
				)
			";

			try {
				$this->db_mobile_ins->statement( $sql );
				$this->db_mobile_ins->commit();
				
				// Update offset payloads
				$this->db_mobile_ins->statement( "
					UPDATE 
						MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
					SET
						OFFSET = $offset,
						EXECUTE_DATE = SYSDATE
					WHERE
						TOPIC_NAME = 'INS_MSA_INS_TR_BLOCK_INSPECTION_D'
				" );
				$this->db_mobile_ins->commit();
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_D - INSERT '.$payload['BINCH'].'-'.$payload['BINCD'].' - SUCCESS '.PHP_EOL;
			}
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_D - INSERT '.$payload['BINCH'].'-'.$payload['BINCD'].' - FAILED '.$e->getMessage().PHP_EOL;
	        }
	        catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_D - INSERT '.$payload['BINCH'].'-'.$payload['BINCD'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
		else {
			// Update offset payloads
			$this->db_mobile_ins->statement( "
				UPDATE 
					MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
				SET
					OFFSET = $offset,
					EXECUTE_DATE = SYSDATE
				WHERE
					TOPIC_NAME = 'INS_MSA_INS_TR_BLOCK_INSPECTION_H'
			" );
			$this->db_mobile_ins->commit();
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_BLOCK_INSPECTION_H - INSERT '.$payload['BINCH'].'-'.$payload['BINCD'].' - DUPLICATE '.PHP_EOL;
		}
	}

	# PHP Kafka MOBILE_INSPECTION.TR_TRACK_INSPECTION
	public function RUN_INS_MSA_INSPECTION_TR_TRACK_INSPECTION() {
		// Kafka Config
		$topic = "INS_MSA_INS_TR_TRACK_INSPECTION";
		$Kafka = new RdKafka\Consumer();
		# $Kafka->setLogLevel(LOG_DEBUG);
		$Kafka->addBrokers( config('app.kafkahost') );
		$Topic = $Kafka->newTopic( $topic );
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
				if ( $last_offset !== false ){
					if ( $last_offset == null ) {
						if ( (int)$message->offset >= $last_offset ) {
							echo $this->INSERT_TR_TRACK_INSPECTION( $payload, (int)$message->offset );
						}	
					}
					else {
						if ( (int)$message->offset > $last_offset ) {
							echo $this->INSERT_TR_TRACK_INSPECTION( $payload, (int)$message->offset );
						}	
					}
				}
			}
		}
	}

	# PHP Query MOBILE_INSPECTION.TR_TRACK_INSPECTION
	public function INSERT_TR_TRACK_INSPECTION( $payload, $offset ) {
		$INSTM = ( (bool) strtotime( $payload['INSTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['INSTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$UPTTM = ( (bool) strtotime( $payload['UPTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['UPTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$DLTTM = ( (bool) strtotime( $payload['DLTTM'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['DLTTM'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$DTTRK = ( (bool) strtotime( $payload['DTTRK'] ) == true ? "to_date('".date( 'YmdHis', strtotime( $payload['DTTRK'] ) )."','YYYYMMDDHH24MISS')" : "NULL" );
		$check = collect( $this->db_mobile_ins->select( "
			SELECT 
				COUNT( * ) AS COUNT 
			FROM 
				TR_BLOCK_INSPECTION_D
			WHERE
				BLOCK_INSPECTION_CODE = '{$payload['BINCH']}'
				AND BLOCK_INSPECTION_CODE_D = '{$payload['BINCH']}'
		" ) )->first();

		if ( $check->count == 0 ) {
			$sql = "INSERT INTO 
					MOBILE_INSPECTION.TR_TRACK_INSPECTION (
						TRACK_INSPECTION_CODE,
						BLOCK_INSPECTION_CODE,
						DATE_TRACK,
						LAT_TRACK,
						LONG_TRACK,
						INSERT_USER,
						INSERT_TIME,
						UPDATE_USER,
						UPDATE_TIME,
						DELETE_USER,
						DELETE_TIME
					) 
				VALUES (
					'{$payload['TRINC']}',
					'{$payload['BINCH']}',
					$DTTRK,
					'{$payload['LATTR']}',
					'{$payload['LONTR']}',
					'{$payload['INSUR']}',
					$INSTM,
					'{$payload['UPTUR']}',
					$UPTTM,
					'{$payload['DLTUR']}',
					$DLTTM
				)
			";

			try {
				$this->db_mobile_ins->statement( $sql );
				$this->db_mobile_ins->commit();
				
				// Update offset payloads
				$this->db_mobile_ins->statement( "
					UPDATE 
						MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
					SET
						OFFSET = $offset,
						EXECUTE_DATE = SYSDATE
					WHERE
						TOPIC_NAME = 'INS_MSA_INS_TR_TRACK_INSPECTION'
				" );
				$this->db_mobile_ins->commit();
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_TRACK_INSPECTION - INSERT '.$payload['BINCH'].'-'.$payload['TRINC'].' - SUCCESS '.PHP_EOL;
			}
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_TRACK_INSPECTION - INSERT '.$payload['BINCH'].'-'.$payload['TRINC'].' - FAILED '.$e->getMessage().PHP_EOL;
	        }
	        catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_TRACK_INSPECTION - INSERT '.$payload['BINCH'].'-'.$payload['TRINC'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
		else {
			// Update offset payloads
			$this->db_mobile_ins->statement( "
				UPDATE 
					MOBILE_INSPECTION.TM_KAFKA_PAYLOADS
				SET
					OFFSET = $offset,
					EXECUTE_DATE = SYSDATE
				WHERE
					TOPIC_NAME = 'INS_MSA_INS_TR_TRACK_INSPECTION'
			" );
			$this->db_mobile_ins->commit();
			return date( 'Y-m-d H:i:s' ).' - INS_MSA_INS_TR_TRACK_INSPECTION - INSERT '.$payload['BINCH'].'-'.$payload['TRINC'].' - DUPLICATE '.PHP_EOL;
		}
	}

	public function INSERT_TR_PREMI_INSPECTION( $payload, $offset ) {
		$check = collect( $this->db_mobile_ins->select( "
			SELECT 
				COUNT( * ) AS COUNT 
			FROM 
				TR_PREMI_INSPECTION
			WHERE
				BLOCK_INSPECTION_CODE = '{$payload['BINCH']}'
		" ) )->first();

	}

}