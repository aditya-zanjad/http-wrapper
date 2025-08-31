<?php

namespace AdityaZanjad\Http\Traits;

use AdityaZanjad\Http\Enums\Method;

/**
 * @version 1.0
 */
trait ValidationRules
{
    /**
     * Get the validation rules required to validate a single HTTP request.
     *
     * @return array<string, string>
     */
    final public function getRulesForSingleRequestValidation(): array
    {
        $validHttpMethods = Method::join();

        return [
            'url'           =>  'required|string|url',
            'method'        =>  "required|string|in:{$validHttpMethods}",
            'headers'       =>  'array|min:1',
            'headers.*'     =>  'required_with:headers|string|min:1',
            'body'          =>  'array|min:1',
            'body.*'        =>  'required_with:body|min:2',
            'body.*.field'  =>  'required_with:body|string|min:1',
            'body.*.value'  =>  'required_with:body|min:1'
        ];
    }

    /**
     * Validate multiple HTTP requests.
     *
     * @return array<int|string, array<string, string>>
     */
    final public function getRulesForBulkRequestsValidation(): array
    {
        $rulesOfInvidualRequest =   $this->getSingleRequestValidationRules();
        $validationRules        =   ['*' => 'required|array|min:2'];

        foreach ($rulesOfInvidualRequest as $path => $rules) {
            $validationRules["*.{$path}"] = $rules;
        }

        return $validationRules;
    }
}
