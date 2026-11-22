<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from AnourValar Eloquent Serialize and Laravel Serializable Closure; see the accompanying LICENSE-*.md files.
namespace Webkernel\ORM\Serialization\Closure\Serializers;

use Webkernel\ORM\Serialization\Closure\Contracts\Serializable;
use Webkernel\ORM\Serialization\Closure\Exceptions\InvalidSignatureException;
use Webkernel\ORM\Serialization\Closure\Exceptions\MissingSecretKeyException;

class Signed implements Serializable
{
    /**
     * The signer that will sign and verify the closure's signature.
     *
     * @var \Webkernel\ORM\Serialization\Closure\Contracts\Signer|null
     */
    public static $signer;

    /**
     * The closure to be serialized/unserialized.
     *
     * @var \Closure
     */
    protected $closure;

    /**
     * Creates a new serializable closure instance.
     *
     * @param  \Closure  $closure
     * @return void
     */
    public function __construct($closure)
    {
        $this->closure = $closure;
    }

    /**
     * Resolve the closure with the given arguments.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->closure, func_get_args());
    }

    /**
     * Gets the closure.
     *
     * @return \Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Get the serializable representation of the closure.
     *
     * @return array
     *
     * @throws \Webkernel\ORM\Serialization\Closure\Exceptions\MissingSecretKeyException
     */
    public function __serialize()
    {
        if (! static::$signer) {
            throw new MissingSecretKeyException();
        }

        return static::$signer->sign(
            serialize(new Native($this->closure))
        );
    }

    /**
     * Restore the closure after serialization.
     *
     * @param  array{serializable: string, hash: string}  $signature
     * @return void
     *
     * @throws \Webkernel\ORM\Serialization\Closure\Exceptions\InvalidSignatureException
     * @throws \Webkernel\ORM\Serialization\Closure\Exceptions\MissingSecretKeyException
     */
    public function __unserialize($signature)
    {
        if (! static::$signer) {
            throw new MissingSecretKeyException();
        }

        if (! static::$signer->verify($signature)) {
            throw new InvalidSignatureException();
        }

        /** @var \Webkernel\ORM\Serialization\Closure\Contracts\Serializable $serializable */
        $serializable = unserialize($signature['serializable']);

        $this->closure = $serializable->getClosure();
    }
}
