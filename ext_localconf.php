<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['moc_message_queue']);
\MOC\MocMessageQueue\Queue\BeanstalkQueue::$server = $config['beanstalk_server'];
\MOC\MocMessageQueue\Queue\BeanstalkQueue::$tube = $config['beanstalk_tube'];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
    'MOC\MocMessageQueue\Command\QueueWorkerCommandController';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
config.tx_extbase {
    objects {
        MOC\MocMessageQueue\Queue\QueueInterface {
            className = MOC\\MocMessageQueue\\Queue\\' . $config['message_queue_implementation'] . 'Queue
        }
    }
}
');

// Logging configuration for mocMessageQueue
$GLOBALS['TYPO3_CONF_VARS']['LOG']['MOC']['MocMessageQueue']['Command']
['QueueWorkerCommandController']['writerConfiguration'] = [
    // configuration for DEBUG severity, including all
    // levels with higher severity
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        // add a SyslogWriter
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'typo3temp/var/logs/moc-message-queue-debug.log'
        ],
    ],
    \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
        // add a SyslogWriter
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'typo3temp/var/logs/moc-message-queue-error.log'
        ],
    ],
];
