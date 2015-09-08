<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Defines the bootstrapper dispatcher
 */
namespace Opulence\Applications\Bootstrappers\Dispatchers;
use Opulence\Applications\Bootstrappers\IBootstrapperRegistry;
use Opulence\Applications\Tasks\Dispatchers\Dispatcher as TaskDispatcher;
use Opulence\Applications\Tasks\TaskTypes;
use Opulence\IoC\IContainer;
use RuntimeException;

class Dispatcher implements IDispatcher
{
    /** @var TaskDispatcher The task dispatcher */
    private $taskDispatcher = null;
    /** @var IContainer The IoC container */
    private $container = null;
    /** @var bool Whether or not we force eager loading for all bootstrappers */
    private $forceEagerLoading = false;
    /** @var array The list of bootstrapper classes that have been run */
    private $runBootstrappers = [];

    /**
     * @param TaskDispatcher $taskDispatcher The task dispatcher
     * @param IContainer $container The IoC container
     */
    public function __construct(TaskDispatcher $taskDispatcher, IContainer $container)
    {
        $this->taskDispatcher = $taskDispatcher;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function dispatch(IBootstrapperRegistry $registry)
    {
        if($this->forceEagerLoading)
        {
            $eagerBootstrapperClasses = $registry->getEagerBootstrappers();
            $lazyBootstrapperClasses = array_unique(array_values($registry->getLazyBootstrapperBindings()));
            $bootstrapperClasses = array_merge($eagerBootstrapperClasses, $lazyBootstrapperClasses);
            $this->dispatchEagerly($registry, $bootstrapperClasses);
        }
        else
        {
            // We must dispatch lazy bootstrappers first in case their bindings are used by eager bootstrappers
            $this->dispatchLazily($registry, $registry->getLazyBootstrapperBindings());
            $this->dispatchEagerly($registry, $registry->getEagerBootstrappers());
        }
    }

    /**
     * @inheritdoc
     */
    public function forceEagerLoading($doForce)
    {
        $this->forceEagerLoading = (bool)$doForce;
    }

    /**
     * Dispatches the registry eagerly
     *
     * @param IBootstrapperRegistry $registry The bootstrapper registry
     * @param array $bootstrapperClasses The list of bootstrapper classes to dispatch
     * @throws RuntimeException Thrown if there was a problem dispatching the bootstrappers
     */
    private function dispatchEagerly(IBootstrapperRegistry $registry, array $bootstrapperClasses)
    {
        $bootstrapperObjects = [];

        foreach($bootstrapperClasses as $bootstrapperClass)
        {
            $bootstrapper = $registry->getInstance($bootstrapperClass);
            $bootstrapper->registerBindings($this->container);
            $bootstrapperObjects[] = $bootstrapper;
        }

        foreach($bootstrapperObjects as $bootstrapper)
        {
            $this->container->call([$bootstrapper, "run"], [], true);
        }

        // Call the shutdown method
        $this->taskDispatcher->registerTask(TaskTypes::PRE_SHUTDOWN, function () use ($bootstrapperObjects)
        {
            foreach($bootstrapperObjects as $bootstrapper)
            {
                $this->container->call([$bootstrapper, "shutdown"], [], true);
            }
        });
    }

    /**
     * Dispatches the registry lazily
     *
     * @param IBootstrapperRegistry $registry The bootstrapper registry
     * @param array $bindingsToBootstrapperClasses The mapping of bindings to their bootstrapper classes
     * @throws RuntimeException Thrown if there was a problem dispatching the bootstrappers
     */
    private function dispatchLazily(IBootstrapperRegistry $registry, array $bindingsToBootstrapperClasses)
    {
        // This gets passed around by reference so that it'll have the latest objects come time to shut down
        $bootstrapperObjects = [];

        foreach($bindingsToBootstrapperClasses as $boundClass => $bootstrapperClass)
        {
            $this->container->bind(
                $boundClass,
                function () use ($registry, &$bootstrapperObjects, $boundClass, $bootstrapperClass)
                {
                    $bootstrapper = $registry->getInstance($bootstrapperClass);

                    if(!in_array($bootstrapper, $bootstrapperObjects))
                    {
                        $bootstrapperObjects[] = $bootstrapper;
                    }

                    if(!isset($this->runBootstrappers[$bootstrapperClass]))
                    {
                        $bootstrapper->registerBindings($this->container);
                        $this->container->call([$bootstrapper, "run"], [], true);
                        $this->runBootstrappers[$bootstrapperClass] = true;
                    }

                    return $this->container->makeShared($boundClass);
                }
            );
        }

        // Call the shutdown method
        $this->taskDispatcher->registerTask(TaskTypes::PRE_SHUTDOWN, function () use (&$bootstrapperObjects)
        {
            foreach($bootstrapperObjects as $bootstrapper)
            {
                $this->container->call([$bootstrapper, "shutdown"], [], true);
            }
        });
    }
}