<?php

namespace Webkernel\Platform\Commands;


use function console;
use Webkernel\Platform\Support\Commands\Concerns\HasPanel;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use \Webkernel\Accounts\EloquentUserProvider;
use \Webkernel\Console\Command;
use \Webkernel\Accounts\Contracts\Authenticatable;
use \Webkernel\Accounts\Contracts\Guard;
use \Webkernel\Accounts\Contracts\UserProvider;
use Webkernel\ORM\Model;
use \Webkernel\Cryptography\Hash;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:platform-user', aliases: [
    'platform:make-user',
    'platform:user',
])]
class MakeUserCommand extends Command
{
    use HasPanel;

    protected $description = 'Create a new Platform user';

    protected $name = 'make:platform-user';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'platform:make-user',
        'platform:user',
    ];

    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'name',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The name of the user',
            ),
            new InputOption(
                name: 'email',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'A valid and unique email address',
            ),
            new InputOption(
                name: 'password',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The password for the user (min. 8 characters)',
            ),
            new InputOption(
                name: 'panel',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The panel to create the user in',
            ),
        ];
    }

    /**
     * @var array{'name': string | null, 'email': string | null, 'password': string | null}
     */
    protected array $options;

    public function handle(): int
    {
        try {
            $this->configurePanel(question: 'Which panel would you like to create this user in?');
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->options = $this->options();

        if (! $this->panel) {
            $this->error('Platform has not been installed yet: ./platform platform:install --panels');

            return static::FAILURE;
        }

        $user = $this->createUser();
        $this->sendSuccessMessage($user);

        return static::SUCCESS;
    }

    /**
     * @return array{'name': string, 'email': string, 'password': string}
     */
    protected function getUserData(): array
    {
        return [
            'name' => $this->options['name'] ?? console()->text(
                label: 'Name',
                required: true,
            ),

            'email' => $this->options['email'] ?? console()->text(
                label: 'Email address',
                required: true,
                validate: fn (string $email): ?string => match (true) {
                    ! filter_var($email, FILTER_VALIDATE_EMAIL) => 'The email address must be valid.',
                    static::getUserModel()::query()->where('email', $email)->exists() => 'A user with this email address already exists',
                    default => null,
                },
            ),

            'password' => Hash::make($this->options['password'] ?? console()->password(
                label: 'Password',
                required: true,
            )),
        ];
    }

    protected function createUser(): Model & Authenticatable
    {
        /** @var Model & Authenticatable $user */
        $user = static::getUserModel()::query()->create($this->getUserData());

        return $user;
    }

    protected function sendSuccessMessage(Model & Authenticatable $user): void
    {
        $loginUrl = $this->panel->getLoginUrl();

        $this->components->info('Success! ' . ($user->getAttribute('email') ?? $user->getAttribute('username') ?? 'You') . " may now log in at {$loginUrl}");
    }

    protected function getAuthGuard(): Guard
    {
        return $this->panel->auth();
    }

    protected function getUserProvider(): UserProvider
    {
        return $this->getAuthGuard()->getProvider();
    }

    /**
     * @return class-string<Model & Authenticatable>
     */
    protected function getUserModel(): string
    {
        /** @var EloquentUserProvider $provider */
        $provider = $this->getUserProvider();

        return $provider->getModel();
    }
}
