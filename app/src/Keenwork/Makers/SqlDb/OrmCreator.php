<?php

declare(strict_types=1);

namespace App\Keenwork\Makers\SqlDb;

use App\Keenwork\Config as AppConfig;
use Cycle\Annotated;
use Cycle\Database;
use Cycle\Database\Config;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
use Cycle\ORM\EntityManager;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\Schema;
use Spiral\Core\Container;

/**
 * Инициализация Cycle ORM.
 */
final class OrmCreator
{
    /**
     * Конфиг подключения к БД.
     *
     * @var DatabaseProviderInterface[]
     */
    private static array $dbConfig;

    /**
     * Схема ORM.
     *
     * @var ORMInterface[]
     */
    private static array $orm;

    public function clean(): void
    {
        if (isset(self::$orm[getmypid()]) && self::$orm[getmypid()] instanceof ORMInterface) {
            self::$orm[getmypid()]->getHeap()->clean();
        }
    }

    public function getEM(bool $heapClean = false): EntityManagerInterface
    {
        return new EntityManager($this->getORM($heapClean));
    }

    /**
     * Определяем схему ORM из имеющихся сущностей.
     *
     * @throws \ErrorException
     */
    public function getORM(bool $heapClean = false): ORMInterface
    {
        if (isset(self::$orm[getmypid()]) && self::$orm[getmypid()] instanceof ORMInterface) {
            if ($heapClean) {
                self::clean();
            }

            return self::$orm[getmypid()];
        }

        $dbal = $this->getDBAL();

        $finder = (new \Symfony\Component\Finder\Finder())->files()->in([
            AppConfig::getRootDir().'src'.DIRECTORY_SEPARATOR.'Entity'.DIRECTORY_SEPARATOR,
        ]);
        $classLocator = new \Spiral\Tokenizer\ClassLocator($finder);
        $embeddableLocator = new Annotated\Locator\TokenizerEmbeddingLocator($classLocator);
        $entityLocator = new Annotated\Locator\TokenizerEntityLocator($classLocator);

        $schema = (new Schema\Compiler())->compile(new Schema\Registry($dbal), [
            new Schema\Generator\ResetTables(),             // re-declared table schemas (remove columns)
            new Annotated\Embeddings($embeddableLocator),        // register embeddable entities
            new Annotated\Entities($entityLocator),          // register annotated entities
            new Annotated\TableInheritance(),               // register STI/JTI
            new Annotated\MergeColumns(),                   // add @Table column declarations
            new Schema\Generator\GenerateRelations(),       // generate entity relations
            new Schema\Generator\GenerateModifiers(),       // generate changes from schema modifiers
            new Schema\Generator\ValidateEntities(),        // make sure all entity schemas are correct
            new Schema\Generator\RenderTables(),            // declare table schemas
            new Schema\Generator\RenderRelations(),         // declare relation keys and indexes
            new Schema\Generator\RenderModifiers(),         // render all schema modifiers
            new Annotated\MergeIndexes(),                   // add @Table column declarations
            new Schema\Generator\SyncTables(),              // sync table changes to database
            new Schema\Generator\GenerateTypecast(),        // typecast non string columns
        ]);

        // for behavior
        $container = new Container();
        $commandGenerator = new EventDrivenCommandGenerator(new \Cycle\ORM\Schema($schema), $container);

        self::$orm[getmypid()] = new ORM(new Factory($dbal), new \Cycle\ORM\Schema($schema), $commandGenerator);

        return self::$orm[getmypid()];
    }

    public function isConnect(): bool
    {
        try {
            return $this->getDBAL()->database('default')->hasTable('logs');
        } catch (\Throwable $e) {
            echo $e->getMessage();

            return false;
        }
    }

    /**
     * Настройка подключения.
     *
     * @throws \ErrorException
     */
    public function getDBAL(): DatabaseProviderInterface
    {
        if (isset(self::$dbConfig[getmypid()]) && self::$dbConfig[getmypid()] instanceof DatabaseProviderInterface) {
            return self::$dbConfig[getmypid()];
        }

        $dbConfig = new Config\DatabaseConfig([
            'default' => 'default',
            'databases' => [
                'default' => ['connection' => 'postgres_user'],
            ],
            'connections' => [
                'postgres_user' => new Config\PostgresDriverConfig(
                    connection: new Config\Postgres\TcpConnectionConfig(
                        database: (string) AppConfig::get('databases:default:database') ?: 'error',
                        host: (string) AppConfig::get('databases:default:host') ?: 'error',
                        port: (int) AppConfig::get('databases:default:port') > 0 ? (int) AppConfig::get('databases:default:port') : 1,
                        user: (string) AppConfig::get('databases:default:user') ?: 'error',
                        password: (string) AppConfig::get('databases:default:password') ?: 'error',
                    ),
                    schema: 'public',
                    reconnect: true,
                    timezone: 'Europe/Moscow',
                    queryCache: false,
                ),
            ],
        ]);
        self::$dbConfig[getmypid()] = new Database\DatabaseManager($dbConfig);

        return self::$dbConfig[getmypid()];
    }
}
