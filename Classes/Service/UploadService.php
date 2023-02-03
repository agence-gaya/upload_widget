<?php

declare(strict_types=1);

namespace GAYA\UploadWidget\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;

class UploadService
{
    public function __construct(
        protected HashService $hashService,
        protected ResourceFactory $resourceFactory
    ) {
    }

    public function getFile(string $protectedFileUid): File
    {
        $fileUid = (int)$this->hashService->validateAndStripHmac($protectedFileUid);
        return $this->resourceFactory->getFileObject($fileUid);
    }

    public function protectFileUid(int $fileUid): string
    {
        return $this->hashService->appendHmac((string)$fileUid);
    }

    public function validateProtectFileUid(string $protectedFileUid): bool
    {
        try {
            $this->hashService->validateAndStripHmac($protectedFileUid);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function getRandomFilename(string $extension): string
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(32);
        } else {
            $bytes = random_bytes(32);
        }
        return bin2hex($bytes) . '.' . $extension;
    }

    public function createExtbaseFileReferenceFromFile(File $file): ExtbaseFileReference
    {
        $fileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
                'crop' => null,
            ]
        );
        return $this->createExtbaseFileReferenceFromFileReference($fileReference);
    }

    public function createExtbaseFileReferenceFromFileReference(FileReference $fileReference): ExtbaseFileReference
    {
        /** @var $extbaseFileReference ExtbaseFileReference */
        $extbaseFileReference = GeneralUtility::makeInstance(ExtbaseFileReference::class);
        $extbaseFileReference->setOriginalResource($fileReference);

        return $extbaseFileReference;
    }
}
