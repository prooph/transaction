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

namespace Prooph\EventStoreBusBridge;

use ArrayIterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\Stream;
use Prooph\ServiceBus\CommandBus;

final class CausationMetadataEnricher implements ActionEventListenerAggregate, MetadataEnricher, Plugin
{
    use DetachAggregateHandlers;

    /**
     * @var Message
     */
    private $currentCommand;

    public function setUp(ActionEventEmitterEventStore $eventStore): void
    {
        $eventEmitter = $eventStore->getActionEventEmitter();

        $eventEmitter->attachListener(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event): void {
                if (null === $this->currentCommand || ! $this->currentCommand instanceof Message) {
                    return;
                }

                $recordedEvents = $event->getParam('streamEvents');

                $enrichedRecordedEvents = [];

                foreach ($recordedEvents as $recordedEvent) {
                    $enrichedRecordedEvents[] = $this->enrich($recordedEvent);
                }

                $event->setParam('streamEvents', new ArrayIterator($enrichedRecordedEvents));
            },
            1000
        );

        $eventEmitter->attachListener(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event): void {
                if (null === $this->currentCommand || ! $this->currentCommand instanceof Message) {
                    return;
                }

                $stream = $event->getParam('stream');
                $recordedEvents = $stream->streamEvents();

                $enrichedRecordedEvents = [];

                foreach ($recordedEvents as $recordedEvent) {
                    $enrichedRecordedEvents[] = $this->enrich($recordedEvent);
                }

                $stream = new Stream(
                    $stream->streamName(),
                    new ArrayIterator($enrichedRecordedEvents),
                    $stream->metadata()
                );

                $event->setParam('stream', $stream);
            },
            1000
        );
    }

    public function attach(ActionEventEmitter $eventEmitter): void
    {
        $this->trackHandler(
            $eventEmitter->attachListener(
                CommandBus::EVENT_DISPATCH,
                function (ActionEvent $event): void {
                    $this->currentCommand = $event->getParam(CommandBus::EVENT_PARAM_MESSAGE);
                },
                CommandBus::PRIORITY_INVOKE_HANDLER + 1000
            )
        );

        $this->trackHandler(
            $eventEmitter->attachListener(
                CommandBus::EVENT_FINALIZE,
                function (ActionEvent $event): void {
                    $this->currentCommand = null;
                },
                1000
            )
        );
    }

    public function enrich(Message $message): Message
    {
        $message = $message->withAddedMetadata('_causation_id', $this->currentCommand->uuid()->toString());
        $message = $message->withAddedMetadata('_causation_name', $this->currentCommand->messageName());

        return $message;
    }
}
