<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\UnauthorizedException;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class SearchJobOffersRequest extends FormRequest
{
  public function authorize(): bool
  {
    $user = User::authenticated();
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);
    $isCompany = $user->hasRole(RolTenant::COMPANY);
    if ($isAdmin || $isCompany) {
      return true;
    }
    abort(403, 'Acceso no autorizado');
    throw new UnauthorizedException('Acceso no autorizado');
  }

  public function rules(): array
  {
    return [
      'companyId'       => ['nullable', 'integer'],
      'orderBy'         => ['nullable', 'in:publication_date,salary'],
      'dateFilter'      => ['nullable', 'in:urgent,yesterday,week,month'],
      'categoryId'      => ['nullable', 'integer'],
      'experience_id'    => ['nullable', 'integer'],
      'salary'     => ['nullable', 'string'],
      'scheduleId' => ['nullable', 'integer'],
      'locationId'      => ['nullable', 'integer'],
      'contractTypeId' => ['nullable', 'integer'],
      'search'           => ['nullable', 'string'],
      'perPage'         => ['nullable', 'integer', 'min:1'],
      'page'             => ['nullable', 'integer', 'min:1'],
    ];
  }
}
