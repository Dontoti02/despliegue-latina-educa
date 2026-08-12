<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Models\JobOffer;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class FindJobOfferUseRequest extends FormRequest
{
  public function authorize(): bool
  {
    $user = User::authenticated();
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);

    $jobOfferId = $this->query('id');
    $jobOfferSlug = $this->query('slug');

    if ($isAdmin) {
      return true;
    }

    if (!$user->company) {
      return false;
    }

    $query = JobOffer::query()->where('company_id', $user->company->id);

    if ($jobOfferSlug || $jobOfferId) {
      $query->when($jobOfferSlug, fn($q) => $q->where('slug', $jobOfferSlug))
        ->when($jobOfferId, fn($q) => $q->where('id', $jobOfferId));
    } else {
      return false;
    }

    if ($query->exists()) {
      return true;
    }
    return false;
  }

  public function rules(): array
  {
    return [];
  }
}
