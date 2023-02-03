<?php

declare(strict_types=1);

namespace GAYA\UploadWidget\Property\TypeConverter;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use GAYA\UploadWidget\Service\UploadService;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class UploadedFileConverter extends AbstractTypeConverter
{
    /**
     * Folder where the file upload should go to (including storage).
     */
    public const CONFIGURATION_UPLOAD_FOLDER = 1;

    /**
     * How to handle a upload when the name of the uploaded file conflicts.
     */
    public const CONFIGURATION_UPLOAD_CONFLICT_MODE = 2;

    /**
     * Max file size allowed
     */
    public const CONFIGURATION_MAX_FILE_SIZE = 3;

    /**
     * List of file extensions allowed separated by coma
     */
    public const CONFIGURATION_ALLOWED_FILE_EXTENSIONS = 4;

    /**
     * @var string
     */
    protected $defaultUploadFolder = '1:/user_upload/';

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = File::class;

    /**
     * Take precedence over the available FileReferenceConverter
     *
     * @var int
     */
    protected $priority = 30;

    /**
     * @var File[]
     */
    protected array $convertedResources = [];

    public function __construct(
        protected ResourceFactory $resourceFactory,
        protected HashService $hashService,
        protected PersistenceManager $persistenceManager,
        protected UploadService $uploadService
    ) {
    }

    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($source['error'] !== UPLOAD_ERR_OK) {
            switch ($source['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604667'), 1663604667);
                case UPLOAD_ERR_FORM_SIZE:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604668'), 1663604668);
                case UPLOAD_ERR_PARTIAL:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604669'), 1663604669);
                case UPLOAD_ERR_NO_FILE:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604670'), 1663604670);
                case UPLOAD_ERR_NO_TMP_DIR:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604671'), 1663604671);
                case UPLOAD_ERR_CANT_WRITE:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604672'), 1663604672);
                case UPLOAD_ERR_EXTENSION:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604673'), 1663604673);
                default:
                    return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604465'), 1663604474);
            }
        }

        if (isset($this->convertedResources[$source['tmp_name']])) {
            return $this->convertedResources[$source['tmp_name']];
        }

        if (!is_uploaded_file($source['tmp_name'])) {
            return new Error(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604465'), 1663604465);
        }

        try {
            $resource = $this->importUploadedResource($source, $configuration);
        } catch (\Exception $e) {
            return new Error($e->getMessage(), $e->getCode());
        }

        $this->convertedResources[$source['tmp_name']] = $resource;
        return $resource;
    }

    /**
     * Import a resource and respect configuration given for properties
     *
     * @param array $uploadInfo
     * @param PropertyMappingConfigurationInterface $configuration
     * @return File
     * @throws TypeConverterException
     */
    protected function importUploadedResource(array $uploadInfo, PropertyMappingConfigurationInterface $configuration): File
    {
        // Check file extension
        if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid($uploadInfo['name'])) {
            throw new TypeConverterException(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1664199845'), 1664199845);
        }

        $allowedFileExtensions = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_ALLOWED_FILE_EXTENSIONS);

        $filePathInfo = PathUtility::pathinfo($uploadInfo['name']);
        if ($allowedFileExtensions !== null) {
            if (!GeneralUtility::inList($allowedFileExtensions, strtolower($filePathInfo['extension']))) {
                throw new TypeConverterException(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1664199845'), 1664199845);
            }
        }

        // Check file size
        $maxFileSize = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_MAX_FILE_SIZE);

        if ($maxFileSize !== null) {
            if ($uploadInfo['size'] > $maxFileSize) {
                throw new TypeConverterException(LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1663604666'), 1663604666);
            }
        }

        // Everything is OK, we store the file
        $uploadFolderId = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_FOLDER) ?: $this->defaultUploadFolder;
        $conflictMode = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_UPLOAD_CONFLICT_MODE) ?: DuplicationBehavior::RENAME;

        $uploadFolder = $this->resourceFactory->retrieveFileOrFolderObject($uploadFolderId);

        return $uploadFolder->getStorage()->addUploadedFile($uploadInfo, $uploadFolder, $this->uploadService->getRandomFilename($filePathInfo['extension']), $conflictMode);
    }
}
