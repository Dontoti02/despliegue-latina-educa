<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Domain\Repositories;

use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\DTOs\CreateJobOfferDTO;

interface CreateJobOfferRepositoryInterface
{
  public function create(CreateJobOfferDTO $form): void;
}
