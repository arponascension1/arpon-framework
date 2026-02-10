<?php

namespace Arpon\Http;

use JsonException;

class JsonResponse extends Response
{
    protected int $encodingOptions;

    public function __construct($data = [], int $statusCode = 200, array $headers = [], int $encodingOptions = 0)
    {
        $this->encodingOptions = $encodingOptions;

        $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';

        parent::__construct($this->encode($data), $statusCode, $headers);
    }

    public function setData($data): static
    {
        $this->setContent($this->encode($data));

        return $this;
    }

    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    public function setEncodingOptions(int $encodingOptions): static
    {
        $this->encodingOptions = $encodingOptions;

        return $this;
    }

    protected function encode($data): string
    {
        try {
            return json_encode($data, $this->encodingOptions | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \InvalidArgumentException('Unable to encode data to JSON: ' . $e->getMessage(), 0, $e);
        }
    }
}

