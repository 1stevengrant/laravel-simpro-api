<?php

declare(strict_types=1);

namespace StitchDigital\LaravelSimproApi\Requests\Quotes\Sections\CostCenters\OneOffs;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RetrieveQuoteSectionCostCenterOneOff extends Request
{
    public function __construct(protected readonly int $oneOffId, protected readonly int $costCenterId, protected readonly int $sectionId, protected readonly int $quoteId, protected readonly int $companyId)
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
        return '/companies/'.$this->companyId.'/quotes/'.$this->quoteId.'/sections/'.$this->sectionId.'/costCenters/'.$this->costCenterId.'/oneOffs/'.$this->oneOffId;
    }
}
