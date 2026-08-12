<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests\ChangeOfferStatusRequest;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases\ChangeOfferStatusUseCase;

class ChangeOfferStatusController extends Controller
{
  public function __invoke(
    ChangeOfferStatusRequest $request,
    ChangeOfferStatusUseCase $useCase
  ): JsonResponse {
    $request->validated();
    $response = $useCase->execute($request->validated());
    return $response;
  }
}
