<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    App\Providers\DOSpacesStorageServiceProvider::class,
    Modules\Tenant\Packages\JobOpportunities\JobOpportunityServiceProvider::class,
];
