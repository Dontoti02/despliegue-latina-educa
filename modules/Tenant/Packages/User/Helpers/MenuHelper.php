<?php

namespace Modules\Tenant\Packages\User\Helpers;

use Modules\Admin\Models\Domain;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Models\SystemConfiguration;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\Menu;
use Modules\Tenant\Models\Option;

class MenuHelper
{
  public static function listByUser($user, $period)
  {
    $is_admin = $user->rol_id === RolTenant::ADMINISTRADOR;
    $excluded_options = !$period && $is_admin ? ['UsersList', 'AcademicPeriods', 'ListOfMerit'] : [];

    $tenantId = tenant('id');
    $domain = Domain::byKey('tenant_id', $tenantId);

    $modulesKey =  $domain->institution->modules->filter(function ($module) {
      return !$module->is_active;
    })->map(function ($module) {
      return $module->module_key;
    })->values()->toArray();

    $menuInstitutionTableKey = [
      'bolsa_laboral' => Menu::where('name', 'Bolsa Laboral')->value('id'),
    ];

    $excluded_menu = [];
    foreach ($modulesKey as $moduleKey) {
      if (isset($menuInstitutionTableKey[$moduleKey])) {
        $excluded_menu[] = $menuInstitutionTableKey[$moduleKey];
      }
    }

    $menus = Menu::select('menu.id', 'menu.name')
      ->join('option', 'menu.id', '=', 'option.menu_id')
      ->join('rol_option', 'option.id', '=', 'rol_option.option_id')
      ->where('rol_option.rol_id', $user->rol_id)
      ->whereNotIn('menu.id', $excluded_menu)
      ->orderBy('menu.order', 'asc')
      ->groupBy('menu.id', 'menu.name')
      ->get();

    $result = [];
    foreach ($menus as $menu) {
      $options = self::getOptions($user->rol_id, $menu->id, null, $excluded_options);

      if (count($options) == 0) continue;

      $menu->options = $options;
      $result[] = $menu;
    }

    $links = SystemConfiguration::select([
      'key',
      'name',
      'value',
    ])
      ->whereIn('key', ['virtual_library_url', 'institutional_repository_url', 'external_job_opportunities'])
      ->whereNotNull('value')
      ->get();

    foreach ($links as $link) {
      SystemConfigurationHelper::setTypeValue($link);

      $result[] = [
        'id' => $link->key,
        'name' => $link->key,
        'url' => $link->value,
      ];
    }

    return $result;
  }

  public static function getOptions($rol_id, $menu_id, $option_id, $excluded_options)
  {
    $options = Option::select([
      'option.id',
      'option.name',
      'option.name_url',
      'option.icon',
    ])
      ->join('rol_option', 'option.id', '=', 'rol_option.option_id')
      ->where('option.menu_id', $menu_id)
      ->where('rol_option.rol_id', $rol_id)
      ->where('option.option_id', $option_id)
      ->where('option.is_visible', true)
      ->whereNotIn('option.name_url', $excluded_options)
      ->orderBy('option.order', 'asc')
      ->get();

    foreach ($options as $option) {
      $option->options = self::getOptions($rol_id, $menu_id, $option->id, $excluded_options);
    }

    return $options;
  }
}
