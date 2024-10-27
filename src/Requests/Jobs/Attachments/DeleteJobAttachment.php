<?php

declare(strict_types=1);

namespace StitchDigital\LaravelSimproApi\Requests\Jobs\Attachments;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteJobAttachment extends Request
{
    public function __construct(protected string $attachmentId, protected readonly int $jobId, protected int $companyId)
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
        return '/companies/'.$this->companyId.'/jobs/'.$this->jobId.'/attachments/files/'.$this->attachmentId;
    }
}
