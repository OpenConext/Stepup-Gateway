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
     * @var string
     */
    private $extraFieldName;

    /**
     * @param RequestId $requestId
     * @param string $extraFieldName
     */
    public function __construct(RequestId $requestId, $extraFieldName)
    {
        if (!is_string($extraFieldName)) {
            throw new \InvalidArgumentException('Field name must be string.');
        }

        $this->requestId = $requestId;
        $this->extraFieldName = $extraFieldName;
    }


    /**
     * Adds the request ID onto the record's extra data.
     *
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra'][$this->extraFieldName] = $this->requestId->get();

        return $record;
    }
}
