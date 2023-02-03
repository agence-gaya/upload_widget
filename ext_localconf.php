<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use GAYA\UploadWidget\Controller\UploadController;

defined('TYPO3') or die();

(function () {
    ExtensionUtility::configurePlugin(
        'UploadWidget',
        'Upload',
        [
            UploadController::class => 'save,delete',
        ],
        [
            UploadController::class => 'save,delete',
        ]
    );
})();
