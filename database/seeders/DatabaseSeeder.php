<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
		for($a = 1; $a <= 9; $a++)
		{
			for($b = 1; $b <= 9; $b++)
			{
				for($c = 1; $c <= 9; $c++)
				{
					for($d = 1; $d <= 9; $d++)
					{
						if($a != $b && $a != $c && $a != $d && $b != $c && $b != $d && $c != $d)
						{
							$number = $a.$b.$c.$d;
							$data[]	= [	'id'					=> NULL, 
										'number'				=> $number,
										'beat_user_name'		=> '',
										'beat_user_age'			=> 0,
										'beat_user_time'		=> 0,
										'beat_user_tries'		=> 0,
										'start_playing'			=> 0,
										'user_playing'			=> NULL,
										//'play_start'			=> 0,
										'updated_at'			=> NULL,
										'status'				=> 0
									];
						}
					}
				}
			}
		}
		DB::table('games')->insert($data);
    }
}
