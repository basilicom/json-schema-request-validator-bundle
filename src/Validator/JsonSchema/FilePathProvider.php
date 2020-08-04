<?php

namespace Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema;

use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\Exception\FileNotFoundException;
use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\Exception\NoFilePathProvidedException;

class FilePathProvider
{
    /** @var string[] */
    private $filePaths;

    /**
     * @param string $routeName
     *
     * @return string
     * @throws NoFilePathProvidedException
     * @throws FileNotFoundException
     */
    public function getJsonSchemaFilePathForRouteName(string $routeName): string
    {
        if (empty($this->filePaths[$routeName])) {
            throw new NoFilePathProvidedException('Json schema file path for route \'' . $routeName . '\' not provided. Set this file path in your Controller\'s setJsonSchemaFilePathsInFilePathProvider method.');
        }

        if (!file_exists($this->filePaths[$routeName])) {
            throw new FileNotFoundException('Json schema file not found at ' . $this->filePaths[$routeName]);
        }

        return $this->filePaths[$routeName];
    }

    public function setJsonSchemaFilePathForRouteName(string $routeName, string $jsonSchemaFilePath)
    {
        $this->filePaths[$routeName] = $jsonSchemaFilePath;
    }
}
