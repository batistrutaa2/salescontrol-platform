<?php

namespace App\Models\Legace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllConsultLegace extends Model
{
  use HasFactory;
  
  protected $connection = 'mysql2';

  protected $table = 'records';

}
