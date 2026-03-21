<?php

namespace Tyamahori\PlainSqs\Sqs;

use Tyamahori\PlainSqs\Jobs\DispatcherJob;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Queue\Jobs\SqsJob;
use JsonException;

/**
 * Class CustomSqsQueue
 * @package App\Services
 */
class Queue extends SqsQueue
{
    /**
     * Create a payload string from the given job and data.
     * @throws JsonException
     */
    protected function createPayload($job, $queue, $data = '', $delay = null): string
    {
        if (!$job instanceof DispatcherJob) {
            return parent::createPayload($job, $queue, $data, $delay);
        }

        $handlerJob = $this->getClass($queue) . '@handle';

        if ($job->isPlain()) {
            return json_encode($job->getPayload(), JSON_THROW_ON_ERROR);
        }
        return json_encode(['job' => $handlerJob, 'data' => $job->getPayload()], JSON_THROW_ON_ERROR);
    }

    /**
     * @param string|null $queue
     * @return string
     */
    private function getClass(string|null $queue = null): string
    {
        if ($queue === null) {
            return Config::get('sqs-plain.default-handler');
        }

        $parts = explode('/', $queue);
        $queue = end($parts);

        if (array_key_exists($queue, Config::get('sqs-plain.handlers'))) {
            return Config::get('sqs-plain.handlers')[$queue];
        }
        return Config::get('sqs-plain.default-handler');
    }

    /**
     * @param $queue
     * @return SqsJob|null
     * @throws JsonException
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (isset($response['Messages']) && count($response['Messages']) > 0) {
            $queueId = explode('/', $queue);
            $queueId = array_pop($queueId);

            $class = (array_key_exists($queueId, $this->container['config']->get('sqs-plain.handlers')))
                ? $this->container['config']->get('sqs-plain.handlers')[$queueId]
                : $this->container['config']->get('sqs-plain.default-handler');

            $response = $this->modifyPayload($response['Messages'][0], $class);

            return new SqsJob($this->container, $this->sqs, $response, $this->connectionName, $queue);
        }

        return null;
    }


    /**
     * @throws JsonException
     */
    private function modifyPayload(string|array $payload, string $class): array
    {
        if (! is_array($payload)) {
            $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        }

        $body = json_decode($payload['Body'], true, 512, JSON_THROW_ON_ERROR);

        $body = [
            'job' => $class . '@handle',
            'data' => $body['data'] ?? $body,
            'uuid' => $payload['MessageId']
        ];

        $payload['Body'] = json_encode($body, JSON_THROW_ON_ERROR);

        return $payload;
    }

    /**
     * @param string $payload
     * @param null $queue
     * @param array $options
     * @return mixed|null
     * @throws JsonException
     */
    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (isset($payload['data'], $payload['job'])) {
            $payload = $payload['data'];
        }

        return parent::pushRaw(json_encode($payload, JSON_THROW_ON_ERROR), $queue, $options);
    }
}
