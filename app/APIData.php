<?php

/**
 * Data Class
 *
 * @package  Laravel
 * @author   Ferdinand
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Session;
use App\APISetup;

class APIData extends Model
{

	#   		 									  			  ▁ ▂ ▄ ▅ ▆ ▇ █ CONSTRUCTOR
	# -------------------------------------------------------------------------------------
	public static function url($id)
	{
		switch ($id) {
				# API INSPEKSI - MSA - AUTH
			case 'url_api_ins_msa_auth':
				return APISetup::url()['msa']['ins']['auth'];
				break;
				# API INSPEKSI - MSA - FINDING
			case 'url_api_ins_msa_finding':
				return APISetup::url()['msa']['ins']['finding'];
				break;
				# API INSPEKSI - MSA - INSPECTION
			case 'url_api_ins_msa_inspection':
				return APISetup::url()['msa']['ins']['inspection'];
				break;
				# API INSPEKSI - MSA - EBCC VALIDATION
			case 'url_api_ins_msa_ebccvalidation':
				return APISetup::url()['msa']['ins']['ebccvalidation'];
				break;
				# API INSPEKSI - MSA - HECTARE STATEMENT
			case 'url_api_ins_msa_hectarestatement':
				return APISetup::url()['msa']['ins']['hectarestatement'];
				break;
				# API INSPEKSI - MSA - REPORT
			case 'url_api_ins_msa_report':
				return APISetup::url()['msa']['ins']['report'];
				break;
				# DEFAULT
			case 'url_api_ins_msa_mivalidation':
				return APISetup::url()['msa']['ins']['mivalidation'];
				break;
				# DEFAULT
			default:
				return '';
				break;
		}
	}

	# 												    	  ▁ ▂ ▄ ▅ ▆ ▇ █ CATEGORY - Find
	# -------------------------------------------------------------------------------------
	public static function category_find($parameter = '')
	{

		$url = self::url('url_api_ins_msa_auth') . '/api/category' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 												    	   ▁ ▂ ▄ ▅ ▆ ▇ █ CONTENT - Find
	# -------------------------------------------------------------------------------------
	public static function content_find($parameter = '')
	{
		$url = self::url('url_api_ins_msa_auth') . '/api/content' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	#										   ▁ ▂ ▄ ▅ ▆ ▇ █ Hectare Statement Block - Find
	# -------------------------------------------------------------------------------------
	public static function hectarestatement_block_find($parameter = '')
	{
		$url = self::url('url_api_ins_msa_hectarestatement') . '/block/q?WERKS=' . $parameter;

		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 												    	  ▁ ▂ ▄ ▅ ▆ ▇ █ KUALITAS - Find
	# -------------------------------------------------------------------------------------
	public static function kualitas_find($parameter = '')
	{
		$url = self::url('url_api_ins_msa_ebccvalidation') . '/ebcc/kualitas' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 												   		   ▁ ▂ ▄ ▅ ▆ ▇ █ MODULES - Find
	# -------------------------------------------------------------------------------------
	#
	# ╔══════════════════╤════════════════════════════════════════════════════════════════╗
	# ║ Tanpa Parameter  │ Mengambil seluruh data tanpa pengecualian          			  ║
	# ╠══════════════════╪════════════════════════════════════════════════════════════════╣
	# ║ Dengan Parameter │ Mengambil data berdasarkan Parameter, contohnya :			  ║
	# ║ 				 │ 1. /api/modules?MODULE_CODE=02.00.00.00.00 					  ║
	# ║ 				 │ 2. /api/modules?MODULE_CODE=02.00.00.00.00&INSERT_USER=TAC00004║
	# ║ 				 │ 3. /api/modules/02.00.00.00.00 								  ║
	# ╚══════════════════╧════════════════════════════════════════════════════════════════╝
	#
	public static function modules_find($parameter = '')
	{ // Parameter = Module Code
		$url = self::url('url_api_ins_msa_auth') . '/api/modules/' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 												    ▁ ▂ ▄ ▅ ▆ ▇ █ MODULES - Find By Job
	# -------------------------------------------------------------------------------------
	#
	# ╔══════════════════╤════════════════════════════════════════════════════════════════╗
	# ║ Tanpa Parameter  │ Mengambil data berdasarkan USER_AUTH_CODE          			  ║
	# ╠══════════════════╪════════════════════════════════════════════════════════════════╣
	# ║ Dengan Parameter │ Mengambil data berdasarkan Parameter (CEO, EM, KEPALA_KEBUN,   ║
	# ║ 			     │ ASISTEN_LAPANGAN, dan z-lain.   						      ║
	# ╚══════════════════╧════════════════════════════════════════════════════════════════╝
	#
	public static function modules_find_by_job($parameter = '')
	{ // Parameter = User Role
		$url = self::url('url_api_ins_msa_auth') . '/api/modules/by-job' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 												    	 ▁ ▂ ▄ ▅ ▆ ▇ █ PARAMETER - Find
	# -------------------------------------------------------------------------------------
	public static function parameter_find($parameter = '')
	{ // WERKS_AFD_BLOCK_CODE
		$url = self::url('url_api_ins_msa_auth') . '/api/parameter' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 												    	      ▁ ▂ ▄ ▅ ▆ ▇ █ USER - Find
	# -------------------------------------------------------------------------------------
	public static function user_find($parameter = '')
	{
		$url = self::url('url_api_ins_msa_auth') . '/api/user' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 												    	  ▁ ▂ ▄ ▅ ▆ ▇ █ USER - Find One
	# -------------------------------------------------------------------------------------
	public static function user_find_one($id = '', $token = 'session')
	{

		$data['items'] = array();
		$data['items']['USER_AUTH_CODE'] = '';
		$data['items']['EMPLOYEE_NIK'] = '';
		$data['items']['USER_ROLE'] = '';
		$data['items']['LOCATION_CODE'] = '';
		$data['items']['REF_ROLE'] = '';
		$data['items']['JOB'] = '';
		$data['items']['FULLNAME'] = '';

		if ($id != '') {
			$url = self::url('url_api_ins_msa_auth') . '/api/user/' . $id;
			$client = ($token == 'session' ? APISetup::ins_rest_client('GET', $url) : APISetup::ins_rest_client_manual('GET', $url));
			if ($client['status'] == true) {
				if (isset($client['data'])) {
					$data['items']['USER_AUTH_CODE'] = $client['data']['USER_AUTH_CODE'];
					$data['items']['EMPLOYEE_NIK'] = $client['data']['EMPLOYEE_NIK'];
					$data['items']['USER_ROLE'] = $client['data']['USER_ROLE'];
					$data['items']['LOCATION_CODE'] = $client['data']['LOCATION_CODE'];
					$data['items']['REF_ROLE'] = $client['data']['REF_ROLE'];
					$data['items']['JOB'] = $client['data']['JOB'];
					$data['items']['FULLNAME'] = $client['data']['FULLNAME'];
				}
			}
		}

		return $data;
	}

	# 											    ▁ ▂ ▄ ▅ ▆ ▇ █ USER AUTHORIZATION - Find
	# -------------------------------------------------------------------------------------
	public static function user_authorization_find($parameter = '')
	{
		$url = self::url('url_api_ins_msa_auth') . '/api/user-authorization' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	#   		 									   ▁ ▂ ▄ ▅ ▆ ▇ █ REFFERENCE ROLE - Find
	# -------------------------------------------------------------------------------------
	public static function refrole_find()
	{

		$items = array(
			array("ID" => "NATIONAL", "TEXT" => "NATIONAL"),
			array("ID" => "REGION_CODE", "TEXT" => "REGION CODE"),
			array("ID" => "COMP_CODE", "TEXT" => "COMP CODE"),
			array("ID" => "BA_CODE", "TEXT" => "BA CODE"),
			array("ID" => "AFD_CODE", "TEXT" => "AFD CODE"),
		);

		return $items;
	}

	#   		 								   ▁ ▂ ▄ ▅ ▆ ▇ █ REFFERENCE ROLE - Find One
	# -------------------------------------------------------------------------------------
	public static function refrole_find_one($id)
	{

		$data = self::refrole_find();
		$search = array_search($id, array_column($data, 'ID'));
		$items = $data[$search];

		return $items;
	}

	public static function web_report_ebcc_validation_find($parameter = '')
	{

		$data['items'] = array();
		$url = self::url('url_api_ins_msa_ebccvalidation') . '/api/v1.1/report/web/per-baris' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);

		return $client;
	}

	# 									   ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - INSPEKSI BARIS - Find
	# -------------------------------------------------------------------------------------
	public static function web_report_class_block_find($parameter)
	{

		$data['items'] = array();
		$url = self::url('url_api_ins_msa_report') . '/api/report/class-block/periode' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);

		if (isset($client['status']) && $client['status'] == true) {
			if (count($client['data']) > 0) {
				$data['items'] = $client['data'];
			}
		}

		return $data;
	}

	# 										      ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - FINDING - Find
	# -------------------------------------------------------------------------------------
	public static function web_report_finding_find($query = array())
	{

		$url_query = '';
		if (!empty($query) > 0) {
			$i = 1;
			foreach ($query as $key => $value) {
				if ($value != '') :
					if ($i == 1) {
						$url_query .= $key . '=' . $value;
					} else {
						$url_query .= '&' . $key . '=' . $value;
					}
				endif;

				$i++;
			}
		}

		$data['items'] = array();

		$url = self::url('url_api_ins_msa_finding') . '/api/v1.1/report/web/finding/all?' . $url_query;

		// print $url;
		// dd();
		$client = APISetup::ins_rest_client('GET', $url);

		if ($client['status'] == true) {
			if (count($client['data']) > 0) {
				$data['items'] = $client['data'];
			}
		}

		return $data;
	}

	# 									   ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - INSPEKSI BARIS - Find
	# -------------------------------------------------------------------------------------
	public static function web_report_inspection_baris_find($parameter)
	{

		$data['items'] = array();
		$url = self::url('url_api_ins_msa_report') . '/api/report/inspection-baris' . $parameter;
		// print $url;dd();
		$client = APISetup::ins_rest_client('GET', $url);

		if (isset($client['status']) && $client['status'] == true) {
			if (count($client['data']) > 0) {
				$data['items'] = $client['data'];
			}
		}

		return $data;
	}

	# 							     ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - INSPEKSI BARIS VALID - Find
	# -------------------------------------------------------------------------------------
	public static function web_report_inspection_baris_valid_find($parameter)
	{

		$data['items'] = array();
		$url = self::url('url_api_ins_msa_report') . '/api/report/inspection-baris-valid' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);

		if (isset($client['status']) && $client['status'] == true) {
			if (count($client['data']) > 0) {
				$data['items'] = $client['data'];
			}
		}

		return $data;
	}

	# 										     ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - INSPEKSI - Find
	# -------------------------------------------------------------------------------------
	#
	# Parameter $token, jika diisi maka akan menggunakan token isian ini. Jika dikosongi, 
	# akan menggunakan session.
	#
	public static function web_report_inspection_find($query = array(), $token = 'session')
	{

		$url_query = '';
		if (!empty($query) > 0) {
			$i = 1;
			foreach ($query as $key => $value) {
				if ($value != '') :
					if ($i == 1) {
						$url_query .= $key . '=' . $value;
					} else {
						$url_query .= '&' . $key . '=' . $value;
					}
				endif;

				$i++;
			}
		}

		$data['items'] = array();
		$url = self::url('url_api_ins_msa_inspection') . '/api/v1.0/report?' . $url_query;
		$client = ($token == 'session' ? APISetup::ins_rest_client('GET', $url) : APISetup::ins_rest_client_manual('GET', $url));
		// print $url;
		if (isset($client['status']) && $client['status'] == true) {
			if (count($client['data']) > 0) {
				$data['items'] = $client['data'];
			}
		}

		return $data;
	}

	# 										  ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - INSPEKSI - Content
	# -------------------------------------------------------------------------------------
	public static function web_report_inspection_content_find($token = 'session')
	{
		// GROUP_CATEGORY=INSPEKSI
		// GROUP_CATEGORY=FINDING
		$url = self::url('url_api_ins_msa_auth') . '/api/web-report/inspection/content-code';
		$client = ($token == 'session' ? APISetup::ins_rest_client('GET', $url) : APISetup::ins_rest_client_manual('GET', $url));
		// print $url;
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 											 ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - KRITERIA - Find One
	# -------------------------------------------------------------------------------------
	public static function web_report_inspection_kriteria_findone($parameter)
	{
		$url = self::url('url_api_ins_msa_auth') . '/api/web-report/inspection/kriteria/' . $parameter;
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 											 ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - KRITERIA - Find
	# -------------------------------------------------------------------------------------
	public static function web_report_inspection_kriteria_find()
	{
		$url = self::url('url_api_ins_msa_auth') . '/api/kriteria/';
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}

	# 											 ▁ ▂ ▄ ▅ ▆ ▇ █ WEB REPORT - INSPEKSI - Find
	# -------------------------------------------------------------------------------------
	public static function web_report_land_use_findone($parameter, $token = 'session')
	{ // WERKS_AFD_BLOCK_CODE
		$url = self::url('url_api_ins_msa_hectarestatement') . '/report/land-use/' . $parameter;
		$client = ($token == 'session' ? APISetup::ins_rest_client('GET', $url) : APISetup::ins_rest_client_manual('GET', $url));
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}
	
	
	# 											 ▁ ▂ ▄ ▅ ▆ ▇ █ WEB VALIDASI - HEADER - Find
	# -------------------------------------------------------------------------------------
	public static function val_header_find()
	{
		$url = self::url('url_api_ins_msa_mivalidation') . '/filter/all';
		$client = APISetup::ins_rest_client('GET', $url);
		$data = [];
		if ($client['status'] == true) {
			if (isset($client['data'])) {
				$data = $client['data'];
			}
		}
		return $data;
	}



}
