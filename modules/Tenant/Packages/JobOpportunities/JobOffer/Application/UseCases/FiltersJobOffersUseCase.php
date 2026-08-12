<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\UseCases;

use Illuminate\Http\JsonResponse;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\DTOs\FiltersJobOfferDTO;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Domain\Repositories\JobOfferRepositoryInterface;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class FiltersJobOffersUseCase
{
  public function __construct(
    protected JobOfferRepositoryInterface $repository
  ) {}

  public function execute(): JsonResponse
  {
    $user = User::authenticated();
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);
    $isCompany = $user->hasRole(RolTenant::COMPANY);
    $dto = new FiltersJobOfferDTO(
      isAdmin: $isAdmin,
      isCompany: $isCompany,
      companyId: $isAdmin ? null : ($isCompany ? $user->company->id : null),
    );
    $filters = $this->repository->filters($dto);
    return Response::success($filters, 'Filtros de convocatorias');
  }
}
