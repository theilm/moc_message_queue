<?php
$TCA['tx_mocmessagequeue_queue'] = [
    'ctrl' => [
        'title' => 'MOC Message queue',
        'label' => 'Message',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'hideTable' => 1,
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) .
            'Configuration/TCA/MessageQueue.php',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) .
            'Resources/Public/Icons/MessageQueue.png'
    ],
    'interface' => ['showRecordFieldList' => 'data'],
    'columns' => [
        'data' => [
            'label' => 'Serialized event',
            'config' => [
                'type' => 'passthrough',
            ],
        ]
    ],
    'types' => [
        '0' => ['showitem' => 'data']
    ]
];
