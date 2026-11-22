<?php

namespace Webkernel\Platform;

use Webkernel\Platform\Support\Invader;

use BackedEnum;
use Webkernel\Platform\Facades\Platform;
use \Webkernel\Accounts\Access\AuthorizationException;
use \Webkernel\Accounts\Access\Response;
use Webkernel\ORM\Model;
use Webkernel\Utilities\Arr;
use \Webkernel\Accounts\Gate;
use LogicException;
use UnitEnum;

if (! function_exists('Webkernel\Platform\authorize')) {
    /**
     * @param  Model|class-string<Model>  $model
     *
     * @throws AuthorizationException
     */
    function authorize(string | UnitEnum $action, Model | string $model, bool $shouldCheckPolicyExistence = true): Response
    {
        return get_authorization_response($action, $model, $shouldCheckPolicyExistence)->authorize();
    }
}

if (! function_exists('Webkernel\Platform\get_authorization_response')) {
    /**
     * @param  Model|class-string<Model>  $model
     */
    function get_authorization_response(string | UnitEnum $action, Model | string $model, bool $shouldCheckPolicyExistence = true): Response
    {
        $user = Platform::auth()->user();

        $actionValue = match (true) {
            $action instanceof BackedEnum => $action->value,
            $action instanceof UnitEnum => $action->name,
            default => $action,
        };

        if (! $shouldCheckPolicyExistence) {
            if (
                Platform::isAuthorizationStrict()
                && (! Gate::forUser($user)->has($action))
                && (
                    blank($policyClass = Gate::getPolicyFor($model))
                    || (! method_exists($policyClass, $actionValue))
                )
            ) {
                $modelName = is_string($model) ? $model : $model::class;

                throw new LogicException(blank($policyClass)
                    ? "Strict authorization mode is enabled, but no ability [{$actionValue}] or policy with method [{$actionValue}()] was found for [{$modelName}]."
                    : "Strict authorization mode is enabled, but no ability [{$actionValue}] or [{$actionValue}()] method was found on [{$policyClass}].");
            }

            return Gate::forUser($user)->inspect($action, Arr::wrap($model));
        }

        $policy = Gate::getPolicyFor($model);

        if (filled($policy) && method_exists($policy, $actionValue)) {
            return Gate::forUser($user)->inspect($action, Arr::wrap($model));
        }

        if (Platform::isAuthorizationStrict()) {
            $policyClass = match (true) {
                is_string($policy) => $policy,
                is_object($policy) => $policy::class,
                default => null,
            };

            $modelName = is_string($model) ? $model : $model::class;

            throw new LogicException(blank($policyClass)
                ? "Strict authorization mode is enabled, but no policy was found for [{$modelName}]."
                : "Strict authorization mode is enabled, but no [{$actionValue}()] method was found on [{$policyClass}].");
        }

        /** @var bool | Response | null $response */
        $response = Invader::of(Gate::forUser($user))->callBeforeCallbacks( /** @phpstan-ignore-line */
            $user,
            $action,
            [$model],
        );

        if ($response === false) {
            return Response::deny();
        }

        if (! $response instanceof Response) {
            return Response::allow();
        }

        return $response;
    }
}
