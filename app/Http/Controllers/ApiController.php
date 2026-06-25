<?php

namespace App\Http\Controllers;

use App\Support\Api\ApiResponse;
use Illuminate\Routing\Controller as BaseController;

class ApiController extends BaseController
{
    use ApiResponse;

    protected int $perPage = 15;
}
