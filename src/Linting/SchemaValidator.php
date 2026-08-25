<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Linting;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Validates a manifest against `resources/schema/template.schema.json`.
 *
 * The schema is the contract a community bundle is accepted under, so it is
 * enforced by the same code that documents it rather than by a checklist in a
 * contributing guide.
 */
class SchemaValidator
{
    private ?Validator $validator = null;

    public function __construct(private readonly ?string $schemaPath = null) {}

    /**
     * @return array<int, string> human-readable violations, empty when valid
     */
    public function validate(string $json): array
    {
        $data = json_decode($json, false);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['template.json is not valid JSON: '.json_last_error_msg()];
        }

        $result = $this->validator()->validate($data, file_get_contents($this->path()) ?: '{}');

        if ($result->isValid()) {
            return [];
        }

        $error = $result->error();

        if ($error === null) {
            return [];
        }

        $messages = [];

        foreach ((new ErrorFormatter)->format($error) as $pointer => $errors) {
            foreach ($errors as $message) {
                $messages[] = ($pointer === '/' ? '' : trim($pointer, '/').': ').$message;
            }
        }

        return $messages;
    }

    private function path(): string
    {
        return $this->schemaPath ?? __DIR__.'/../../resources/schema/template.schema.json';
    }

    private function validator(): Validator
    {
        return $this->validator ??= new Validator;
    }
}
