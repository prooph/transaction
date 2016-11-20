<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-%year% prooph software GmbH <contact@prooph.de>
 * (c) 2015-%year% Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Prooph\EventStoreBusBridge\Container;

use Interop\Container\ContainerInterface;
use Prooph\EventStoreBusBridge\CausationMetadataEnricher;
use Prooph\ServiceBus\CommandBus;

final class CausationMetadataEnricherFactory
{
    /**
     * @var string
     */
    private $commandBusServiceName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     CausationMetadataEnricher::class => [CausationMetadataEnricherFactory::class, 'command_bus_service_name'],
     * ];
     * </code>
     *
     * @throws \InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): CausationMetadataEnricher
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new \InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $commandBusServiceName = CommandBus::class)
    {
        $this->commandBusServiceName = $commandBusServiceName;
    }

    public function __invoke(ContainerInterface $container): CausationMetadataEnricher
    {
        $commandBus = $container->get($this->commandBusServiceName);

        $CausationMetadataEnricher = new CausationMetadataEnricher();

        $commandBus->utilize($CausationMetadataEnricher);

        return $CausationMetadataEnricher;
    }
}