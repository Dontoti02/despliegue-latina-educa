<?php

namespace Modules\Tenant\Packages\Home\UseCases;

use Exception;

use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Home\Repositories\HomeSecretaryRepository;
use Modules\Tenant\Packages\Home\Repositories\HomeStudentRepository;
use Modules\Tenant\Packages\Home\Repositories\HomeTeacherRepository;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class HomeUseCases
{
    public static function home()
    {
        try {
            $user = User::authenticated();

            switch ($user->rol_id) {
                case RolTenant::STUDENT:
                    $result = HomeStudentRepository::home();
                    break;
                case RolTenant::TEACHER:
                    $result = HomeTeacherRepository::home();
                    break;
                default:
                    $result = HomeSecretaryRepository::home();
                    break;
            }

            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
