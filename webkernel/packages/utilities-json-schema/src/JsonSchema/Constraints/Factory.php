<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Utilities\JsonSchema\Constraints;

use Webkernel\Utilities\JsonSchema\DraftIdentifiers;
use Webkernel\Utilities\JsonSchema\Constraints\CollectionConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\ConstraintInterface;
use Webkernel\Utilities\JsonSchema\Constraints\ConstConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\EnumConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\FormatConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\NumberConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\ObjectConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\SchemaConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\StringConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\TypeConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\UndefinedConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\Draft06Constraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\Draft07Constraint;
use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft2019\Draft2019Constraint;
use Webkernel\Utilities\JsonSchema\Exception\InvalidArgumentException;
use Webkernel\Utilities\JsonSchema\SchemaStorage;
use Webkernel\Utilities\JsonSchema\SchemaStorageInterface;
use Webkernel\Utilities\JsonSchema\Uri\UriRetriever;
use Webkernel\Utilities\JsonSchema\UriRetrieverInterface;
use Webkernel\Utilities\JsonSchema\Validator;
use Webkernel\Utilities\JsonSchema\Constraints\BaseConstraint;
use Webkernel\Utilities\JsonSchema\Constraints\Constraint;
use Webkernel\Utilities\JsonSchema\Constraints\TypeCheck\LooseTypeCheck;
use Webkernel\Utilities\JsonSchema\Constraints\TypeCheck\StrictTypeCheck;
use Webkernel\Utilities\JsonSchema\Constraints\TypeCheck\TypeCheckInterface;

/**
 * Factory for centralize constraint initialization.
 */
class Factory
{
    /**
     * @var SchemaStorageInterface
     */
    protected $schemaStorage;

    /**
     * @var UriRetrieverInterface
     */
    protected $uriRetriever;

    /**
     * @var int
     * @phpstan-var int-mask-of<Constraint::CHECK_MODE_*>
     */
    private $checkMode = Constraint::CHECK_MODE_NORMAL;

    /**
     * @var array<int, TypeCheckInterface>
     * @phpstan-var array<int-mask-of<Constraint::CHECK_MODE_*>, TypeCheckInterface>
     */
    private $typeCheck = [];

    /**
     * @var int-mask-of<Validator::ERROR_*> Validation context
     */
    protected $errorContext = Validator::ERROR_DOCUMENT_VALIDATION;

    /**
     * The default dialect used for strict mode (Constraint::CHECK_MODE_STRICT) when the schema is without a schema property
     *
     * @var string
     */
    private $defaultDialect = DraftIdentifiers::Draft6->value;

    /**
     * @var array
     */
    protected $constraintMap = [
        'array' => CollectionConstraint::class,
        'collection' => CollectionConstraint::class,
        'object' => ObjectConstraint::class,
        'type' => TypeConstraint::class,
        'undefined' => UndefinedConstraint::class,
        'string' => StringConstraint::class,
        'number' => NumberConstraint::class,
        'enum' => EnumConstraint::class,
        'const' => ConstConstraint::class,
        'format' => FormatConstraint::class,
        'schema' => SchemaConstraint::class,
        'validator' => Validator::class,
        'draft06' => Draft06Constraint::class,
        'draft07' => Draft07Constraint::class,
        'draft2019-09' => Draft2019Constraint::class,
    ];

    /**
     * @var array<ConstraintInterface>
     */
    private $instanceCache = [];

    /**
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $checkMode
     */
    public function __construct(
        ?SchemaStorageInterface $schemaStorage = null,
        ?UriRetrieverInterface $uriRetriever = null,
        int $checkMode = Constraint::CHECK_MODE_NORMAL
    ) {
        // set provided config options
        $this->setConfig($checkMode);

        $this->uriRetriever = $uriRetriever ?: new UriRetriever();
        $this->schemaStorage = $schemaStorage ?: new SchemaStorage($this->uriRetriever);
    }

    /**
     * Set config values
     *
     * @param int $checkMode Set checkMode options - does not preserve existing flags
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $checkMode
     */
    public function setConfig(int $checkMode = Constraint::CHECK_MODE_NORMAL): void
    {
        $this->checkMode = $checkMode;
    }

    /**
     * Enable checkMode flags
     *
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $options
     */
    public function addConfig(int $options): void
    {
        $this->checkMode |= $options;
    }

    /**
     * Disable checkMode flags
     *
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $options
     */
    public function removeConfig(int $options): void
    {
        $this->checkMode &= ~$options;
    }

    /**
     * Get checkMode option
     *
     * @param int|null $options Options to get, if null then return entire bitmask
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*>|null $options Options to get, if null then return entire bitmask
     *
     * @phpstan-return int-mask-of<Constraint::CHECK_MODE_*>
     */
    public function getConfig(?int $options = null): int
    {
        if ($options === null) {
            return $this->checkMode;
        }

        return $this->checkMode & $options;
    }

    public function getUriRetriever(): UriRetrieverInterface
    {
        return $this->uriRetriever;
    }

    public function getSchemaStorage(): SchemaStorageInterface
    {
        return $this->schemaStorage;
    }

    public function getTypeCheck(): TypeCheckInterface
    {
        if (!isset($this->typeCheck[$this->checkMode])) {
            $this->typeCheck[$this->checkMode] = ($this->checkMode & Constraint::CHECK_MODE_TYPE_CAST)
                ? new LooseTypeCheck()
                : new StrictTypeCheck();
        }

        return $this->typeCheck[$this->checkMode];
    }

    public function setConstraintClass(string $name, string $class): Factory
    {
        // Ensure class exists
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Unknown constraint ' . $name);
        }
        // Ensure class is appropriate
        if (
            !in_array(ConstraintInterface::class, class_implements($class), true)
            || !is_subclass_of($class, BaseConstraint::class)
        ) {
            throw new InvalidArgumentException('Invalid class ' . $name);
        }
        $this->constraintMap[$name] = $class;

        return $this;
    }

    /**
     * Create a constraint instance for the given constraint name.
     *
     * @param $constraintName string
     *
     * @throws InvalidArgumentException if is not possible create the constraint instance
     *
     * @return ConstraintInterface&BaseConstraint
     * @phpstan-return ConstraintInterface&BaseConstraint
     */
    public function createInstanceFor(string $constraintName): ConstraintInterface&BaseConstraint
    {
        if (!isset($this->constraintMap[$constraintName])) {
            throw new InvalidArgumentException('Unknown constraint ' . $constraintName);
        }

        if (!isset($this->instanceCache[$constraintName])) {
            $this->instanceCache[$constraintName] = new $this->constraintMap[$constraintName]($this);
        }

        return clone $this->instanceCache[$constraintName];
    }

    /**
     * Get the error context
     *
     * @return int-mask-of<Validator::ERROR_*>
     */
    public function getErrorContext(): int
    {
        return $this->errorContext;
    }

    /**
     * Set the error context
     *
     * @param int-mask-of<Validator::ERROR_*> $errorContext
     */
    public function setErrorContext(int $errorContext): void
    {
        $this->errorContext = $errorContext;
    }

    public function getDefaultDialect(): string
    {
        return $this->defaultDialect;
    }

    public function setDefaultDialect(string $defaultDialect): void
    {
        $this->defaultDialect = $defaultDialect;
    }
}
