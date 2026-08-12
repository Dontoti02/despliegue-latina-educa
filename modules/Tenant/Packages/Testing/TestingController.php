<?php

namespace Modules\Tenant\Packages\Testing;

use App\Http\Controllers\Controller;

class TestingController extends Controller
{
    public function test()
    {
        return 'Hola mundo';
    }
}
