<?php

declare(strict_types=1);

namespace Keenwork;

use Keenwork\Factory\Psr17Factory;
use Keenwork\Middleware\Middleware;
use ErrorException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory as SlimFactory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Exception\HttpNotFoundException;
use Workerman\Worker;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;
use Slim\App;
use Rakit\Validation\Validator;
use DI\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use function is_string;

class Keenwork
{
    /**
     * Version Keenwork
     */
    public const VERSION = '0.4.0';

    /**
     * WEB Slim App
     * @var App $slim
     */
    private ?App $slim;

    /**
     * host web server
     * @var string
     */
    private string $hostHttp;

    /**
     * port web server
     * @var int
     */
    private int $portHttp;

    /**
     * enable|disable debag mode for workerman
     * @var bool
     */
    private bool $debugHttp;

    /**
     * logger PSR-3 for Slim
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;

    /**
     * container PSR-11 for Slim
     * @var ContainerInterface|null
     */
    private ?ContainerInterface $containerHttp;

    /**
     * callable starts when starts worker
     * @var callable|null
     */
    private $callableAtStartHttp;

    /**
     * Number of workers workerman
     * @var int
     */
    private int $workersHttp;

    /**
     * working http, flag
     * @var bool
     */
    private bool $workingHttp;

    /**
     * data initialization http, flag
     * @var bool
     */
    private bool $dataInitHttp;

    /**
     * @var array<int, array{'interval': int, 'job': callable, 'params': string[],
     *     'init': callable|null, 'name': string, 'workers': int}>
     */
    private static array $jobs = [];

    /**
     * @var array<int, int>
     */
    private array $timerIDs;

    /**
     * Keenwork constructor.
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->slim = null;
        $this->hostHttp = '0.0.0.0';
        $this->portHttp = 8080;
        $this->debugHttp = false;
        $this->logger = $logger;
        $this->containerHttp = null;
        $this->callableAtStartHttp = null;
        $this->workersHttp = 0;
        $this->workingHttp = false;
        $this->dataInitHttp = false;
        $this->timerIDs = [];
        Worker::$pidFile = __DIR__ . '/../workerman.pid';
    }

    /**
     * Init http server
     * @psalm-param  array{'debug': bool, 'host': string, 'port': int, 'workers': int} $config
     */
    public function initHttp(array $config): void
    {
        if ($this->isDataInitHttp()) {
            echo "You can't initialize data http twice\n";

            return;
        }
        if ($this->isWorkingHttp()) {
            echo "You can't initialize data http while the server is running\n";

            return;
        }

        $validator = new Validator();
        $validation = $validator->make($config, [
            'host' => 'required|ip',
            'port' => 'required|integer',
            'workers' => 'required|integer',
            'debug' => 'required|boolean',
        ]);
        $validation->validate();
        if ($validation->fails()) {
            $stringErrors = '[';
            foreach ($validation->errors()->toArray() as $key => $error) {
                $stringErrors .= ' ' .$key . ' ';
            }
            $stringErrors .= ']';

            throw new \InvalidArgumentException('ERROR: initHttp(): invalid argument(s): ' . $stringErrors . '.');
        }

        $this->setHost($config['host']);
        $this->setPort($config['port']);
        $this->setDebugHttp($config['debug']);
        $this->setWorkersHttp($config['workers']);

        $this->setContainerHttp(new Container());
        $provider = new Psr17FactoryProvider();
        $provider::setFactories([Psr17Factory::class]);
        SlimFactory::setPsr17FactoryProvider($provider);
        $this->setSlim(SlimFactory::create(null, $this->getContainerHttp()));
        $this->getSlim()->add(new Middleware());

        $this->setDataInitHttp(true);
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
     * Run all servers Keenwork
     */
    public static function runAll(Keenwork ...$keenworks): void
    {
        $start = true;
        try {
            foreach ($keenworks as $keenwork) {
                $keenwork->initRun();
            }
        } catch (\Throwable $e) {
            echo $e->getMessage() . "\n";
            $start = false;
        }

        if (!$start) {
            echo "ERROR: Failed to start server\n";

            return;
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
        $worker->onMessage = function ($connection, WorkermanRequest $request) {
            try {
                $response = $this->_handle($request);
                $connection->send($response);
            } catch (HttpNotFoundException $error) {
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

        $this->setWorkingHttp(true);
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
            (array)$request->header(),
            (string)$request->rawBody(),
            '1.1',
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
     * @param App $slim
     */
    private function setSlim(App $slim): self
    {
        $this->slim = $slim;

        return $this;
    }

    /**
     * @param string $host
     */
    private function setHost(string $host): self
    {
        $this->hostHttp = $host;

        return $this;
    }

    /**
     * @param int $port
     */
    private function setPort(int $port): self
    {
        $this->portHttp = $port;

        return $this;
    }

    /**
     * @param bool $debug
     */
    private function setDebugHttp(bool $debug): self
    {
        $this->debugHttp = $debug;

        return $this;
    }

    /**
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return ContainerInterface|null
     */
    private function getContainerHttp(): ?ContainerInterface
    {
        return $this->containerHttp;
    }

    /**
     * @param ContainerInterface $container
     */
    private function setContainerHttp(ContainerInterface $container): self
    {
        $this->containerHttp = $container;

        return $this;
    }

    /**
     * @param int $workers
     */
    private function setWorkersHttp(int $workers): self
    {
        $this->workersHttp = $workers;

        return $this;
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
     * @return bool
     */
    private function isWorkingHttp(): bool
    {
        return $this->workingHttp;
    }

    /**
     * @param bool $workingHttp
     */
    private function setWorkingHttp(bool $workingHttp): self
    {
        $this->workingHttp = $workingHttp;

        return $this;
    }

    /**
     * @return bool
     */
    private function isDataInitHttp(): bool
    {
        return $this->dataInitHttp;
    }

    /**
     * @param bool $dataInitHttp
     */
    private function setDataInitHttp(bool $dataInitHttp): self
    {
        $this->dataInitHttp = $dataInitHttp;

        return $this;
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
