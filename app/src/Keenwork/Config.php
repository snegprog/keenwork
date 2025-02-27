<?php

namespace App\Keenwork;

class Config
{
    private const CONFIG_FILE = 'config.yaml';
    private const CORS_CONFIG_FILE = 'cors.yaml';
    private const CONFIG_DIR = 'config';

    /**
     * @var mixed[] - массив данных файла конфигурации
     */
    private static array $config;

    /**
     * @var string[] - массив данных файла конфигурации CORS
     */
    private static array $corsConfig;

    /**
     * Метод безопасного получения переменных конфигурации.
     *
     * @param string $variable - example: 'param1', or 'param1:sub_param1'
     *
     * @return string|int|float|bool - значение конфигурации
     *
     * @throws \ErrorException
     */
    public static function get(string $variable): string|int|float|bool
    {
        $config = self::getConfig();
        $explode = explode(':', $variable);
        $result = $config;
        foreach ($explode as $configKey) {
            if (!\is_array($result) || !\array_key_exists($configKey, $result)) {
                throw new \ErrorException('Invalid param');
            }

            $result = $result[$configKey];
        }
        if (!\is_string($result) && !is_numeric($result) && !is_bool($result)) {
            return '';
        }

        return $result;
    }

    /**
     * Конфиг заголовков CORS.
     *
     * @throws \ErrorException
     */
    public static function getCors(string $variable): string
    {
        return self::getCorsConfig()[$variable];
    }

    /**
     * Текущее окружение.
     *
     * @return string - prod|dev|test
     */
    public static function getEnvironment(): string
    {
        return (string) self::get('environment');
    }

    /**
     * Возвращаем корневую директорию проекта.
     */
    public static function getRootDir(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
    }

    /**
     * Получаем массив данных конфигурации CORS.
     *
     * @return string[]
     *
     * @throws \ErrorException
     */
    private static function getCorsConfig(): array
    {
        if (!empty(self::$corsConfig)) {
            return self::$corsConfig;
        }

        $pre = \yaml_parse_file(
            __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.
            self::CONFIG_DIR.DIRECTORY_SEPARATOR.self::CORS_CONFIG_FILE
        );
        if (!\is_array($pre)) {
            throw new \ErrorException('Invalid cors.yaml');
        }

        foreach ($pre as $key => $val) {
            if (!\is_string($val)) {
                unset($pre[$key]);
            }
        }

        self::$corsConfig = $pre;

        return self::$corsConfig;
    }

    /**
     * Получаем массив данных конфигурации приложения.
     *
     * @return mixed[]
     */
    private static function getConfig(): array
    {
        if (!empty(self::$config)) {
            return self::$config;
        }

        $pre = \yaml_parse_file(
            __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.
            self::CONFIG_DIR.DIRECTORY_SEPARATOR.self::CONFIG_FILE
        );
        if (!\is_array($pre)) {
            throw new \ErrorException('Invalid config.yaml');
        }

        self::$config = $pre;

        return self::$config;
    }
}
