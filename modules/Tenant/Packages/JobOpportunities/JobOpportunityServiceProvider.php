<?php

namespace Modules\Tenant\Packages\JobOpportunities;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Packages\JobOpportunities\Company\Providers\CompanyServiceProvider;
use Modules\Tenant\Packages\JobOpportunities\JobOffer\Providers\JobOfferServiceProvider;

class JobOpportunityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(CompanyServiceProvider::class);
        $this->app->register(JobOfferServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
