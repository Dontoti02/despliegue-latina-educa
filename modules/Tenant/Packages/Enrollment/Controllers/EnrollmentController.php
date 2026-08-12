<?php

namespace Modules\Tenant\Packages\Enrollment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Enrollment\UseCases\EnrollmentUseCases;

class EnrollmentController extends Controller
{
  public function filters()
  {
    return EnrollmentUseCases::filters();
  }

  public function list(Request $request)
  {
    return EnrollmentUseCases::list($request);
  }

  public function get(int $id)
  {
    return EnrollmentUseCases::get($id);
  }

  public function update(Request $request)
  {
    return EnrollmentUseCases::update($request);
  }

  public function delete(int $id)
  {
    return EnrollmentUseCases::delete($id);
  }

  public function validate()
  {
    return EnrollmentUseCases::validate();
  }

  public function family(string $documentNumber)
  {
    return EnrollmentUseCases::family($documentNumber);
  }

  public function detail(Request $request)
  {
    return EnrollmentUseCases::detail($request);
  }

  public function listClassrooms(Request $request)
  {
    return EnrollmentUseCases::listClassrooms($request);
  }

  public function set(Request $request)
  {
    return EnrollmentUseCases::set($request);
  }

  public function download(Request $request)
  {
    return EnrollmentUseCases::download($request);
  }
}
