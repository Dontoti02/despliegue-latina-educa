<?php

namespace Modules\Shared\Helpers;

use Illuminate\Support\Facades\Request;

class SessionManager
{
    
    const JWT_SESSION = 'andheuris_jwt_token_plataforma_academica';
     
    /**
     * Get session
     *
     * @return collect|null
     */
    public static function get()
    {

        return (object)Request::get(self::JWT_SESSION);
    }

}
