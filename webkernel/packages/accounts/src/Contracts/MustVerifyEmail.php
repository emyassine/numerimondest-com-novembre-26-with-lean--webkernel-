<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts\Contracts;

interface MustVerifyEmail
{
    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail();

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified();

    /**
     * Mark the given user's email as unverified.
     *
     * @return bool
     */
    public function markEmailAsUnverified();

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification();

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification();
}
