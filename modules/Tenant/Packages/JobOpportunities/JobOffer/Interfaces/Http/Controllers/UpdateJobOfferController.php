<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests\CreateJobOfferRequest;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases\UpdateobOfferUseCase;

class UpdateJobOfferController extends Controller
{
  public function __invoke(
    int $id,
    CreateJobOfferRequest $request,
    UpdateobOfferUseCase $useCase
  ): JsonResponse {
    $response = $useCase->execute($id, $request->validated());
    return $response;
  }
}
