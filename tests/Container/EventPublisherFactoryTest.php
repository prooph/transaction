<?php
/**
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStoreBusBridge\Container;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prooph\EventStoreBusBridge\Container\EventPublisherFactory;
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\ServiceBus\EventBus;

class EventPublisherFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_event_publisher_using_the_default_service_name_for_getting_an_event_bus(): void
    {
        $eventBus = $this->prophesize(EventBus::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get(EventBus::class)->willReturn($eventBus->reveal());

        $factory = new EventPublisherFactory();

        $eventPublisher = $factory($container->reveal());

        $this->assertInstanceOf(EventPublisher::class, $eventPublisher);
    }

    /**
     * @test
     */
    public function it_creates_an_event_publisher_via_callstatic(): void
    {
        $eventBus = $this->prophesize(EventBus::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('foo')->willReturn($eventBus->reveal());

        $type = 'foo';
        $eventPublisher = EventPublisherFactory::$type($container->reveal());

        $this->assertInstanceOf(EventPublisher::class, $eventPublisher);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_passed_to_callstatic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $type = 'foo';
        EventPublisherFactory::$type('invalid container');
    }
}
