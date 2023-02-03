# TYPO3 Extension upload_widget

This extension provides a frontend widget to upload files in extbase forms.

This includes:

- a view helper for your template
- a controller to store the uploaded file in FAL
- a property validator for your model (DTO)
- a service to create a reference between the uploaded file and your final model

## Installation

Intall upload_widget with Composer:

`composer require gaya/upload_widget`

## Configuration

In your root template, include the TypoScript "Upload Widget"

## Usage

You can test this extension by installing gaya/upload_widget_example. All explanations below come from this.

### Form model

Create a DTO for your form and add the validator for the file property:

```
/**
 * @var string
 * @Extbase\Validate("NotEmpty")
 * @Extbase\Validate("\GAYA\UploadWidget\Validation\Validator\ProtectedFileUidValidator")
 */
protected string $file = '';
```

### Template

Call the view helper in your form template:

```
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"
      xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers"
      xmlns:uw="http://typo3.org/ns/GAYA/UploadWidget/ViewHelpers"
      data-namespace-typo3-fluid="true">

<f:form action="create" objectName="exampleForm" object="{exampleForm}" class="form">
    <div class="form-group">
        <label for="file">File</label>
        <uw:form.upload property="file" id="file" class="form-control" />
    </div>
</f:form>

</html>
```

### Controller

When your DTO is validated, you simply have to create a reference between the uploaded file and your stored object:

```
public function createAction(ExampleForm $exampleForm)
{
    $example = GeneralUtility::makeInstance(Example::class);
    $example->setFile(
        $this->uploadService->createExtbaseFileReferenceFromFile(
            $this->uploadService->getFile($exampleForm->getFile()),
            'tx_uploadwidgetexample_domain_model_example'
        )
    );

    $this->exampleRepository->add($example);
}
```
