## Keenwork '0.6.3'
* Workerman + Slim + PHP/DI + Guzzle + CycleORM + Monolog 

### Предварительные условия
1. Docker-cli >= 24.0.5
2. Docker compose >= v2.3.3 (ВАЖНО: команда docker-compose не работает, только docker compose)

### Развертывание
1. Клонируем репозиторий - ```git clone git@github.com:snegprog/keenwork.git```
2. Переходим в папку app - ```cd <project path>/app```
3. Создаем файл config.yaml - ```cp config/config.yaml.example config/config.yaml```
4. Создаем файл cors.yaml- ```cp config/cors.yaml.example config/cors.yaml```
5. Переходим в папку docker - ```cd ../docker```
6. Создаем папку хранения данных БД - ```mkdir postgresql/data```
7. Выполняем сборку контейнеров - ```docker compose build```
8. Устанавливаем пакеты composer - ```<project path>/app/bin/composer-install```
9. Запускаем контейнеры - ```docker compose up -d```
10. Прописываем в локальном файле hosts значение - ```127.0.1.1 keenwork.local```
11. В браузере должен открываться страница http://keenwork.local/v1/info/check

**Убедитесь, что в папке app/bin/ все файлы имеют права на исполнение. В ином случае выполните команду:**
```shell
chmod +x <project path>/app/bin/<file>
```

### Команды
**Установка пакетов composer**
```shell
<project path>/app/bin/composer-install
```

**Установка пакетов composer без dev пакетов**
```shell
<project path>/app/bin/composer-install no-dev
```

**Проверка соответствия проекта code style, без его правок:**
```shell
<project path>/app/bin/code-style
```

**Проверка соответствия проекта code style, с его правками:**
```shell
<project path>/app/bin/code-style fix
```

**Проверка проекта статическим анализатором:**
```shell
<project path>/app/bin/static-analysis
```

**Прохождение unit тестов**
```shell
<project path>/app/bin/unit-test
```

### Планеровщик (Scheduler)
**Смотрите код в файле app/app.php**

### Команды исполняемые в контейнере php
**Посмотреть доступные команды**
```shell
/var/www/app/bin/console.php
```
**Просмотр команд Workerman**
```shell
/var/www/app/app.php
```