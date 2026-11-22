<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from AnourValar Eloquent Serialize and Laravel Serializable Closure; see the accompanying LICENSE-*.md files.
namespace Webkernel\ORM\Serialization;

use Webkernel\ORM\Relations\Relation;
use Webkernel\ORM\Model;
use Webkernel\Database\Query\Expression;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

class Service
{
    use \Webkernel\ORM\Serialization\Grammars\ModelGrammar;
    use \Webkernel\ORM\Serialization\Grammars\EloquentBuilderGrammar;
    use \Webkernel\ORM\Serialization\Grammars\QueryBuilderGrammar;

    /**
     * Pack
     *
     * @param \Webkernel\ORM\Builder|\Webkernel\ORM\Relations\Relation $builder
     * @return string
     * @throws \RuntimeException
     */
    public function serialize(\Webkernel\ORM\Builder|Relation $builder): string
    {
        if (
            $builder instanceof \Webkernel\ORM\Relations\HasOne
            || $builder instanceof \Webkernel\ORM\Relations\HasMany
            || $builder instanceof \Webkernel\ORM\Relations\BelongsTo // as well as MorphTo
            || $builder instanceof \Webkernel\ORM\Relations\MorphOne
        ) {
            $builder = $builder->getQuery(); // chaperone/inverse is not supported!
        }

        if ($builder instanceof Relation) {
            throw new \RuntimeException(get_class($builder) . ' cannot be packed.');
        }

        $package = $this->pack($builder);

        return serialize($package); // important!
    }

    /**
     * Unpack
     *
     * @param mixed $package
     * @throws \LogicException
     * @return \Webkernel\ORM\Builder
     */
    public function unserialize($package): \Webkernel\ORM\Builder
    {
        // Prepare data
        if (is_string($package)) {
            $package = unserialize($package, [
                'allowed_classes' => [
                    Package::class,
                    Expression::class,
                    DateTime::class,
                    DateTimeImmutable::class,
                    DateTimeZone::class,
                ],
            ]);
        }
        if (! ($package instanceof Package)) {
            throw new \LogicException('Incorrect argument.');
        }

        $model = $package->get('model');
        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new \LogicException('Serialized query model is invalid.');
        }

        // Unpack
        return $this->unpack($package);
    }
}
