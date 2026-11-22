<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Serialization\Closure\Signers;

use \Webkernel\Serialization\Closure\Contracts\Signer;

class Hmac implements Signer
{
    /**
     * The secret key.
     *
     * @var string
     */
    protected $secret;

    /**
     * Creates a new signer instance.
     *
     * @param  string  $secret
     * @return void
     */
    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Sign the given serializable.
     *
     * @param  string  $serialized
     * @return array
     */
    public function sign($serialized)
    {
        return [
            'serializable' => $serialized,
            'hash' => base64_encode(hash_hmac('sha256', $serialized, $this->secret, true)),
        ];
    }

    /**
     * Verify the given signature.
     *
     * @param  array{serializable: string, hash: string}  $signature
     * @return bool
     */
    public function verify($signature)
    {
        return hash_equals(base64_encode(
            hash_hmac('sha256', $signature['serializable'], $this->secret, true)
        ), $signature['hash']);
    }
}
