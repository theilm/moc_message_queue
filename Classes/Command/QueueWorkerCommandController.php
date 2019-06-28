<?php

namespace MOC\MocMessageQueue\Command;

use MOC\MocMessageQueue\Message\MessageInterface;
use MOC\MocMessageQueue\Message\StringMessage;
use MOC\MocMessageQueue\Queue\QueueInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Message queue worker
 *
 * This command can start the worker process that will listen for message in the configured queue.
 *
 * @package MOC\MocMessageQueue
 */
class QueueWorkerCommandController extends CommandController
{

    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * QueueWorkerCommandController constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * @param QueueInterface $queue
     */
    public function injectQueue(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @param Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Run the queue in the background
     *
     * @param integer $maximumMessages If set to a number larger than 0 (default), only this amount of numbers are
     * handed, before it exits with an exit code of 9
     * @param boolean $debugOutput If TRUE, a slot is connected that display some debug output when a message is handled
     * @param integer $maximumTime If set to a number larger than 0 (default), the script only runs this time
     * (in seconds) before it exits with an exit code of 9
     * @return void
     */
    public function startCommand($maximumMessages = 0, $debugOutput = false, $maximumTime = 0)
    {
        $this->logger->debug(
            'Starting up queue worker with implementation ' . get_class($this->queue)
        );
        $this->signalSlotDispatcher->connect(
            __CLASS__,
            'messageReceived',
            function (MessageInterface $message) {
                $this->logger->debug(
                    'Message received: ' . get_class($message) .
                    ($message instanceof StringMessage ? ' - Message ' . $message->getPayload() : '')
                );
            }
        );
        if ($debugOutput) {
            print date('d/m-Y H:i:s ') .
                'Starting up queue worker with implementation ' . get_class($this->queue) . PHP_EOL;
            ob_flush();
            $this->signalSlotDispatcher->connect(__CLASS__, 'messageReceived', function(MessageInterface $message) {
                print date('d/m-Y H:i:s ') .  'Message received: ' . get_class($message);
                if ($message instanceof StringMessage) {
                    print ' - Message ' . $message->getPayload();
                }
                print PHP_EOL;
                ob_flush();
            });
        }

        $numberOfMessagesHandled = 0;
        $startTime = time();
        while (true) {
            try {
                $message = $this->queue->waitAndReserve();
                if ($message !== null) {
                    $this->signalSlotDispatcher->dispatch(
                        __CLASS__,
                        'messageReceived',
                        ['message' => $message]
                    );
                    $this->queue->finish($message);
                    $numberOfMessagesHandled++;
                    if ($maximumMessages > 0 && $numberOfMessagesHandled >= $maximumMessages) {
                        $logMessage = 'Maximum number of messages ' .
                            $maximumMessages . ' is reached. Exiting with exitcode 9.';
                        $this->logger->debug($logMessage);
                        if ($debugOutput) {
                            print date('d/m-Y H:i:s ') . $logMessage . PHP_EOL;
                            ob_flush();
                        }
                        $this->sendAndExit(9);
                    }
                    if ($maximumTime > 0 && (time() - $startTime) > $maximumTime) {
                        $logMessage = 'Maximum runningtime ' .
                            $maximumTime . ' seconds is reached. Exiting with exitcode 9.';
                        $this->logger->debug($logMessage);
                        if ($debugOutput) {
                            print date('d/m-Y H:i:s') . $logMessage . PHP_EOL;
                            ob_flush();
                        }
                        $this->sendAndExit(9);
                    }
                } else {
                    $this->logger->error('Message is null. Exiting with exitcode 9.');
                    $this->sendAndExit(9);
                }
            } catch (\Exception $exception) {
                $logMessage = 'Error handling message:' . $exception->getMessage();
                $this->logger->error($logMessage);
                if ($debugOutput) {
                    print date('d/m-Y H:i:s ') .  $logMessage . PHP_EOL;
                    ob_flush();
                }
                GeneralUtility::devLog(
                    $logMessage,
                    'moc_message_queue'
                );
            }
        }
    }

    /**
     * Publish test message to queue
     *
     * This will publish a simple StringMessage to the queue. It is only used for test purposes.
     *
     * @param string $messageString The message to publish
     * @return void
     */
    public function publishTestMessageCommand($messageString)
    {
        $message = new StringMessage($messageString);
        $this->queue->publish($message);
    }

}
