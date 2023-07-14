<?php

declare(strict_types=1);

namespace Keenwork;

use ErrorException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory as SlimFactory;
use Slim\Exception\HttpNotFoundException;
use Throwable;
use Workerman\Worker;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;
use Slim\App;
use DI\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Workerman\Connection\ConnectionInterface;

use function is_string;

class Keenwork
{
    /**
     * Version Keenwork
     */
    public const VERSION = '0.4.0';

    /**
     * callable starts when starts worker
     * @var callable|null
     */
    private $callableAtStartHttp;

    /**
     * @var array<int, array{'interval': int, 'job': callable, 'params': string[],
     *     'init': callable|null, 'name': string, 'workers': int}>
     */
    private static array $jobs = [];

    /**
     * @var array<int, int>
     */
    private array $timerIDs;

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
        Worker::$pidFile = __DIR__ . '/../workerman.pid';
    }

    /**
     * Return config param value or the config at whole
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
     * Set up worker initialization code if needed
     *
     * @param callable $init
     */
    public function callableAtStartHttp(callable $init): void
    {
        $this->callableAtStartHttp = $init;
    }

    /**
     * Add periodic $job executed every $interval of seconds
     *
     * @param int      $interval
     * @param callable $job
     * @param string[] $params
     * @param callable|null $init
     * @param int      $workers
     * @param string   $name
     */
    public function addJob(
        int $interval,
        callable $job,
        array $params = [],
        callable $init = null,
        string $name = '',
        int $workers = 1
    ): void {
        self::$jobs[] = [
            'interval' => $interval,
            'job'      => $job,
            'params'   => $params,
            'init'     => $init,
            'name'     => $name,
            'workers'  => $workers,
        ];
    }

    /**
     * Run all servers
     * @param Keenwork $keenwork
     * @throws Throwable
     */
    public static function runAll(Keenwork $keenwork): void
    {
        try {
            $keenwork->initRun();
        } catch (\Throwable $e) {
            echo $e->getMessage() . "\n";
            echo "ERROR: Failed to start server\n";

            throw $e;
        }

        Worker::runAll();
    }

    /**
     * @return string
     */
    public function getHostHttp(): string
    {
        return $this->hostHttp;
    }

    /**
     * @return int
     */
    public function getPortHttp(): int
    {
        return $this->portHttp;
    }

    /**
     * @return bool
     */
    public function isDebugHttp(): bool
    {
        return $this->debugHttp;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return int
     */
    public function getWorkersHttp(): int
    {
        return $this->workersHttp;
    }

    /**
     * @return array<int, array{'interval': int, 'job': callable, 'params': string[],
     *     'init': callable|null, 'name': string, 'workers': int}>
     */
    public static function getJobs(): array
    {
        return self::$jobs;
    }

    /**
     * @return App
     */
    public function getSlim(): App
    {
        if ($this->slim === null) {
            throw new \UnderflowException("переменная slim не определена");
        }

        return $this->slim;
    }

    /**
     * Startup initialization
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
                    Worker::$stdoutFile = (string)$handler->getUrl();
                    break;
                }
            }
        }

        // Init JOB workers
        foreach (self::getJobs() as $job) {
            $w = new Worker();
            $w->count = $job['workers'];
            $w->name = 'Keenwork v' . self::VERSION .' [job] ' . $job['name'];
            $w->onWorkerStart = function () use ($job) {
                $this->addtTimerID((int)Timer::add($job['interval'], $job['job']));
            };
        }

        // Init HTTP workers
        $worker = new Worker('http://' . $this->hostHttp . ':' . $this->portHttp);
        $worker->count = $this->workersHttp;
        $worker->name = 'Keenwork v' . self::VERSION;

        if ($this->callableAtStartHttp) {
            $worker->onWorkerStart = $this->callableAtStartHttp;
        }

        /** Main Http */
        $worker->onMessage = function (ConnectionInterface $connection, WorkermanRequest $request) {
            try {
                $response = $this->_handle($request);
                $connection->send($response);
            } catch (HttpNotFoundException) {
                $connection->send(new WorkermanResponse(Response::HTTP_NOT_FOUND));
            } catch (\Throwable $error) {
                if ($this->isDebugHttp()) {
                    echo "\n[ERR] " . $error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage();
                }

                if (null !== $this->getLogger()) {
                    $this->getLogger()->error($error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage());
                }

                $connection->send(new WorkermanResponse(Response::HTTP_INTERNAL_SERVER_ERROR));
            }
        };
    }

    /**
     * Handle Workerman request to return Workerman response
     *
     * @param WorkermanRequest $request
     * @return WorkermanResponse
     * @throws ErrorException
     */
    private function _handle(WorkermanRequest $request): WorkermanResponse
    {
        if ($request->queryString() && is_string($request->queryString())) {
            parse_str($request->queryString(), $queryParams);
        } else {
            $queryParams = [];
        }

        $req = new Request(
            $request->method(),
            ($request->uri() instanceof UriInterface) || is_string($request->uri())
                ? $request->uri()
                : throw new ErrorException('Invalid uri'),
            (array) $request->header(),
            $request->rawBody(),
            (string) Config::get('http_protocol_version'),
            $_SERVER,
            (array)$request->cookie(),
            (array)$request->file(),
            $queryParams
        );

        $ret = $this->getSlim()->handle($req);

        $headers = $ret->getHeaders();

        if (!isset($headers['Server'])) {
            $headers['Server'] = 'Keenwork v' . self::VERSION;
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/plain; charset=utf-8';
        }

        return new WorkermanResponse(
            $ret->getStatusCode(),
            $headers,
            (string)$ret->getBody()
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
     * @param int $timerID
     * @return $this
     */
    private function addtTimerID(int $timerID): self
    {
        $this->timerIDs[$timerID] = $timerID;

        return $this;
    }
}
