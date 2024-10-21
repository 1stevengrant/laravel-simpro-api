<?php

declare(strict_types=1);

namespace StitchDigital\LaravelSimproApi\Requests\Sites\CustomFields;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RetrieveSiteCustomField extends Request
{
    public function __construct(protected readonly int $customFieldId, protected readonly int $siteId, protected readonly int $companyId)
    {
        //
    }

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/companies/'.$this->companyId.'/sites/'.$this->siteId.'/customFields/'.$this->customFieldId;
    }
}
