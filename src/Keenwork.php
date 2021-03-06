<?php

declare(strict_types=1);

namespace Keenwork;

use Keenwork\Request;
use Keenwork\Response;
use Keenwork\Factory\CometPsr17Factory;
use Keenwork\Middleware\JsonBodyParserMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Exception\HttpNotFoundException;
use Workerman\Worker;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;
use Slim\App;
use Rakit\Validation\Validator;
use DI\Container;

class Keenwork
{
    /**
     * Version Keenwork
     */
    public const VERSION = '0.2.2';

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

    //TODO: think
    private static $jobs = [];

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
    }


    /**
     * Init http server
     * @param array $config
     */
    public function initHttp(array $config = []): void
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
            'host' => 'ip',
            'port' => 'integer',
            'workers' => 'integer',
            'debug' => 'boolean',
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

        $this->setHost($config['host'] ?? '0.0.0.0');
        $this->setPort($config['port'] ?? 8080);
        $this->setDebugHttp($config['debug'] ?? false);
        $this->setWorkersHttp($config['workers'] ?? ((int) shell_exec('nproc')*4));

        $this->setContainerHttp(new Container());
        $provider = new Psr17FactoryProvider();
        $provider::setFactories([CometPsr17Factory::class]);
        AppFactory::setPsr17FactoryProvider($provider);
        $this->setSlim(AppFactory::create(null, $this->getContainerHttp()));
        $this->getSlim()->add(new JsonBodyParserMiddleware());

        $this->setDataInitHttp(true);
    }

    /**
     * Return config param value or the config at whole
     *
     * @return array - config http data
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
     * @param array    $params
     * @param callable $init
     * @param int      $workers
     * @param string   $name
     */
    public function addJob(int $interval, callable $job, array $params = [], callable $init = null, string $name = '', int $workers = 1)
    {
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
     * @return array
     */
    public static function getJobs(): array
    {
        return self::$jobs;
    }

    /**
     * @return App|null
     */
    public function getSlim(): ?App
    {
        return $this->slim;
    }

    /**
     * Startup initialization
     */
    private function initRun()
    {
        // Write worker output to log file if exists
        if (null !== $this->getLogger()) {
            foreach ($this->getLogger()->getHandlers() as $handler) { // TODO: Call to an undefined method Psr\Log\LoggerInterface::getHandlers().
                if ($handler->getUrl()) {
                    Worker::$stdoutFile = $handler->getUrl();
                    break;
                }
            }
        }

        // FIXME We should use real free random port not fixed 65432
        // Init JOB workers
//        foreach (self::$jobs as $job) {
        //	        $w = new Worker('text://' . $this->host . ':' . 65432);
//    	    $w->count = $job['workers'];
//        	$w->name = 'Keenwork v' . self::VERSION .' [job] ' . $job['name'];
//        	$w->onWorkerStart = function() use ($job) {
//      	        if ($this->init)
        //					call_user_func($this->init);
//            	Timer::add($job['interval'], $job['job']);
//        	};
//        }

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
     */
    private function _handle(WorkermanRequest $request): WorkermanResponse
    {
        if ($request->queryString()) {
            parse_str($request->queryString(), $queryParams);
        } else {
            $queryParams = [];
        }

        $req = new Request(
            $request->method(),
            $request->uri(),
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
            $ret->getBody()
        );
    }

    /**
     * @param App $slim
     */
    private function setSlim(App $slim): void
    {
        $this->slim = $slim;
    }

    /**
     * @param string $host
     */
    private function setHost(string $host): void
    {
        $this->hostHttp = $host;
    }

    /**
     * @param int $port
     */
    private function setPort(int $port): void
    {
        $this->portHttp = $port;
    }

    /**
     * @param bool $debug
     */
    private function setDebugHttp(bool $debug): void
    {
        $this->debugHttp = $debug;
    }

    /**
     * @param LoggerInterface|null $logger
     */
    private function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
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
    private function setContainerHttp(ContainerInterface $container): void
    {
        $this->containerHttp = $container;
    }

    /**
     * @param int $workers
     */
    private function setWorkersHttp(int $workers): void
    {
        $this->workersHttp = $workers;
    }

    /**
     * @param array $jobs
     */
    private static function setJobs(array $jobs): void
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
    private function setWorkingHttp(bool $workingHttp): void
    {
        $this->workingHttp = $workingHttp;
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
    private function setDataInitHttp(bool $dataInitHttp): void
    {
        $this->dataInitHttp = $dataInitHttp;
    }
}
