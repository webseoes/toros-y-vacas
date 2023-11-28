<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
/*
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/
Route::prefix('game')->group(
	function()
	{
		Route::any(	'/', 		
					'App\Http\Controllers\GameController@Init'
		)
		->name('stats');
		
		Route::any(	'/new',
					'App\Http\Controllers\GameController@NewGame'
		)
		->name('new_game');
		
		Route::any(	'/{game}',
					'App\Http\Controllers\GameController@GameActions'
		)
		->whereNumber('game')
		->name('show_validate');
	}
);

/*
	Si la ruta no existe entonces devuelve las estadísticas
	Esto se pude cambiar por una función que devuelva un error
*/
Route::fallback('App\Http\Controllers\GameController@Init');