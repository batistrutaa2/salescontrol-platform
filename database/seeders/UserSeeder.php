<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {

    $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');

    DB::table('users')->insert([
      [
        'id' => 1,
        'empresa_id' => 1,
        'user_role_id' => 4,
        'name' => 'CELSO ROMAO BATISTA JUNIOR',
        'email' => 'admin@admin.com.br',
        'email_verified_at' => $currentDateTime,
        'password' => Hash::make("47633852836"),
        'created_at' => now(),
        'updated_at' => now(),
        'ativo' => 'Y'
      ],
    ]);
  }
}
