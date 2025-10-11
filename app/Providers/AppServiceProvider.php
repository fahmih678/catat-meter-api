<?php

namespace App\Providers;

use App\Contracts\RepositoryInterface;
use App\Repositories\BaseRepository;
use App\Repositories\PamRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\MeterRepository;
use App\Repositories\MeterRecordRepository;
use App\Services\PamService;
use App\Services\CustomerService;
use App\Services\MeterService;
use App\Services\MeterRecordService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository Bindings
        $this->app->bind(PamRepository::class, function ($app) {
            return new PamRepository();
        });

        $this->app->bind(CustomerRepository::class, function ($app) {
            return new CustomerRepository();
        });

        $this->app->bind(MeterRepository::class, function ($app) {
            return new MeterRepository();
        });

        $this->app->bind(MeterRecordRepository::class, function ($app) {
            return new MeterRecordRepository();
        });

        // Service Bindings
        $this->app->bind(PamService::class, function ($app) {
            return new PamService($app->make(PamRepository::class));
        });

        $this->app->bind(CustomerService::class, function ($app) {
            return new CustomerService($app->make(CustomerRepository::class));
        });

        $this->app->bind(MeterService::class, function ($app) {
            return new MeterService($app->make(MeterRepository::class));
        });

        $this->app->bind(MeterRecordService::class, function ($app) {
            return new MeterRecordService($app->make(MeterRecordRepository::class));
        });

        // Singleton bindings for frequently used services
        $this->app->singleton('pam.service', function ($app) {
            return $app->make(PamService::class);
        });

        $this->app->singleton('customer.service', function ($app) {
            return $app->make(CustomerService::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register macros for easier access
        $this->registerMacros();

        // Register custom validation rules if needed
        $this->registerValidationRules();
    }

    /**
     * Register custom macros for easier service access
     */
    private function registerMacros(): void
    {
        // Add macro to Request for easier PAM service access
        \Illuminate\Http\Request::macro('pamService', function () {
            return app(PamService::class);
        });

        \Illuminate\Http\Request::macro('customerService', function () {
            return app(CustomerService::class);
        });
    }

    /**
     * Register custom validation rules
     */
    private function registerValidationRules(): void
    {
        // Custom validation rule to check if area belongs to PAM
        \Illuminate\Support\Facades\Validator::extend('area_belongs_to_pam', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0])) {
                return false;
            }

            $pamId = $parameters[0];
            $area = \App\Models\Area::find($value);

            return $area && $area->pam_id == $pamId;
        });

        // Custom validation rule to check if tariff group belongs to PAM
        \Illuminate\Support\Facades\Validator::extend('tariff_group_belongs_to_pam', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0])) {
                return false;
            }

            $pamId = $parameters[0];
            $tariffGroup = \App\Models\TariffGroup::find($value);

            return $tariffGroup && $tariffGroup->pam_id == $pamId;
        });

        // Custom validation for unique customer number within PAM
        \Illuminate\Support\Facades\Validator::extend('unique_customer_number_in_pam', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0])) {
                return false;
            }

            $pamId = $parameters[0];
            $customerId = isset($parameters[1]) ? $parameters[1] : null;

            $query = \App\Models\Customer::where('pam_id', $pamId)
                ->where('customer_number', $value);

            if ($customerId) {
                $query->where('id', '!=', $customerId);
            }

            return !$query->exists();
        });
    }
}
