<?php

namespace Modules\Tenant\Packages\JobOpportunities\MasterTable\Enums;

use Modules\Tenant\Models\JobContractType;
use Modules\Tenant\Models\JobLocation;
use Modules\Tenant\Models\JobOfferCategory;
use Modules\Tenant\Models\JobWorkSchedule;

class MasterTablesEnum
{
  public const MAP = [
    'work_schedule' => JobWorkSchedule::class,
    'category' => JobOfferCategory::class,
    'location' => JobLocation::class,
    'contract_type' => JobContractType::class,
  ];
}
