<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Models\JobOffer;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class DeleteJobOfferUseRequest extends FormRequest
{
  public function authorize(): bool
  {
    $user = User::authenticated();
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);

    if ($isAdmin) {
      return true;
    }
    $jobOfferId = $this->route('id');
    $findJobOffer = JobOffer::query()
      ->where('company_id', $user->company->id)
      ->where('id', $jobOfferId)
      ->exists();
    if ($findJobOffer) {
      return true;
    }
    abort(403, 'No tienes permiso para acceder a esta oferta de trabajo.');
    return false;
  }

  public function rules(): array
  {
    return [];
  }
}
