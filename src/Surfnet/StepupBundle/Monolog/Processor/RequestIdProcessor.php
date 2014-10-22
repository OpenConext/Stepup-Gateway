<?php

namespace Surfnet\StepupBundle\Monolog\Processor;

use Surfnet\StepupBundle\Request\RequestId;

class RequestIdProcessor
{
    /**
     * @var RequestId
     */
    private $requestId;

    /**
     * @param RequestId $requestId
     */
    public function __construct(RequestId $requestId)
    {
        $this->requestId = $requestId;
    }


    /**
     * Adds the request ID onto the record's extra data.
     *
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra']['request_id'] = $this->requestId->get();

        return $record;
    }
}
