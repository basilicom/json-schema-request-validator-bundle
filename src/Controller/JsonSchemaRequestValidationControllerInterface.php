<?php

namespace Basilicom\JsonSchemaRequestValidator\Controller;

use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\FilePathProvider;

interface JsonSchemaRequestValidationControllerInterface
{
    public function setJsonSchemaFilePathsInFilePathProvider(FilePathProvider $filePathProvider): void;
}
