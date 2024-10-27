<?php

declare(strict_types=1);

namespace StitchDigital\LaravelSimproApi\Requests\Quotes\Sections\CostCenters\Prebuilds;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteQuoteSectionCostCenterPrebuild extends Request
{
    public function __construct(protected readonly int $prebuildId, protected string $costCenterId, protected string $sectionId, protected readonly int $quoteId, protected int $companyId)
    {
        //
    }

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::DELETE;

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/companies/'.$this->companyId.'/quotes/'.$this->quoteId.'/sections/'.$this->sectionId.'/costCenters/'.$this->costCenterId.'/prebuilds/'.$this->prebuildId;
    }
}
