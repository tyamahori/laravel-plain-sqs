<?php

namespace Tyamahori\PlainSqs\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class DispatcherJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected bool $plain = false;

    /**
     * DispatchedJob constructor.
     */
    public function __construct(
        protected array $data
    ) {
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        if ($this->isPlain()) {
            return $this->data;
        }

        return [
            'job' => Config::get('sqs-plain.default-handler'),
            'data' => $this->data
        ];
    }

    public function setPlain(bool $plain = true): self
    {
        $this->plain = $plain;

        return $this;
    }

    public function isPlain(): bool
    {
        return $this->plain;
    }
}
