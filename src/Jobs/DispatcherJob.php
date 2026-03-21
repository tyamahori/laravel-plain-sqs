<?php

namespace Tyamahori\PlainSqs\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
