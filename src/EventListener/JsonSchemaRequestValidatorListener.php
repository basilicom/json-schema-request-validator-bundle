<?php

namespace Basilicom\JsonSchemaRequestValidator\EventListener;

use Basilicom\JsonSchemaRequestValidator\Controller\JsonSchemaRequestValidationControllerInterface;
use Basilicom\JsonSchemaRequestValidator\Exception\GeneralJsonSchemaRequestValidatorException;
use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\Exception\FileNotFoundException;
use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\Exception\NoFilePathProvidedException;
use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\FilePathProvider;
use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchemaValidator;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class JsonSchemaRequestValidatorListener
{
    /** @var JsonSchemaValidator */
    private $jsonSchemaValidator;

    public function __construct(JsonSchemaValidator $jsonSchemaValidator)
    {
        $this->jsonSchemaValidator = $jsonSchemaValidator;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!($controller instanceof JsonSchemaRequestValidationControllerInterface)) {
            return;
        }

        try {
            $jsonSchemaFilePath = $this->getJsonSchemaFilePath($event->getRequest(), $controller);
        } catch (FileNotFoundException | NoFilePathProvidedException $exception) {
            $event->setController(function () use ($exception) {
                return JsonResponse::create(['message' => 'Could not get json schema to validate request body'], Response::HTTP_INTERNAL_SERVER_ERROR);
            });

            return;
        }

        $content = $this->getRequestContent($event->getRequest());
        if (empty($content)) {
            $event->setController(function () {
                return JsonResponse::create(['message' => 'Request did not contain any valid content'], Response::HTTP_FORBIDDEN);
            });

            return;
        }

        try {
            if (!$this->jsonSchemaValidator->isValid($content, $jsonSchemaFilePath)) {
                $errors = $this->jsonSchemaValidator->getErrors($content, $jsonSchemaFilePath);
                $event->setController(function () use ($errors) {
                    return JsonResponse::create(['message' => 'Request content validation failed', 'errors' => $errors], Response::HTTP_FORBIDDEN);
                });
            }
        } catch (GeneralJsonSchemaRequestValidatorException $exception) {
            $event->setController(function () use ($exception) {
                return JsonResponse::create(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
            });
        }
    }

    /**
     * @param Request $request
     * @param JsonSchemaRequestValidationControllerInterface $controller
     *
     * @return string
     *
     * @throws FileNotFoundException
     * @throws NoFilePathProvidedException
     */
    private function getJsonSchemaFilePath(Request $request, JsonSchemaRequestValidationControllerInterface $controller): string
    {
        $jsonSchemaFilePathProvider = new FilePathProvider();
        $controller->setJsonSchemaFilePathsInFilePathProvider($jsonSchemaFilePathProvider);


        $routeName = $request->attributes->get('_route');

        return $jsonSchemaFilePathProvider->getJsonSchemaFilePathForRouteName($routeName);
    }

    /**
     * @param Request $request
     *
     * @return stdClass|null
     */
    private function getRequestContent(Request $request)
    {
        $json = $request->getContent();

        return json_decode($json);
    }
}
