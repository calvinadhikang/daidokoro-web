<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\GoogleCloudStorage\PortableVisibilityHandler;
use League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerGoogleCloudStorageDriver();
        $this->configureDefaults();
    }

    protected function registerGoogleCloudStorageDriver(): void
    {
        Storage::extend('gcs', function ($app, array $config): FilesystemAdapter {
            $client = new StorageClient([
                'projectId' => $config['project_id'] ?? null,
                'keyFilePath' => $config['key_file'] ?? null,
            ]);

            $bucket = $client->bucket($config['bucket']);
            $visibilityHandler = ($config['uniform_bucket_level_access'] ?? true)
                ? new UniformBucketLevelAccessVisibility()
                : new PortableVisibilityHandler();
            $adapter = new GoogleCloudStorageAdapter(
                $bucket,
                $config['path_prefix'] ?? '',
                $visibilityHandler,
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config,
            );
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
