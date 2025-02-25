# JSON Schema Request Validator Bundle

Easily validate Symfony request bodies via JSON schema and automatically reject invalid requests

## Version
| Version | Symfony        |
|---------|----------------|
| 1.x     | 3.4.x or 4.1.x |
| 2.x     | 5.3.x          |
| 3.x     | 6.4.x or 7.0.x |

## Installation

via composer
```
composer require basilicom/json-schema-request-validator-bundle
```

```php
use Basilicom\JsonSchemaRequestValidator\JsonSchemaRequestValidatorBundle;
// ...
return [
    // ...
    JsonSchemaRequestValidatorBundle::class => ['all' => true],
];
```

## Usage
The controller needs to implement the `JsonSchemaRequestValidationControllerInterface`.
All request bodies of its actions then will be validated with JSON schema files set via the interface method `setJsonSchemaFilePathsInFilePathProvider(FilePathProvider $filePathProvider)`.

All actions of this controller must have a JSON schema file which must be mapped via the route name.

The system automatically rejects an invalid incoming requests with status code "400 Bad Request".
If no JSON schema file can be found it will respond with "500 Internal Server Error".

## Example Symfony controller

```php
<?php

namespace AppBundle\Controller;

use Basilicom\JsonSchemaRequestValidator\Controller\JsonSchemaRequestValidationControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestingEndpointsController extends AbstractController implements JsonSchemaRequestValidationControllerInterface
{
    /**
     * @Route("/testing", methods={"POST"}, name="testing_post")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function testingPost(Request $request): JsonResponse
    {
        return new JsonResponse(['success']);
    }
    
    /**
     * @Route("/testing", methods={"GET"}, name="testing_get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function testingGet(Request $request): JsonResponse
    {
        return new JsonResponse(['success']);
    }

    public function setJsonSchemaFilePathsInFilePathProvider(FilePathProvider $filePathProvider)
    {
        $filePathProvider->setIgnoreRouteName('testing_get', true);
        $filePathProvider->setJsonSchemaFilePathForRouteName('testing_post', __DIR__ . '/../Resources/jsonschemas/test.json');
    }
}

```
