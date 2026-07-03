# genai/di

Popular dependency-injection attributes and the processor that compiles them
into a `genai/container` definition file.

This is a **build-time** package (PHP 8): it's read by the `genai/attribute`
scanner during compilation. The container file it produces is **PHP 5.3-safe**,
like everything `genai/container` emits.

## Attributes

| Attribute | Target | Effect |
|---|---|---|
| `#[Service]` | class | register the class as an autowired bean |
| `#[Repository]` | class | same — a stereotype for the persistence layer |
| `#[Configuration]` | class | register the class as a bean; its `#[Bean]` methods become factory beans |
| `#[Bean]` | method | a bean produced by calling the method on its (configuration) bean |
| `#[Value]` | parameter | inject a value into a constructor / factory parameter |

`Service`/`Repository`/`Configuration` all extend `Component`; one
`ComponentProcessor` handles them via `ReflectionAttribute::IS_INSTANCEOF`.

## Usage

Point the scanner at your app plus this processor's namespace; it auto-registers
`ComponentProcessor` (by type) and dumps `container.php`:

```php
$scanner = new GenAI\Attribute\Scanner($loader);
$scanner->scan(['App', 'GenAI\\Di\\Processor']);
$scanner->compile(new GenAI\Attribute\Context($configDir, $outputDir, $parameters));
```

```php
#[Service]
class UserController {
    public function __construct(UserRepository $repo) {}   // autowired by type
}

#[Repository]
class UserRepository {}

#[Configuration]
class AppConfig {
    #[Bean(Mailer::class)]
    public function mailer(Clock $clock) {                 // params autowired too
        return new Mailer($clock);
    }
}
```

## `#[Value]` forms

```php
#[Value('${app.name}')]   // a config value — looked up in Context::$parameters and BAKED at build
#[Value('%db.dsn%')]      // a runtime container parameter -> $c->get('db.dsn')
#[Value('sendmail')]      // a literal
```

`${...}` is resolved at compile time from the flat `parameters` map you pass to
`Context` (you populate it from your config source). A missing key fails the
build with a clear error.

## `#[Bean]` id + the PHP 5.3 caveat

A `#[Bean]` method's id is, in order: the explicit `#[Bean('id')]` /
`#[Bean(Type::class)]`, else the method's return type, else the method name.

A `#[Bean]` method is **called at runtime** (on the configuration bean). If your
runtime is **PHP 5.3** it must not declare a `: ReturnType` (return types are
PHP 7+), so give the id explicitly:

```php
#[Bean(Renderer::class)]      // the attribute line is a comment on 5.3 — Type::class there is fine
public function renderer() {  // no ': Renderer'
    return new Renderer(__DIR__ . '/../templates');
}
```

On a PHP 7+/8 runtime you can use the return type and drop the explicit id.

## Requires

`php >=8.0`, `genai/attribute`, `genai/container` (it bridges the two; the
runtime container it feeds stays PHP 5.3).
