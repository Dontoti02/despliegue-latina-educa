<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests\CreateJobOfferRequest;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases\CreateJobOfferUseCase;

class CreateJobOfferController extends Controller
{
  public function __invoke(
    CreateJobOfferRequest $request,
    CreateJobOfferUseCase $useCase
  ): JsonResponse {
    $response = $useCase->execute($request->validated());
    return $response;
  }
}
