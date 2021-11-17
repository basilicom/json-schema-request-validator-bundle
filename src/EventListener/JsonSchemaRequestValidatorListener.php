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
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class JsonSchemaRequestValidatorListener
{
    /** @var JsonSchemaValidator */
    private $jsonSchemaValidator;

    public function __construct(JsonSchemaValidator $jsonSchemaValidator)
    {
        $this->jsonSchemaValidator = $jsonSchemaValidator;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!($controller instanceof JsonSchemaRequestValidationControllerInterface)) {
            return;
        }

        $jsonSchemaFilePathProvider = new FilePathProvider();
        $controller->setJsonSchemaFilePathsInFilePathProvider($jsonSchemaFilePathProvider);

        if ($this->routeShouldBeIgnored($event->getRequest(), $jsonSchemaFilePathProvider)) {
            return;
        }

        try {
            $jsonSchemaFilePath = $this->getJsonSchemaFilePath($event->getRequest(), $jsonSchemaFilePathProvider);
        } catch (FileNotFoundException | NoFilePathProvidedException $exception) {
            $event->setController(function () use ($exception) {
                return new JsonResponse(
                    ['message' => 'Could not get json schema to validate request body'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            });

            return;
        }

        $content = $this->getRequestContent($event->getRequest());
        if (empty($content)) {
            $event->setController(function () {
                return new JsonResponse(
                    ['message' => 'Request did not contain any valid content'],
                    Response::HTTP_BAD_REQUEST
                );
            });

            return;
        }

        try {
            if (!$this->jsonSchemaValidator->isValid($content, $jsonSchemaFilePath)) {
                $errors = $this->jsonSchemaValidator->getErrors($content, $jsonSchemaFilePath);
                $event->setController(function () use ($errors) {
                    return new JsonResponse(
                        ['message' => 'Request content validation failed', 'errors' => $errors],
                        Response::HTTP_BAD_REQUEST
                    );
                });
            }
        } catch (GeneralJsonSchemaRequestValidatorException $exception) {
            $event->setController(function () use ($exception) {
                return new JsonResponse(
                    ['message' => $exception->getMessage()],
                    Response::HTTP_BAD_REQUEST
                );
            });
        }
    }

    /**
     * @param Request $request
     * @param FilePathProvider $jsonSchemaFilePathProvider
     *
     * @return string
     *
     * @throws FileNotFoundException
     * @throws NoFilePathProvidedException
     */
    private function getJsonSchemaFilePath(Request $request, FilePathProvider $jsonSchemaFilePathProvider): string
    {
        $routeName = $this->getRouteName($request);

        return $jsonSchemaFilePathProvider->getJsonSchemaFilePathForRouteName($routeName);
    }

    /**
     * @param Request $request
     * @param FilePathProvider $jsonSchemaFilePathProvider
     *
     * @return bool
     */
    private function routeShouldBeIgnored(Request $request, FilePathProvider $jsonSchemaFilePathProvider): bool
    {
        $routeName = $this->getRouteName($request);

        return $jsonSchemaFilePathProvider->shouldBeIgnored($routeName);
    }

    /**
     * @param Request $request
     *
     * @return stdClass|array|null
     */
    private function getRequestContent(Request $request)
    {
        $json = $request->getContent();

        return json_decode($json);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function getRouteName(Request $request): string
    {
        return $request->attributes->get('_route');
    }
}
