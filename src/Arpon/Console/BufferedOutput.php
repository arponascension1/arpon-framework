<?php

namespace Arpon\Console;

use Symfony\Component\Console\Output\StreamOutput;

class BufferedOutput extends StreamOutput
{
    /**
     * Create a new BufferedOutput instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(fopen('php://memory', 'w'));
    }

    /**
     * Get the buffered output.
     *
     * @return string
     */
    public function fetch()
    {
        $stream = $this->getStream();
        rewind($stream);
        return stream_get_contents($stream) ?: '';
    }
}
