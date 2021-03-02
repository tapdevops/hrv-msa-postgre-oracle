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

		if(ISSET($payload['ID']))
		{
			$HRV_MSA_PROCESS_TR_DELIVERY_H = array('HOLDING_NAME','HOLDING_CODE','REGION_NAME','REGION_CODE','COMPANY_NAME','COMPANY_CODE','BA_NAME','BA_CODE','NAB_CODE',
												   'NAB_REMARK','IS_DOUBLE_HANDLING','SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID',
												   'INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID','UPDATE_BY_NAME','TRANSACTION_TIME','APP_VER','STATUS');
			$check['HRV_MSA_PROCESS_TR_DELIVERY_H'] = array_fill_keys($HRV_MSA_PROCESS_TR_DELIVERY_H, true);
			$HRV_MSA_PROCESS_TR_DELIVERY_USER_D = array('VEHICLE_ID','USERNAME','USER_FULLNAME','USER_NIK','EMPLOYEE_ID','JOB_CODE','DELIVERY_USER_TYPE','DELIVERY_USER_PARENT',
														'SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID','INSERT_BY_NAME','UPDATE_TIME',
														'UPDATE_BY_ID','UPDATE_BY_NAME','USER_AFDELING');
			$check['HRV_MSA_PROCESS_TR_DELIVERY_USER_D'] = array_fill_keys($HRV_MSA_PROCESS_TR_DELIVERY_USER_D, true);
			$HRV_MSA_PROCESS_TR_DELIVERY_VEHICLE_D = array('DELIVERY_ID','NAB_CODE','VEHICLE_CODE','VEHICLE_REMARK','VEHICLE_TYPE','VEHICLE_LICENSE_PLATE','IS_TRANSIT',
														   'SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID','INSERT_BY_NAME','UPDATE_TIME',
														   'UPDATE_BY_ID','UPDATE_BY_NAME');
			$check['HRV_MSA_PROCESS_TR_DELIVERY_VEHICLE_D'] = array_fill_keys($HRV_MSA_PROCESS_TR_DELIVERY_VEHICLE_D, true);
			$HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_H = array('HOLDING_NAME','HOLDING_CODE','REGION_NAME','REGION_CODE','COMPANY_NAME','COMPANY_CODE','BA_NAME','BA_CODE',
														   'AFDELING_NAME','AFDELING_CODE','BLOCK_NAME','BLOCK_CODE','TPH_NAME','TPH_CODE','TPH_LAT','TPH_LNG','TPH_REMARK',
														   'BCP_REMARK','BERITA_ACARA','ATTACHMENT','HARVEST_TYPE','HARVEST_ACTIVITY_CODE','HARVEST_ACTIVITY_LAT',
														   'HARVEST_ACTIVITY_LNG','HARVEST_ACTIVITY_STATUS','TOLERANCE_RADIUS','NAB_CODE','NAB_TIME','DELIVERY_TICKET_CODE',
														   'DELIVERY_TICKET_REMARK','IS_LHM_PRINTED','LHM_PRINTED_TIME','LHM_PRINTED_BY_ID','LHM_PRINTED_BY_NAME','SYNC_STATUS',
														   'SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID','INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID',
														   'UPDATE_BY_NAME','TRANSACTION_TIME','APP_VER','BERITA_ACARA_DELETE','DELETE_TIME','BJR_VALUE','STATUS','ATTACHMENT_NAME',
														   'IS_VALIDATED','TPH_DISTANCE','PROFILE_NAME');
			$check['HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_H'] = array_fill_keys($HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_H, true);
			$HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_ITEM_D = array('HARVEST_ACTIVITY_ID','HARVEST_ACTIVITY_CODE','CATEGORY_PARENT','CATEGORY_NAME','CATEGORY_CODE','CATEGORY_TYPE',
																'CATEGORY_VALUE_TYPE','CATEGORY_UOM','CATEGORY_OPERATOR','CATEGORY_IS_PENALTY','CATEGORY_SORT',
																'HARVEST_ACTIVITY_ITEM_VALUE','SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID',
																'INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID','UPDATE_BY_NAME','TRANSACTION_TIME','CATEGORY_IS_HARVESTED',
																'CATEGORY_IS_DELIVERED','CATEGORY_IS_PAID','HARVEST_ACTIVITY_ITEM_TYPE','JOB_CODE','JOB_LEVEL');
			$check['HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_ITEM_D'] = array_fill_keys($HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_ITEM_D, true);
			$HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D = array('HARVEST_ACTIVITY_ID','HARVEST_ACTIVITY_CODE','USERNAME','USER_FULLNAME','USER_NIK','JOB_CODE',
																'ACTIVITY_USER_PARENT','ACTIVITY_USER_TYPE','IS_BORROWED','SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME',
																'INSERT_TIME','INSERT_BY_ID','INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID','UPDATE_BY_NAME','TRANSACTION_TIME',
																'AAP_TIME','USER_AFDELING','USER_ESTATE');
			$check['HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D'] = array_fill_keys($HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D, true);
			$HRV_MSA_PROCESS_TR_HARVEST_REPORT_H = array('HOLDING_NAME','HOLDING_CODE','REGION_NAME','REGION_CODE','COMPANY_NAME','COMPANY_CODE','BA_NAME','BA_CODE',
														 'AFDELING_NAME','AFDELING_CODE','SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID',
														 'INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID','UPDATE_BY_NAME','TRANSACTION_TIME','APP_VER','STATUS');
			$check['HRV_MSA_PROCESS_TR_HARVEST_REPORT_H'] = array_fill_keys($HRV_MSA_PROCESS_TR_HARVEST_REPORT_H, true);
			$HRV_MSA_PROCESS_TR_HARVEST_REPORT_ITEM_D = array('REPORT_ID','BLOCK_NAME','BLOCK_CODE','HARVEST_ACRE','CATEGORY_PARENT','CATEGORY_NAME','CATEGORY_CODE',
															  'CATEGORY_TYPE','CATEGORY_UOM','CATEGORY_SORT','REPORT_ITEM_VALUE','SYNC_STATUS','SYNC_TIME','SYNC_BY_ID',
															  'SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID','INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID','UPDATE_BY_NAME',
															  'BLOCK_ACRE','CATEGORY_IS_HARVESTED','CATEGORY_IS_DELIVERED','CATEGORY_IS_PAID','CATEGORY_IS_PENALTY');
			$check['HRV_MSA_PROCESS_TR_HARVEST_REPORT_ITEM_D'] = array_fill_keys($HRV_MSA_PROCESS_TR_HARVEST_REPORT_ITEM_D, true);
			$HRV_MSA_PROCESS_TR_HARVEST_REPORT_USER_D = array('REPORT_ID','USERNAME','USER_FULLNAME','USER_NIK','EMPLOYEE_ID','JOB_CODE','REPORT_USER_TYPE',
															  'REPORT_USER_PARENT','SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID',
															  'INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID','UPDATE_BY_NAME','USER_AFDELING');
			$check['HRV_MSA_PROCESS_TR_HARVEST_REPORT_USER_D'] = array_fill_keys($HRV_MSA_PROCESS_TR_HARVEST_REPORT_USER_D, true);
			$HRV_MSA_PROCESS_TR_LHM_REPORT_H = array('HOLDING_NAME','HOLDING_CODE','REGION_NAME','REGION_CODE','COMPANY_NAME','COMPANY_CODE','BA_NAME','BA_CODE',
													 'AFDELING_NAME','AFDELING_CODE','BLOCK_NAME','BLOCK_CODE','TPH_NAME','TPH_CODE','HARVEST_ACTIVITY_CODE','KRANI_NIK',
													 'KRANI_FULLNAME','MANDOR_NIK','MANDOR_FULLNAME','PEMANEN_NIK','PEMANEN_FULLNAME','LUASAN','IS_TPH_MANUAL',
													 'INVALID_TPH_RADIUS','CUSTOMER','IS_VALIDATED','SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME',
													 'INSERT_BY_ID','INSERT_BY_NAME','UPDATE_TIME','UPDATE_BY_ID','UPDATE_BY_NAME','TRANSACTION_TIME','STATUS','NAB_CODE',
													 'VEHICLE_LICENSE_PLATE','NAB_ID','IS_PRINTED','KRANI_AFDELING','MANDOR_AFDELING','PEMANEN_AFDELING','HARVEST_ACTIVITY_ID',
													 'NAB_STATUS','NAB_TIME','NAB_BY_ID','NAB_BY_NAME','AAP_STATUS','AAP_TIME','AAP_BY_ID','AAP_BY_NAME','TPH_DISTANCE',
													 'BJR_VALUE','HARVEST_ACTIVITY_STATUS','MIN_SAMPLING_ASLAP','MIN_SAMPLING_KABUN','VEHICLE_CODE','SUPIR_AFDELING','SUPIR_NIK',
													 'SUPIR_FULLNAME','PEMANEN_ESTATE','PROFILE_NAME','USER_GANDENG','TUKANG_MUAT','VEHICLE_TRANSIT','NAB_DELIVERY_DATE');
			$check['HRV_MSA_PROCESS_TR_LHM_REPORT_H'] = array_fill_keys($HRV_MSA_PROCESS_TR_LHM_REPORT_H, true);
			$HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D = array('LHM_ID','CATEGORY_NAME','CATEGORY_CODE','CATEGORY_TYPE','CATEGORY_UOM','LHM_ITEM_VALUE','LHM_ITEM_TYPE',
														  'SYNC_STATUS','SYNC_TIME','SYNC_BY_ID','SYNC_BY_NAME','INSERT_TIME','INSERT_BY_ID','INSERT_BY_NAME','UPDATE_TIME',
														  'UPDATE_BY_ID','UPDATE_BY_NAME','STATUS','NAB_STATUS','NAB_TIME','NAB_BY_ID','NAB_BY_NAME','AAP_STATUS','AAP_TIME',
														  'AAP_BY_ID','AAP_BY_NAME','CATEGORY_IS_PAID','CATEGORY_IS_HARVESTED','CATEGORY_IS_DELIVERED','CATEGORY_IS_PENALTY',
														  'JOB_CODE','JOB_LEVEL');
			$check['HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D'] = array_fill_keys($HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D, true);

			try {
					$insert_into = ''; $insert_value = ''; $update_set = '';
					foreach ($payload as $field => $value) 
					{	
						if(ISSET($check[$topic][$field]) || $field=='ID'){
							$value = str_replace("'","`",$value);
							$insert_into .= $insert_into==''?$field:','.$field;
							$insert_value .= $insert_value==''?"'".$value."'":",'".$value."'";
							$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
						}
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
			catch ( \Throwable $e ) {
				return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
			catch ( \Exception $e ) {
				return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$payload['ID'].' - FAILED '.$e->getMessage().PHP_EOL;
			}
		}
		else 
		{
			return date( 'Y-m-d H:i:s' ).' - $topic - INSERT ( NO ID ) - FAILED '.PHP_EOL;
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
		$denda_panen = array('COMP_CODE','PROFILE_NAME','NO_BCC','KODE_DENDA_PANEN','JUMLAH','EXPORT_STATUS','EXPORT_TIMESTAMP',
							 'POST_STATUS','POST_TIMESTAMP');
		$ebcc = array('COMP_CODE','PROFILE_NAME','NO_BCC','NIK_PEMANEN','TANGGAL','NO_TPH','CUSTOMER','PLANT','AFDELING','BLOCK',
					  'HECTARE','TBS_BAYAR','BRONDOLAN','TBS_KIRIM','NIK_MANDOR','NIK_KRANI_BUAH','FLAG_GANDENG','NIK_GANDENG',
					  'COMP_CODE_KARY','PROFILE_NAME_KARY','AFDELING_KARY','EXPORT_STATUS','EXPORT_TIMESTAMP','POST_STATUS',
					  'POST_TIMESTAMP','TBS_PANEN');
		$nab = array('COMP_CODE','PROFILE_NAME','ESTATE_CODE','NO_NAB','NO_BCC','TANGGAL','NO_POLISI','EXPORT_STATUS','EXPORT_TIMESTAMP',
					'POST_STATUS','POST_TIMESTAMP','ID_NAB_TGL','ID_NAB_TANGGAL');
		$check['HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN'] = array_fill_keys($denda_panen, true);
		$check['HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB'] = array_fill_keys($nab, true);
		$check['HRV_MSA_PROCESS_T_STATUS_TO_SAP_EBCC'] = array_fill_keys($ebcc, true);
		try {
				$insert_into = ''; $insert_value = ''; $update_set = ''; $where = '';$update_conditional_set = ''; 
				foreach ($payload as $field => $value) 
				{
					if($field!='POST_STATUS' && $field!='POST_TIMESTAMP')
					{
						if($topic=='HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN' && ISSET($payload['JUMLAH']) && ISSET($check[$topic][$field])){
							if($field=='COMP_CODE' || $field=='PROFILE_NAME' || $field=='NO_BCC' || $field=='KODE_DENDA_PANEN'){
								$where .= $where==''?$field."='".$value."'":" AND ".$field."='".$value."'";
							}else {
								$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
							}
							$insert_into .= $insert_into==''?$field:','.$field;
							$insert_value .= $insert_value==''?"'".$value."'":",'".$value."'";
						}
						if($topic=='HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB' && ISSET($check[$topic][$field])){
							if($field=='ID_NAB_TANGGAL')
							{
								$field = 'ID_NAB_TGL';
							}
							if($field=='COMP_CODE' || $field=='PROFILE_NAME' || $field=='ESTATE_CODE' || $field=='NO_NAB' || $field=='NO_BCC' || $field=='TANGGAL'){
								$where .= $where==''?$field."='".$value."'":" AND ".$field."='".$value."'";
							}else {
								$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
							}
							$insert_into .= $insert_into==''?$field:','.$field;
							$insert_value .= $insert_value==''?"'".$value."'":",'".$value."'";
						}
						if($topic=='HRV_MSA_PROCESS_T_STATUS_TO_SAP_EBCC' && ISSET($check[$topic][$field])){
							if($field=='COMP_CODE' || $field=='PROFILE_NAME' || $field=='NO_BCC'){
								$where .= $where==''?$field."='".$value."'":" AND ".$field."='".$value."'";
							}else {
								$update_set .= $update_set==''?$field."='".$value."'":",".$field."='".$value."'";
							}
							if($field=='EXPORT_STATUS' || $field=='EXPORT_TIMESTAMP')
							{
								$update_conditional_set .= $update_conditional_set==''?$field."='".$value."'":",".$field."='".$value."'";
							}
							$insert_into .= $insert_into==''?$field:','.$field;
							$insert_value .= $insert_value==''?"'".$value."'":",'".$value."'";
						}
					}
				}
				if($update_conditional_set!='')
				{
					$update_conditional_set = "UPDATE EHARVESTING.T_STATUS_TO_SAP_DENDA_PANEN SET $update_conditional_set WHERE $where;";
				}
				if($insert_into!='' && $update_set !='')
				{
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
									$update_conditional_set
								ELSE
									INSERT INTO EHARVESTING.$table ($insert_into) 
									VALUES ($insert_value);
								END IF;
							END;";
					$this->eharvesting_oracle->statement($sql);
					$this->eharvesting_oracle->commit();
				}
				// return date( 'Y-m-d H:i:s' )." - $topic - INSERT - SUCCESS '.PHP_EOL;
		}
		catch ( \Throwable $e ) {
			return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$topic.' - FAILED '.$e->getMessage().PHP_EOL;
		}
		catch ( \Exception $e ) {
			return date( 'Y-m-d H:i:s' )." - $topic - INSERT ".$topic.' - FAILED '.$e->getMessage().PHP_EOL;
		}
	}
}