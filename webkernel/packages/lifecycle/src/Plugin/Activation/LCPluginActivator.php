<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Plugin\Activation;

use Composer\Composer;
use Composer\IO\IOInterface;
use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\Install\LCBaseInstaller;
use Webkernel\Lifecycle\Path\LCBasePathInjector;

/** Performs the setup required when Composer activates the Lifecycle plugin. */
final class LCPluginActivator
{
    /**
     * Configures Composer and registers Lifecycle's package installer.
     * @param $composer Composer Composer instance being configured.
     * @param $io IOInterface Composer console IO.
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $composer->getConfig()->merge([
            'config' => [
                'optimize-autoloader' => true,
                'apcu-autoloader' => true,
            ],
        ], 'webkernel/lifecycle');

        LCBasePathInjector::inject($composer);

        $previous_io = StandardComposerIO::bind($io);
        try {
            StandardComposerIO::tree('plugin activation', static function (): void {
                StandardComposerIO::result('DONE', 'Lifecycle plugin activated');
                StandardComposerIO::result('DONE', sprintf('Project base path set: %s', $GLOBALS['BASE_PATH']));
                StandardComposerIO::result('DONE', 'Optimized Composer autoloader enabled');
                StandardComposerIO::result('DONE', 'Composer APCu autoloader enabled');

                if (! extension_loaded('apcu')) {
                    StandardComposerIO::result('WARN', 'APCu extension unavailable; Composer cannot use APCu in this PHP process');
                    return;
                }

                if (function_exists('apcu_enabled') && ! apcu_enabled()) {
                    StandardComposerIO::result('WARN', 'APCu extension loaded but disabled; Composer cannot use APCu in this PHP process');
                    return;
                }

                StandardComposerIO::result('DONE', 'APCu extension available and enabled for this PHP process');
            });
        } finally {
            StandardComposerIO::restore($previous_io);
        }

        $composer->getInstallationManager()->addInstaller(new LCBaseInstaller($io, $composer));
    }
}
