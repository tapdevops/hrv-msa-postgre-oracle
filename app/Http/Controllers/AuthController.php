<?php

# Namespace
	namespace App\Http\Controllers;

# Default Laravel Vendor Setup
	use Illuminate\Http\Response;
	use Illuminate\Routing\Controller;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\File;
	use Illuminate\Support\Facades\Storage;
	use View;
	use Validator;
	use Redirect;
	use Session;
	use Config;
	use URL;
	use DateTime;
	use Maatwebsite\Excel\Facades\Excel;

# API
	use App\APISetup;
	use App\APIData as Data;

class AuthController extends Controller {

	public function __construct() {
		//$this->url_api_ins_msa_auth = APISetup::url()['msa']['ins']['auth'];
		//$this->url_api_ins_msa_hectarestatement = APISetup::url()['msa']['ins']['hectarestatement'];
		$this->url_api_ins_msa_auth = 'http://apisqa.tap-agri.com/mobileinspectionqa/ins-msa-qa-auth';
		$this->url_api_ins_msa_hectarestatement = 'http://apisqa.tap-agri.com/mobileinspectionqa/ins-msa-qa-hectarestatement';
	}
	
	#   		 									  		     ▁ ▂ ▄ ▅ ▆ ▇ █ Login - Form
	# -------------------------------------------------------------------------------------
	public function login_form() {
		return view( 'auth/form-login' );
	}

	#   		 									  		   ▁ ▂ ▄ ▅ ▆ ▇ █ Login - Proses
	# -------------------------------------------------------------------------------------
	public function login_proses( Request $req ) {

		$data['status'] = false;
		$data['message'] = '';

		$rules = array(
			'USERNAME' => 'required|regex:/(^([a-zA-Z.]+)(\d+)?$)/u|max:64',
			'PASSWORD' => 'required|max:64|'
		);

		$validator = Validator::make( Input::all(), $rules );

		if ( $validator->fails() ) {
			$data['message'] = 'Periksa kembali inputan Username/Password anda.';
			
		} else {
			$username = Input::get( 'USERNAME' );
			$password = Input::get( 'PASSWORD' );
			$client = new \GuzzleHttp\Client();
			$res = $client->request( 'POST', $this->url_api_ins_msa_auth.'/api/v2.0/auth/login', [
				'json' => [
					'username' => $username,
					'password' => $password
				]
			]);

			$logindata = json_decode( $res->getBody() );

			// Kondisi username dan password sudah terdaftar dalam sistem.
			if ( $logindata->status == true ) {
				$req->session()->put( [
					'IS_LOGIN' => true,
					'USERNAME' => $logindata->data->USERNAME,
					'NIK' => $logindata->data->NIK,
					'ACCESS_TOKEN' => $logindata->data->ACCESS_TOKEN,
					'JOB_CODE' => $logindata->data->JOB_CODE,
					'USER_AUTH_CODE' => $logindata->data->USER_AUTH_CODE,
					'REFFERENCE_ROLE' => $logindata->data->REFFERENCE_ROLE,
					'USER_ROLE' => $logindata->data->USER_ROLE,
					'LOCATION_CODE' => $logindata->data->LOCATION_CODE
				] );
				$data['status'] = true;
				$data['message'] = 'Login berhasil, mengarahkan ke halaman utama...';
			}
			// Kondisi username dan password salah atau tidak terdaftar dalam sistem.
			else {
				$data['message'] = $logindata->message;
			}
		}

		return response()->json( $data );

	}

	#   		 									  		           ▁ ▂ ▄ ▅ ▆ ▇ █ Logout
	# -------------------------------------------------------------------------------------
	public function logout() {
		session()->flush();
		return redirect( 'login' );
	}
}