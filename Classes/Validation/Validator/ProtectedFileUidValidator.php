<?php

declare(strict_types=1);

namespace GAYA\UploadWidget\Validation\Validator;

use GAYA\UploadWidget\Service\UploadService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class ProtectedFileUidValidator extends AbstractValidator
{
    protected UploadService $uploadService;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        // TODO: see in TYPO3 12 if this can be changed by dependency injection
        $this->uploadService = GeneralUtility::makeInstance(UploadService::class);
    }

    public function isValid($value)
    {
        if (!$this->uploadService->validateProtectFileUid($value)) {
            $this->addError(
                $this->translateErrorMessage(
                    'form.error.1663858994',
                    'uploadWidget'
                ),
                1663858994
            );
        }
    }
}
