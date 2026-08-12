<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\Requests\DeleteJobOfferUseRequest;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases\DeleteJobOfferUseCase;

class DeleteJobOfferController extends Controller
{
  public function __invoke(
    $id,
    DeleteJobOfferUseRequest $request,
    DeleteJobOfferUseCase $useCase
  ): JsonResponse {
    $request->validated();
    $response = $useCase->execute($id);
    return $response;
  }
}
