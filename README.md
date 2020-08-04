# JSON Schema Request Validator Bundle

Easily validate Symfony request bodies via JSON schema and automatically reject invalid requests

## Usage
The controller needs to implement the `JsonSchemaRequestValidationControllerInterface`.
All request bodies of its actions then will be validated with JSON schema files set via the interface method `setJsonSchemaFilePathsInFilePathProvider(FilePathProvider $filePathProvider)`.

All actions of this controller must have a JSON schema file which must be mapped via the route name.

The system automatically rejects an invalid incoming requests with status code "403 Forbidden".
If no JSON schema file can be found it will respond with "500 Internal Server Error".

## Example Symfony controller

```php
<?php

namespace AppBundle\Controller;

use Basilicom\JsonSchemaRequestValidator\Controller\JsonSchemaRequestValidationControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestingEndpointsController extends Controller implements JsonSchemaRequestValidationControllerInterface
{
    /**
     * @Route("/testing", methods={"POST"}, name="testing_post")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function testing(Request $request)
    {
        return JsonResponse::create(['success']);
    }

    public function setJsonSchemaFilePathsInFilePathProvider(FilePathProvider $filePathProvider)
    {
        $filePathProvider->setJsonSchemaFilePathForRouteName('testing_post', __DIR__ . '/../Resources/jsonschemas/test.json');
    }
}

```