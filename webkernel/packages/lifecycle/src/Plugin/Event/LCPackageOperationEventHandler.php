<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Plugin\Event;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\InstalledVersions;
use Composer\Installer\PackageEvent;
use Composer\Util\Filesystem;
use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\Declaration\LCDeclarationReader;
use Webkernel\Lifecycle\Install\LCBaseInstaller;
use Webkernel\Lifecycle\Install\LCPackageType;

/** Reports install paths and outcomes for Webkernel package operations. */
final class LCPackageOperationEventHandler
{
    /**
     * Reports a Webkernel package operation.
     * @param $event PackageEvent Composer package operation event.
     * @return void
     * @throws \Throwable If the package install path cannot be resolved.
     */
    public function handle(PackageEvent $event): void
    {
        LCDeclarationReader::forget($event->getComposer());

        $operation = $event->getOperation();
        $package = match (true) {
            $operation instanceof UpdateOperation => $operation->getTargetPackage(),
            $operation instanceof InstallOperation,
            $operation instanceof UninstallOperation => $operation->getPackage(),
            default => null,
        };

        if ($package === null || LCPackageType::tryFrom($package->getType()) === null) {
            return;
        }

        $previous_io = StandardComposerIO::bind($event->getIO());
        $started_at = hrtime(true);

        try {
            StandardComposerIO::tree($event->getName(), function () use ($event, $package, $started_at): void {
                StandardComposerIO::branch(
                    sprintf('%s <fg=gray>(%s)</>', $package->getName(), $package->getType()),
                    /** @throws \Throwable If package operation reporting fails. */
                    function () use ($event, $package, $started_at): void {
                        try {
                            try {
                                $install_path = InstalledVersions::getInstallPath($package->getName());
                            } catch (\OutOfBoundsException) {
                                $install_path = null;
                            }

                            if ($install_path === null) {
                                $install_path = (new LCBaseInstaller($event->getIO(), $event->getComposer()))
                                    ->getInstallPath($package);
                            }

                            $install_path = (new Filesystem())->normalizePath($install_path);
                            StandardComposerIO::line(sprintf('<fg=gray>Install path: %s</>', $install_path));

                            if (str_starts_with($event->getName(), 'pre-')) {
                                StandardComposerIO::result('START', 'Composer is preparing this Webkernel package operation');
                                return;
                            }

                            StandardComposerIO::result(
                                'DONE',
                                'Composer completed this Webkernel package operation',
                                StandardComposerIO::duration(hrtime(true) - $started_at),
                            );
                        } catch (\Throwable $exception) {
                            StandardComposerIO::result(
                                'FAIL',
                                sprintf('%s: %s', $exception::class, $exception->getMessage()),
                                StandardComposerIO::duration(hrtime(true) - $started_at),
                            );
                            throw $exception;
                        }
                    },
                    'default',
                );
            });
        } finally {
            StandardComposerIO::restore($previous_io);
        }
    }
}
