<?php

namespace Modules\Tenant\Packages\User\Repositories;

use Modules\Tenant\Packages\User\Helpers\MenuHelper;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Period\Helpers\PeriodHelper;

class MenuRepository
{
    public static function get()
    {
        $user = User::authenticated();

        $period = PeriodHelper::current();
        $menu = MenuHelper::listByUser($user, $period);

        return $menu;
    }
}
