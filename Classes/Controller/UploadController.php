<?php

declare(strict_types=1);

namespace GAYA\UploadWidget\Controller;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use GAYA\UploadWidget\Service\UploadService;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\File;
use GAYA\UploadWidget\Property\TypeConverter\UploadedFileConverter;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class UploadController extends ActionController
{
    public function __construct(
        protected UploadService $uploadService
    ) {
    }

    public function processRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $response = parent::processRequest($request);
        } catch (RequiredArgumentMissingException $e) {
            $response = $this->jsonResponse(json_encode([
                'status' => 'error',
                'errors' => [LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:form.error.1664230724')],
            ]));
        } catch (\Exception $e) {
            $response = $this->jsonResponse(json_encode([
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ]));
        }

        return $response;
    }

    protected function initializeSaveAction()
    {
        $uploadConfiguration = [
            UploadedFileConverter::CONFIGURATION_ALLOWED_FILE_EXTENSIONS => $this->settings['allowedExtensions'],
            UploadedFileConverter::CONFIGURATION_MAX_FILE_SIZE           => $this->settings['maxFileSize'],
            UploadedFileConverter::CONFIGURATION_UPLOAD_FOLDER           => $this->settings['uploadFolder'],
        ];
        $propMapConfig = $this->arguments['file']->getPropertyMappingConfiguration();
        /** @var UploadedFileConverter $uploadedFileReferenceConverter */
        $uploadedFileReferenceConverter = $this->objectManager->get(UploadedFileConverter::class);
        $propMapConfig->setTypeConverter($uploadedFileReferenceConverter);
        $propMapConfig->setTypeConverterOptions(
            UploadedFileConverter::class,
            $uploadConfiguration
        );
    }

    public function saveAction(File $file): ResponseInterface
    {
        return $this->jsonResponse(json_encode([
            'status' => 'success',
            'filename' => $file->getName(),
            'fileUid' => $this->uploadService->protectFileUid($file->getUid()),
            'fileDownload' => $file->getPublicUrl(),
            'resetAction' => $this->uriBuilder
                ->reset()
                ->setTargetPageType((int)$this->settings['typeNum'])
                ->setCreateAbsoluteUri(true)
                ->uriFor('delete', ['fileUid' => $this->uploadService->protectFileUid($file->getUid())]),
        ]));
    }

    /**
     * @param string $fileUid
     * @Extbase\Validate("\GAYA\UploadWidget\Validation\Validator\ProtectedFileUidValidator", param="fileUid")
     */
    public function deleteAction(string $fileUid): ResponseInterface
    {
        $file = $this->uploadService->getFile($fileUid);
        $file->delete();

        return $this->jsonResponse(json_encode([
            'status' => 'success',
        ]));
    }

    public function errorAction(): ResponseInterface
    {
        $flattenedErrors = [];

        foreach ($this->arguments->validate()->getFlattenedErrors() as $errors) {
            foreach ($errors as $error) {
                $flattenedErrors[] = $error->getMessage();
            }
        }

        return $this->jsonResponse(json_encode([
            'status' => 'error',
            'errors' => $flattenedErrors,
        ]));
    }
}
