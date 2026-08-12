<?php

namespace Modules\Tenant\Packages\Treasury\Repositories;

use Illuminate\Http\Request;
use Modules\Tenant\Models\Scale;
use Modules\Tenant\Packages\Treasury\Adapters\ScalesAdapter;
use Modules\Tenant\Packages\Treasury\Helpers\ScalesHelper;
use Modules\Tenant\Models\Person;

class ScalesRepository
{

  public static function create(Request $request)
  {

    $name = $request->name;
    $scale_amount = $request->scale_amount;
    $scale = Scale::create([
      'name' => $name,
      'scale_amount' => $scale_amount,
    ]);

    return $scale;
  }
  public static function all(Request $request)
  {

    $search = $request->input('search');
    $page   = (int) $request->input('page', 1);
    $limit  = (int) $request->input('limit', 10);

    $items = collect();

    $paginated = ScalesHelper::getPaginated($page, $limit, $search);

    foreach ($paginated->items() as $item) {
      $items->push(ScalesAdapter::transform($item));
    }

    return  [
      'items' => $items,
      'pagination' => [
        'page' => $paginated->currentPage(),
        'pages' => $paginated->lastPage(),
        'total' =>  $paginated->total(),
      ]
    ];
  }

  public static function update(Request $request, int $id)
  {
    $scale = Scale::find($id);
    $scale->name = $request->name;
    $scale->scale_amount = $request->scale_amount;
    $scale->save();
    return $scale;
  }

  public static function delete(int $id)
  {
    $scale = Scale::find($id);
    $scale->delete();
    return $scale;
  }

  public static function enrollments(int $id)
  {
    $scale = Scale::find($id);
    $enrolls = $scale->enrolls;
    $persons_names = $enrolls->pluck('person_id')->map(function ($person_id) {
      return Person::find($person_id)->names;
    });
    return $persons_names;
  }
}
