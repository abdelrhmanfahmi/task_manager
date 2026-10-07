<?php

namespace App\Providers;

use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\TaskRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds repository interfaces to their concrete implementations,
 * so controllers depend only on the abstractions.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TaskRepositoryInterface::class => TaskRepository::class,
    ];
}
