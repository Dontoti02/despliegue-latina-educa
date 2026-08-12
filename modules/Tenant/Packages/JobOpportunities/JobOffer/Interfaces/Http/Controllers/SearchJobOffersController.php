<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests\SearchJobOffersRequest;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases\SearchJobOffersUseCase;

class SearchJobOffersController extends Controller
{
  public function __invoke(
    SearchJobOffersRequest $request,
    SearchJobOffersUseCase $useCase
  ): JsonResponse {
    $response = $useCase->execute($request->validated());
    return $response;
  }
}
