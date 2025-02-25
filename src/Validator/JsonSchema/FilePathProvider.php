<?php

namespace Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema;

use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\Exception\FileNotFoundException;
use Basilicom\JsonSchemaRequestValidator\Validator\JsonSchema\Exception\NoFilePathProvidedException;

class FilePathProvider
{
    /** @var string[] */
    private array $filePaths;

    /** @var string[] */
    private array $ignoredRouteNames;

    public function __construct()
    {
        $this->filePaths         = [];
        $this->ignoredRouteNames = [];
    }

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

    public function setJsonSchemaFilePathForRouteName(string $routeName, string $jsonSchemaFilePath): void
    {
        $this->filePaths[$routeName] = $jsonSchemaFilePath;
    }

    public function setIgnoreRouteName(string $routeName, bool $shouldBeIgnored = true): void
    {
        if (!$shouldBeIgnored && ($index = array_search($routeName, $this->ignoredRouteNames)) !== false) {
            // bastodo: fix this missing $ar
            array_splice($ar, $index, 1);
            return;
        }

        if ($shouldBeIgnored && !in_array($routeName, $this->ignoredRouteNames)) {
            $this->ignoredRouteNames[] = $routeName;
        }
    }

    public function shouldBeIgnored(string $routeName): bool
    {
        return in_array($routeName, $this->ignoredRouteNames);
    }
}
