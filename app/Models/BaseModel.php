<?php

namespace App\Models;

use App\Support\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use Filterable;
}
