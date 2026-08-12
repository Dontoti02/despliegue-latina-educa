<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Domain\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\DTOs\FiltersJobOfferDTO;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\DTOs\ListJobOffersFilterDTO;
use Modules\Tenant\Models\JobOffer;

interface JobOfferRepositoryInterface
{
  public function filters(FiltersJobOfferDTO $filter): array;

  public function list(ListJobOffersFilterDTO $filter): LengthAwarePaginator;

  public function findById(string $id): ?JobOffer;

  public function findBySlug(string $slug): ?JobOffer;

  public function delete(string $id): void;

  public function changeStatus(JobOffer $id, int $status): void;
}
