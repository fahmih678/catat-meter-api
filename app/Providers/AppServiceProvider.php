<?php

namespace App\Providers;

use App\Contracts\RepositoryInterface;
use App\Repositories\BaseRepository;
use App\Repositories\PamRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\MeterRepository;
use App\Repositories\MeterReadingRepository;
use App\Services\PamService;
use App\Services\CustomerService;
use App\Services\MeterService;
use App\Services\MeterReadingService;
use Illuminate\Pagination\Paginator;
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

        $this->app->bind(MeterReadingRepository::class, function ($app) {
            return new MeterReadingRepository();
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

        $this->app->bind(MeterReadingService::class, function ($app) {
            return new MeterReadingService($app->make(MeterReadingRepository::class));
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
        // Configure response optimization
        $this->configureResponseOptimization();

        // Register macros for easier access
        $this->registerMacros();

        // Register custom validation rules if needed
        $this->registerValidationRules();

        Paginator::defaultView('pagination::bootstrap-5');

        Paginator::defaultSimpleView('simple-bootstrap-5');
    }

    /**
     * Configure response optimization to prevent broken pipe errors
     */
    private function configureResponseOptimization(): void
    {
        // Set output buffering to prevent broken pipe issues
        if (app()->runningInConsole() === false) {
            // Enable output buffering for web requests
            if (ob_get_level() === 0) {
                ob_start();
            }

            // Set reasonable memory and time limits
            ini_set('memory_limit', '256M');
            ini_set('max_execution_time', '60');

            // Configure output buffer to flush automatically
            ini_set('output_buffering', '4096');
            ini_set('implicit_flush', '1');
        }

        // Register response macro for consistent JSON responses
        \Illuminate\Http\Response::macro('apiSuccess', function ($data = null, $message = 'Success', $statusCode = 200) {
            $response = [
                'status' => 'success',
                'message' => $message
            ];

            if ($data !== null) {
                $response['data'] = $data;
            }

            return response()->json($response, $statusCode, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-cache, private',
                'X-Content-Type-Options' => 'nosniff'
            ]);
        });

        \Illuminate\Http\Response::macro('apiError', function ($message = 'Error', $statusCode = 500, $errors = null) {
            $response = [
                'status' => 'error',
                'message' => $message
            ];

            if ($errors !== null) {
                $response['errors'] = $errors;
            }

            return response()->json($response, $statusCode, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-cache, private',
                'X-Content-Type-Options' => 'nosniff'
            ]);
        });
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
