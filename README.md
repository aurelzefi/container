# Container

A package to use dependency injection in your application.

## Requirements

- PHP 7.4 or higher

## Installation

Clone this repo, add the package to your repositories and then require it:

```bash
git clone git@github.com:aurelzefi/container.git
```

```json
"repositories": [
    {
        "type": "path",
        "url": "./../container"
    }
]
```

```bash
composer require aurelzefi/container
```

## Usage

### Binding Simple Classes

```php
use Aurel\Container\Container;

$container = new Container();

$container->bind(MySql::class);
```

### Binding Interfaces 

Suppose you have different database handlers like this:

```php
interface Database
{
    public function select($columns);
}

class MySql implements Database
{
    public function select($columns)
    {
        //
    }
}

class SqlServer implements Database
{
    public function select($columns)
    {
        //
    }
}
```

You can bind in the container the MySql handler like this:

```php
$container->bind(Database::class, MySql::class);
```

Now, whenever you inject the `Database` interface in your application structure, the `MySql` connection will be
 retrieved.
 
### Binding Closures

```php
$container->bind(Database::class, function ($app) {
    return new MySql();
});
```

### Resolving

```php
$container->get(Database::class);
```

### Resolving Overwriting Parameters

Suppose the constructor receives parameters:

```php
class MySql
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
}

$container->get(Database::class, ['connection' => new Connection()]);
```

### Calling Methods

```php
$container->call(Database::class, 'select', ['columns' => ['id', 'name']]);
```
