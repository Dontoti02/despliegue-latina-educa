<?php

namespace Modules\Tenant\Packages\JobOpportunities\JobOffer\Application\DTOs;

class FiltersJobOfferDTO
{
  public function __construct(
    public readonly bool $isAdmin,
    public readonly bool $isCompany,
    public readonly ?int $companyId = null,
  ) {}
}
