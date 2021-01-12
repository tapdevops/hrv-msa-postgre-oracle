<?php

use App\ValidasiHeader as Validate;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get('header', function(){
//     return Validate::data()->paginate(10);

// });

// Route::get('header/{date}', function(){
//     return Validate::validasi_header($date);

// });

// Route::resource('/filter', 'ValidationController@getAllfilter');
// Route::get('/filter/all', 'ValidationController@getAll');