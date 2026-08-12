<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests\FindJobOfferUseRequest;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases\FindJobOfferUseCase;

class FindJobOfferController extends Controller
{
  public function __invoke(
    FindJobOfferUseRequest $request,
    FindJobOfferUseCase $useCase
  ): JsonResponse {
    $request->validated();
    $response = $useCase->execute($request->query('id'), $request->query('slug'));
    return $response;
  }
}
