<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Domain\Repositories;

use Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\DTOs\CreateJobOfferDTO;

interface UpdateJobOfferRepositoryInterface
{
  public function update(int $id, CreateJobOfferDTO $form): void;
}
