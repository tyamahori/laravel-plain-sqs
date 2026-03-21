<?php

namespace Tests\PlainSqs;

use Aws\Sqs\SqsClient;
use Tyamahori\PlainSqs\Jobs\DispatcherJob;
use Tyamahori\PlainSqs\Sqs\Queue;
use Illuminate\Container\Container;
use JsonException;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\Test;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ReflectionException;
use ReflectionMethod;

/**
 * Class QueueTest
 * @package Dusterio\PlainSqs\Tests
 */
class QueueTest extends OrchestraTestCase
{
    use WithWorkbench;

    private Queue $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $sqsClient = $this->createStub(SqsClient::class);
        $container = Container::getInstance();

        $this->queue = new Queue(
            $sqsClient,
            'default',
            'https://sqs.us-east-1.amazonaws.com/123456789012/default'
        );
        $this->queue->setContainer($container);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('sqs-plain.default-handler', 'App\Jobs\DefaultHandler');
        $app['config']->set('sqs-plain.handlers', [
            'custom-queue' => 'App\Jobs\CustomHandler',
        ]);
    }

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    #[Test]
    public function createPayloadWithPlainDispatcherJob(): void
    {
        $data = ['foo' => 'bar', 'baz' => 'qux'];
        $job = new DispatcherJob($data);
        $job->setPlain(true);

        $method = new ReflectionMethod(Queue::class, 'createPayload');

        $payload = $method->invoke($this->queue, $job, 'default', '');
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertEquals($data, $decoded);
        $this->assertArrayNotHasKey('job', $decoded);
    }

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    #[Test]
    public function createPayloadWithNonPlainDispatcherJob(): void
    {
        $data = ['foo' => 'bar', 'baz' => 'qux'];
        $job = new DispatcherJob($data);
        $job->setPlain(false);

        $method = new ReflectionMethod(Queue::class, 'createPayload');

        $payload = $method->invoke($this->queue, $job, 'default', '');
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('job', $decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertEquals('App\Jobs\DefaultHandler@handle', $decoded['job']);
        $this->assertEquals($data, $decoded['data']['data']);
    }

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    #[Test]
    public function createPayloadWithNonPlainDispatcherJobAndCustomQueue(): void
    {
        $data = ['foo' => 'bar', 'baz' => 'qux'];
        $job = new DispatcherJob($data);
        $job->setPlain(false);

        $method = new ReflectionMethod(Queue::class, 'createPayload');

        $payload = $method->invoke($this->queue, $job, 'https://sqs.us-east-1.amazonaws.com/123456789012/custom-queue', '');
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('job', $decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertEquals('App\Jobs\CustomHandler@handle', $decoded['job']);
        $this->assertEquals($data, $decoded['data']['data']);
    }

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    #[Test]
    public function createPayloadWithNonDispatcherJob(): void
    {
        $job = 'App\Jobs\StandardJob';
        $data = ['foo' => 'bar'];

        $method = new ReflectionMethod(Queue::class, 'createPayload');

        $payload = $method->invoke($this->queue, $job, 'default', $data);
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('displayName', $decoded);
        $this->assertEquals($job, $decoded['displayName']);
    }
}
