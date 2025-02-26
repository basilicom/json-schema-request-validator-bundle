<?php

namespace Basilicom\JsonSchemaRequestValidator\Validator;

use Basilicom\JsonSchemaRequestValidator\Validator\Exception\DecodingFileException;
use Basilicom\JsonSchemaRequestValidator\Validator\Exception\FileNotFoundException;
use Basilicom\JsonSchemaRequestValidator\Validator\Exception\ReadingFileException;
use JsonSchema\Validator;
use stdClass;

readonly class JsonSchemaValidator
{
    public function __construct(private Validator $jsonSchemaValidator)
    {
    }

    /**
     * @param $jsonObject
     * @param string $schemaFilePath
     *
     * @return bool
     *
     * @throws DecodingFileException
     * @throws FileNotFoundException
     * @throws ReadingFileException
     */
    public function isValid($jsonObject, string $schemaFilePath): bool
    {
        $this->validate($jsonObject, $schemaFilePath);

        return $this->jsonSchemaValidator->isValid();
    }

    /**
     * @param $jsonObject
     * @param string $schemaFilePath
     *
     * @return array
     *
     * @throws DecodingFileException
     * @throws FileNotFoundException
     * @throws ReadingFileException
     */
    public function getErrors($jsonObject, string $schemaFilePath): array
    {
        $this->validate($jsonObject, $schemaFilePath);

        return $this->jsonSchemaValidator->getErrors();
    }

    /**
     * @param $jsonObject
     * @param string $schemaFilePath
     *
     * @throws DecodingFileException
     * @throws FileNotFoundException
     * @throws ReadingFileException
     */
    private function validate($jsonObject, string $schemaFilePath): void
    {
        $schemaObject = $this->getJsonSchemaObject($schemaFilePath);
        $this->jsonSchemaValidator->reset();
        $this->jsonSchemaValidator->validate($jsonObject, $schemaObject);
    }

    /**
     * @param string $schemaFilePath
     *
     * @return stdClass
     *
     * @throws FileNotFoundException
     * @throws ReadingFileException
     * @throws DecodingFileException
     */
    private function getJsonSchemaObject(string $schemaFilePath): stdClass
    {
        if (!file_exists($schemaFilePath)) {
            throw new FileNotFoundException('Could not find file ' . $schemaFilePath);
        }

        $fileContent = file_get_contents($schemaFilePath);
        if (empty($fileContent)) {
            throw new ReadingFileException('Could not get contents of file ' . $schemaFilePath);
        }

        $jsonObject = json_decode($fileContent);
        if (empty($jsonObject)) {
            throw new DecodingFileException('Could not generate json object from file ' . $schemaFilePath);
        }

        return $jsonObject;
    }
}
