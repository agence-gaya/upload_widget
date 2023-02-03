<?php

declare(strict_types=1);

namespace GAYA\UploadWidget\ViewHelpers\Form;

use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use GAYA\UploadWidget\Service\UploadService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class UploadViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    protected UriBuilder $uriBuilder;

    protected HashService $hashService;

    protected UploadService $uploadService;

    public function __construct(UriBuilder $uriBuilder, HashService $hashService, UploadService $uploadService)
    {
        parent::__construct();

        $this->uriBuilder = $uriBuilder;
        $this->hashService = $hashService;
        $this->uploadService = $uploadService;
    }

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('class', 'string', 'Class of input tag', false, '');
        $this->registerArgument('id', 'string', 'ID of input tag', true);
        $this->registerArgument('typeNum', 'int', 'Typenum used to receive the file');
        $this->registerArgument('uploadResultTag', 'string', 'Upload result tag', false, 'div');
    }

    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->setRespectSubmittedDataValue(true);

        if (isset($this->arguments['typeNum'])) {
            $uri = $this->getUploadUri((int)$this->arguments['typeNum']);
        } else {
            $settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'uploadWidget');
            $uri = $this->getUploadUri((int)$settings['typeNum']);
        }

        $file = null;
        $displayResult = false;
        if ($protectedFileUid = $this->getValueAttribute()) {
            try {
                $file = $this->uploadService->getFile($protectedFileUid);
                $displayResult = true;
            } catch (\Exception $e) {
            }
        }

        $inputFile = new TagBuilder();
        $inputFile->setTagName('input');
        $inputFile->addAttribute('type', 'file');
        $inputFile->addAttribute('id', $this->arguments['id'] . '_upload');
        $inputFile->addAttribute('class', $this->arguments['class'] . ' upload-progress-field');
        $inputFile->addAttribute('data-uri', $uri);
        if ($this->hasArgument('data') && is_array($this->arguments['data'])) {
            foreach ($this->arguments['data'] as $dataAttributeKey => $dataAttributeValue) {
                $inputFile->addAttribute('data-' . $dataAttributeKey, $dataAttributeValue);
            }
        }
        if ($displayResult) {
            $inputFile->addAttribute('style', 'display: none');
        }

        $fileHidden = new TagBuilder();
        $fileHidden->setTagName('input');
        $fileHidden->addAttribute('type', 'hidden');
        $fileHidden->addAttribute('id', $this->arguments['id']);
        $fileHidden->addAttribute('name', $name);
        $fileHidden->addAttribute('value', (string)$this->getValueAttribute());

        $progress = new TagBuilder();
        $progress->setTagName('progress');
        $progress->addAttribute('class', 'upload-progress');
        $progress->addAttribute('max', '100');
        $progress->addAttribute('value', '0');
        $progress->addAttribute('id', $this->arguments['id'] . '_progress');
        $progress->forceClosingTag(true);

        $progressContainer = new TagBuilder();
        $progressContainer->setTagName('div');
        $progressContainer->addAttribute('class', $this->arguments['class'] . ' upload-progress-container');
        $progressContainer->setContent($progress->render());
        $progressContainer->forceClosingTag(true);

        $uploadResult = new TagBuilder();
        $uploadResult->setTagName('span');
        $uploadResult->addAttribute('id', $this->arguments['id'] . '_result');
        $uploadResult->forceClosingTag(true);
        if ($file) {
            $link = new TagBuilder();
            $link->setTagName('a');
            $link->addAttribute('href', '/' . $file->getPublicUrl());
            $link->addAttribute('target', '_blank');
            $link->setContent($file->getName());

            $uploadResult->setContent($link->render());
        }

        $resetUpload = new TagBuilder();
        $resetUpload->setTagName('span');
        $resetUpload->addAttribute('class', 'upload-reset');
        $resetUpload->addAttribute('id', $this->arguments['id'] . '_reset');
        $resetUpload->addAttribute('title', LocalizationUtility::translate('LLL:EXT:upload_widget/Resources/Private/Language/locallang.xlf:remove-file'));
        $resetUpload->forceClosingTag(true);

        $uploadResultContainer = new TagBuilder();
        $uploadResultContainer->setTagName($this->arguments['uploadResultTag']);
        $uploadResultContainer->addAttribute('class', $this->arguments['class'] . ' upload-result-container');
        $uploadResultContainer->forceClosingTag(true);
        $uploadResultContainer->setContent($uploadResult->render() . $resetUpload->render());
        if ($displayResult) {
            $uploadResultContainer->addAttribute('style', 'display: block');
        }

        return $inputFile->render() . $fileHidden->render() . $progressContainer->render() . $uploadResultContainer->render();
    }

    protected function getUploadUri(int $typeNum): string
    {
        return $this->uriBuilder
            ->setTargetPageType($typeNum)
            ->setCreateAbsoluteUri(true)
            ->uriFor('save', [], 'Upload', 'UploadWidget', 'Upload');
    }
}
