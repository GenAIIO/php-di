<?php

namespace GenAI\Di\Processor;

use GenAI\Attribute\AttributeProcessor;
use GenAI\Attribute\Context;
use GenAI\Container\Bean\Definition;
use GenAI\Container\Bean\Param;
use GenAI\Container\ContainerRegister;
use GenAI\Di\Bean;
use GenAI\Di\Component;
use GenAI\Di\ParamValues;
use GenAI\Di\Value;

/**
 * Turns the DI attributes into a compiled container file.
 *
 *   #[Service] / #[Repository] / #[Configuration]  -> the class is an autowired bean
 *   #[Bean] (on a method of such a class)          -> a factory bean built by that method
 *   #[Value] (on a parameter):
 *       '${app.name}'  -> the config value, baked from the build Context
 *       '%db.dsn%'     -> a runtime container parameter ($c->get('db.dsn'))
 *       'literal'      -> a literal
 *   #[ParamValues([...])] (on the function): the same, but as a name=>value map
 *       declared once above the function instead of per-parameter #[Value]s.
 *
 * One processor handles all stereotypes by listening for the Component base
 * (subclasses match via IS_INSTANCEOF), so everything lands in one
 * ContainerRegister / one container file. Parameterless ctor => auto-discoverable.
 *
 * Param resolution is deferred to compile(), where the Context (and its config
 * parameters for ${...}) is available.
 *
 * Build-time only (PHP 8); the dumped container file is PHP 5.3-safe.
 */
class ComponentProcessor implements AttributeProcessor
{
    private ContainerRegister $register;

    /** @var \ReflectionClass[] */
    private array $classes = [];

    public function __construct()
    {
        $this->register = new ContainerRegister();
    }

    public function getAttributeClass(): string
    {
        return Component::class; // matches Service/Repository/Configuration too
    }

    public function process(object $attribute, \Reflector $target): void
    {
        /** @var \ReflectionClass $target */
        $this->classes[] = $target;
    }

    public function compile(Context $context): void
    {
        foreach ($this->classes as $class) {
            $name = $class->getName();

            // The component class itself is an autowired bean (id = class name).
            $this->register->set($name, Definition::create($name, $this->paramsOf($class->getConstructor(), $context)));

            // Each #[Bean] method becomes a factory bean built by calling it.
            foreach ($class->getMethods() as $method) {
                $beanAttributes = $method->getAttributes(Bean::class);
                if (empty($beanAttributes)) {
                    continue;
                }

                $bean = $beanAttributes[0]->newInstance();
                $id   = $bean->name ?? $this->beanId($method);
                $this->register->set(
                    $id,
                    Definition::factory($name, $method->getName(), $this->paramsOf($method, $context))
                );
            }
        }

        $this->register->dumpToFile($context->output('Container.php')); // class Cache\Container
    }

    /**
     * Autowire a function's parameters into container Params, honouring #[Value]
     * (per parameter) and #[ParamValues] (a name=>value map on the function).
     *
     * @param \ReflectionFunctionAbstract|null $function
     * @param Context                          $context
     * @return Param[]
     */
    private function paramsOf(?\ReflectionFunctionAbstract $function, Context $context): array
    {
        if ($function === null) {
            return [];
        }

        $overrides = $this->paramValuesOf($function);

        $params = [];
        foreach ($function->getParameters() as $parameter) {
            $params[] = $this->paramOf($parameter, $context, $overrides);
        }

        return $params;
    }

    /**
     * The #[ParamValues] map on a function (name => value spec), or empty.
     *
     * @param \ReflectionFunctionAbstract $function
     * @return array<string, string>
     */
    private function paramValuesOf(\ReflectionFunctionAbstract $function): array
    {
        $attributes = $function->getAttributes(ParamValues::class);
        return empty($attributes) ? array() : $attributes[0]->newInstance()->values;
    }

    /**
     * Resolve one parameter: a per-parameter #[Value] wins, then a #[ParamValues]
     * map entry, then a class type, then a default, then a (runtime) container
     * parameter.
     *
     * @param array<string, string> $overrides #[ParamValues] map for the function
     */
    private function paramOf(\ReflectionParameter $parameter, Context $context, array $overrides = array()): Param
    {
        $name = $parameter->getName();

        $valueAttributes = $parameter->getAttributes(Value::class);
        if (!empty($valueAttributes)) {
            return $this->valueParam($name, $valueAttributes[0]->newInstance()->value, $context, $parameter);
        }

        if (array_key_exists($name, $overrides)) {
            return $this->valueParam($name, $overrides[$name], $context, $parameter);
        }

        $type = $parameter->getType();
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return Param::dependency($name, $type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return Param::value($name, $parameter->getDefaultValue());
        }

        return Param::parameter($name);
    }

    /**
     * Resolve a #[Value('...')] string into a Param.
     */
    private function valueParam(string $name, string $raw, Context $context, \ReflectionParameter $parameter): Param
    {
        // ${key} -> config value, baked from the build context.
        if (preg_match('/^\$\{(.+)\}$/', $raw, $matches)) {
            $key = $matches[1];
            if (!$context->has($key)) {
                throw new \RuntimeException(sprintf(
                    '#[Value(\'${%s}\')] on %s::$%s: no config parameter "%s" in the build context.',
                    $key,
                    $parameter->getDeclaringClass()->getName(),
                    $name,
                    $key
                ));
            }
            return Param::value($name, $context->parameter($key));
        }

        // %key% -> a runtime container parameter ($c->get('key')).
        if (strlen($raw) > 2 && $raw[0] === '%' && substr($raw, -1) === '%') {
            return Param::dependency($name, substr($raw, 1, -1));
        }

        // Anything else -> a literal.
        return Param::value($name, $raw);
    }

    /**
     * Default bean id for a #[Bean] method: its return type if a class,
     * otherwise the method name.
     */
    private function beanId(\ReflectionMethod $method): string
    {
        $type = $method->getReturnType();
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        return $method->getName();
    }
}
