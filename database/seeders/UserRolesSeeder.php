<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class UserRolesSeeder extends Seeder
{
  /**
   * Seed the application's database.
   *
   * @return void
   */
  public function run()
  {
    $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');

    DB::table('user_roles')->insert([
      ['id' => 1, 'tipo_usuario' => 'VENDEDOR', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 2, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 3, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 4, 'tipo_usuario' => 'DEVELOPER', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 5, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 6, 'tipo_usuario' => 'BENEFICIOS', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 7, 'tipo_usuario' => 'ADVOGADA', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
    ]);
  }
}
