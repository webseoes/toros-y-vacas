<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
	private $validationMessages	= [
									'required'				=> 'The -:attribute- field is required.',
									'string'				=> 'The -:attribute- field must be a string.',
									'integer'				=> 'The -:attribute- field must be an integer.',
									'min'					=> 'The -:attribute- has minimum of :min.',
									'max'					=> 'The -:attribute- has maximum of :max.',
								];
	private $errorMessages		= [
									'method_not_allowed'	=> 'Method nopt allowed.',
									'time_expires'			=> 'Time expires.',
									'repeated_number'		=> 'Repeated number.',
									'you_won'				=> 'You won.',
								];
	
	
	/*
		Muestra las estadísticas de los 10 primeros del ranking
	*/
    public function Init() //OK
    {
		$data = Game::where('status', 2)
				->select('beat_user_name AS user_name'
						,'beat_user_age AS user_age'
						,'beat_user_time AS user_time'
						,'beat_user_tries AS user_tries'
						,'updated_at AS updated_datetime'
						,DB::Raw('(beat_user_time / 2) + beat_user_tries AS user_rating')
				)
				->orderBy('user_ranking', 'asc')
				->orderBy('updated_datetime', 'asc')
				->take(env('APP_RANKING'))
				->get();
		return response()->json($data);
    }

	/*
		Selecciona un juego aleatorio que no se esté jugando
	*/
    public function NewGame(Request $request, Game $game) //OK
    {
		$inputValidation	= \Validator::make(
												$request->all(), 
												[
													'name'	=> [
																	'required', 
																	'string', 
																],
													'age'	=> [
																	'required', 
																	'integer', 
																	'min:'.env('APP_MIN_AGE'),
																	'max:'.env('APP_MAX_AGE'),
																]
												],
												$this->validationMessages
							);
		if($inputValidation->fails())
		{
			$data	= [
						'errors'  => $inputValidation->errors()->all()
					];
		}
		else
		{
			$data = Game::inRandomOrder()
						->where('status', 0)
						->first();
			/*
				Se debe pasar el parámetro old_game cuando se heneró un juego nuevo
				y se desee generar otro, de esta forma se libera el juego que fue previamente
				seleccionado
			*/
			if($request->has('old_game'))
			{
				$this->CleanStats($request->get('old_game'));
			}
			
			/*
				Establece que ese juego está siendo utilizado
			*/
			Game::where('id', $data->id)
					->update(
								[
									'beat_user_name'	=> $request->get('name'),
									'beat_user_age'		=> $request->get('age'),
									'start_playing'		=> time(),
									'status'			=> 1
								]
					);
		}
		return response()->json($data);
    }

	/*
		GET: Muestra un juego específico
		POST: Valida una entrada
		DELETE: Elimina las estadísticas de un juego
	*/
    public function GameActions(Request $request, Game $game)
    {
		switch($request->getMethod())
		{
			case 'GET':
				$data = $game;
				break;
			case 'POST':
				list($data, $code) = $this->ValidateAnswer($request, $game);
				break;
			case 'DELETE':
				$data = $this->CleanStats($game->id);
				break;
			default:
				$data	= [
							'errors'  => [$this->errorMessages['method_not_allowed']]
						];
		}
		if(isset($code) && $code !== null)
		{
			return response()->json($data, $code);
		}
		else
		{
			return response()->json($data);
		}
    }

	/*
		Valida una entrada
	*/
    private function ValidateAnswer($request, $game) //Almost there
    {
		$code = null;
		/*
			Validamos que no se ha agotado el tiempo
		*/
		$timeSoFar = time() - $game->start_playing;
		if($timeSoFar < env('APP_GAMEOVER'))
		{
			$data	= [
						'errors'  => [$this->errorMessages['time_expires']]
					];
			/*
				Code for time expires
			*/
			$code = 213;
		}
		else
		{
			/*
				Validamos que el se entra el número y que cumple con todos los requisitos
			*/
			$inputValidation	= \Validator::make(
													$request->all(), 
													[
														'number'	=> [
																			'required', 
																			'integer', 
																			'min:1234',
																			'max:9876',
																	]
													],
													$this->validationMessages
								);
			if($inputValidation->fails())
			{
				$data	= [
							'errors'  => $inputValidation->errors()->all()
						];
				/*
					Code for input errors
				*/
				$code = 214;
			}
			else
			{
				/*
					Validamos que el número no esté repetido
				*/
				$userPlaying = (array) json_decode($game->user_playing);
				if(array_key_exists($request->get('number'), (array) $userPlaying))
				{
					$data	= [
								'errors'  => [$this->errorMessages['repeated_number']]
							];
					/*
						Code for repeated number
					*/
					$code = 215;
				}
				else
				{
					/*
						Validamos los toros y las vacas
					*/
					for($i = 0; $i < 4; $i++)
					{
						$secret[]	= substr($game->number, $i, 1);
						$number[]	= substr($request->get('number'), $i, 1);
					}
					$bulls	= sizeof(array_intersect_assoc($secret, $number));
					$cows	= sizeof(array_intersect($secret, $number)) - $bulls;
					if($bulls !== 4)
					{
						/*
							El jugador no ha adivinado el número, el juego continua.
							Se devuelven todos los intentos hasta ahora
							Se actualiza el campo user_playing en la DB
						*/
						$userPlaying[$request->get('number')] = [$bulls, $cows, $timeSoFar];
						$data	= [
									'user_playing'  		=> $userPlaying
								];
						/*
							Code for next move
						*/
						$code = 216;
						DB::Table("games")
							->where("id", $game->id)
							->update(	
										[
											'user_playing'	=> json_encode($userPlaying)
										]
							);
					}
					else
					{
						/*
							El jugador adivinó el número
							Se devuelve el tiempo de terminación y la cantidad de intentos
							Se actualizan los campos del usuario ganador y se borran los datos de los intentos
						*/
						$data	= [
									'message'  => [$errorMessages['you_won']]
								];
						/*
							Code for won game
						*/
						$code = 217;
					}
				}
			}
		}
		return [$data, $code];
    }


	/*
		Elimina las estadísticas de un juego
		Cuando se especifica el parámetro -full- como falso
		solo se limpia las entradas del juego, esto es útil 
		para la terminación de un juego porque timeover
	*/
    private function CleanStats($gameID, $full = true)
    {
		if($full === true)
		{
			$updateArray 	= [
								'beat_user_name'	=> '',
								'beat_user_age'		=> 0,
								'beat_user_time'	=> 0,
								'beat_user_tries'	=> 0,
								'beat_user_time'	=> 0
							];
		}
		$updateArray['start_playing']	= 0;
		$updateArray['user_playing']	= null;
		$updateArray['status']			= 0;

		Game::where('id', $gameID)
			->update($updateArray);
    
		/*
			Esta salida es opcional
			Puede servir para no tener que redireccionar desde el cliente 
			u otro idea que pueda surgir
		*/
		$data = $this->Init(); 
		return $data->original;
	}
}
