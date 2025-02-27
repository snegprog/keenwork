<?php

declare(strict_types=1);

namespace App\Keenwork;

use Cron\CronExpression;
use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory as SlimFactory;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;
use Workerman\Worker;

class Keenwork
{
    /**
     * Version Keenwork.
     */
    public const VERSION = '0.6.3';

    /**
     * callable starts when starts worker.
     *
     * @var callable|null
     */
    private $callableAtStartHttp;

    /**
     * @var array<int, array{'interval': float, 'job': callable, 'params': string[],
     *     'init': callable|null, 'name': string, 'workers': int}>
     */
    private static array $jobs = [];

    /**
     * @var array<int, array{'cronExpression': CronExpression, 'job': callable, 'name': string}>
     */
    private static array $shedule = [];

    /**
     * @var array<int, int>
     */
    private array $timerIDs;

    /**
     * @param App<Container>|App<ContainerInterface>|null $slim
     */
    public function __construct(
        private ?App $slim = null,
        readonly ?LoggerInterface $logger = null,
        readonly private string $hostHttp = '0.0.0.0',
        readonly private int $portHttp = 8080,
        readonly private bool $debugHttp = false,
        readonly private ContainerInterface $containerHttp = new Container(),
        readonly private int $workersHttp = 1,
    ) {
        $this->slim = $this->slim ?? SlimFactory::create(null, $this->containerHttp);
        $this->callableAtStartHttp = null;
        $this->timerIDs = [];
        Worker::$pidFile = __DIR__.'/../workerman.pid';
    }

    /**
     * Return config param value or the config at whole.
     *
     * @return array{'host': string, 'port': int, 'debug': bool, 'workers': int} - config http data
     */
    public function getConfigsHttp(): array
    {
        return [
            'host' => $this->getHostHttp(),
            'port' => $this->getPortHttp(),
            'debug' => $this->isDebugHttp(),
            'workers' => $this->getWorkersHttp(),
        ];
    }

    /**
     * Set up worker initialization code if needed.
     */
    public function callableAtStartHttp(callable $init): void
    {
        $this->callableAtStartHttp = $init;
    }

    /**
     * Add periodic $job executed every $interval of seconds.
     *
     * @param string[] $params
     */
    public function addJob(
        float $interval,
        callable $job,
        array $params = [],
        ?callable $init = null,
        string $name = '',
        int $workers = 1,
    ): void {
        self::$jobs[] = [
            'interval' => $interval,
            'job' => $job,
            'params' => $params,
            'init' => $init,
            'name' => $name,
            'workers' => $workers,
        ];
    }

    public function addShedule(CronExpression $cronExpression, callable $job, string $name = ''): void
    {
        self::$shedule[] = [
            'cronExpression' => $cronExpression,
            'job' => $job,
            'name' => $name,
        ];
    }

    /**
     * Run all servers.
     *
     * @throws \Throwable
     */
    public static function runAll(Keenwork $keenwork): void
    {
        try {
            $keenwork->initRun();
        } catch (\Throwable $e) {
            echo $e->getMessage()."\n";
            echo "ERROR: Failed to start server\n";

            throw $e;
        }

        Worker::runAll();
    }

    public function getHostHttp(): string
    {
        return $this->hostHttp;
    }

    public function getPortHttp(): int
    {
        return $this->portHttp;
    }

    public function isDebugHttp(): bool
    {
        return $this->debugHttp;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getWorkersHttp(): int
    {
        return $this->workersHttp;
    }

    /**
     * @return array<int, array{'interval': float, 'job': callable, 'params': string[],
     *     'init': callable|null, 'name': string, 'workers': int}>
     */
    public static function getJobs(): array
    {
        return self::$jobs;
    }

    /**
     * @return array<int, array{'cronExpression': CronExpression, 'job': callable, 'name': string}>
     */
    public static function getShedules(): array
    {
        return self::$shedule;
    }

    /**
     * @return App<Container>|App<ContainerInterface>
     */
    public function getSlim(): App
    {
        if (null === $this->slim) {
            throw new \UnderflowException('переменная slim не определена');
        }

        return $this->slim;
    }

    private function shedule(CronExpression $cronExpression, callable $job): void
    {
        if (!$cronExpression->isDue(new \DateTime())) {
            return;
        }

        $job();
    }

    /**
     * Startup initialization.
     */
    private function initRun(): void
    {
        // Write worker output to log file if exists
        if (null !== $this->getLogger() && $this->getLogger() instanceof Logger) {
            foreach ($this->getLogger()->getHandlers() as $handler) {
                if (!($handler instanceof StreamHandler)) {
                    continue;
                }
                if ($handler->getUrl()) {
                    Worker::$stdoutFile = (string) $handler->getUrl();
                    break;
                }
            }
        }

        // Init JOB workers
        foreach (self::getJobs() as $job) {
            $w = new Worker();
            $w->count = $job['workers'];
            $w->name = 'Keenwork v'.self::VERSION.' [job] '.$job['name'];
            $w->onWorkerStart = function () use ($job) {
                $this->addtTimerID((int) Timer::add($job['interval'], $job['job']));
            };
        }

        foreach (self::getShedules() as $shedule) {
            $w = new Worker();
            $w->name = 'Keenwork v'.self::VERSION.' [shedule] '.$shedule['name'];
            $w->onWorkerStart = function () use ($shedule) {
                $sh = function () use ($shedule) {
                    $this->shedule($shedule['cronExpression'], $shedule['job']);
                };
                $this->addtTimerID((int) Timer::add(60, $sh));
            };
        }

        // Init HTTP workers
        $worker = new Worker('http://'.$this->hostHttp.':'.$this->portHttp);
        $worker->count = $this->workersHttp;
        $worker->name = 'Keenwork v'.self::VERSION;

        if ($this->callableAtStartHttp) {
            $worker->onWorkerStart = $this->callableAtStartHttp;
        }

        /* Main Http */
        $worker->onMessage = function (ConnectionInterface $connection, WorkermanRequest $request) {
            try {
                $response = $this->_handle($request);
                $connection->send($response);
            } catch (HttpNotFoundException) {
                $connection->send(new WorkermanResponse(Response::HTTP_NOT_FOUND));
            } catch (\Throwable $error) {
                if ($this->isDebugHttp()) {
                    echo "\n[ERR] ".$error->getFile().':'.$error->getLine().' >> '.$error->getMessage();
                }

                if (null !== $this->getLogger()) {
                    $this->getLogger()->error($error->getFile().':'.$error->getLine().' >> '.$error->getMessage());
                }

                $connection->send(new WorkermanResponse(Response::HTTP_INTERNAL_SERVER_ERROR));
            }
        };
    }

    /**
     * Handle Workerman request to return Workerman response.
     *
     * @throws \ErrorException
     */
    private function _handle(WorkermanRequest $request): WorkermanResponse
    {
        if ($request->queryString() && \is_string($request->queryString())) {
            parse_str($request->queryString(), $queryParams);
        } else {
            $queryParams = [];
        }

        $req = new Request(
            $request->method(),
            ($request->uri() instanceof UriInterface) || \is_string($request->uri())
                ? $request->uri()
                : throw new \ErrorException('Invalid uri'),
            (array) $request->header(),
            $request->rawBody(),
            (string) Config::get('http_protocol_version'),
            $_SERVER,
            (array) $request->cookie(),
            (array) $request->file(),
            $queryParams
        );

        $ret = $this->getSlim()->handle($req);

        $headers = $ret->getHeaders();

        if (!isset($headers['Server'])) {
            $headers['Server'] = 'Keenwork v'.self::VERSION;
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/plain; charset=utf-8';
        }

        return new WorkermanResponse(
            $ret->getStatusCode(),
            $headers,
            (string) $ret->getBody()
        );
    }

    /**
     * @param array<int, array{'interval': int, 'job': callable, 'params': string[],
     *     'init': callable|null, 'name': string, 'workers': int}> $jobs
     */
    public static function setJobs(array $jobs): void
    {
        self::$jobs = $jobs;
    }

    /**
     * @return array<int, int>
     */
    public function getTimerIDs(): array
    {
        return $this->timerIDs;
    }

    /**
     * @param array<int, int> $timerIDs
     */
    public function setTimerIDs(array $timerIDs): self
    {
        $this->timerIDs = $timerIDs;

        return $this;
    }

    /**
     * @return $this
     */
    private function addtTimerID(int $timerID): self
    {
        $this->timerIDs[$timerID] = $timerID;

        return $this;
    }
}
