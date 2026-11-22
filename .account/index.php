<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect/perfex_bridge.php';

/*
|--------------------------------------------------------------------------
| Perfex session
|--------------------------------------------------------------------------
*/

$staffId  = (int) get_staff_user_id();
$clientId = (int) get_client_user_id();

$isStaff  = $staffId > 0;
$isClient = $clientId > 0;

/*
|--------------------------------------------------------------------------
| URLs
|--------------------------------------------------------------------------
*/

$perfexBase = rtrim(APP_BASE_URL, '/') . '/';

$urls = [
    'client'       => $perfexBase . 'clients',
    'client_login' => $perfexBase . 'authentication/login',
    'client_logout'=> $perfexBase . 'authentication/logout',

    'staff'        => $perfexBase . 'admin',
    'staff_login'  => $perfexBase . 'admin/authentication',
    'staff_logout' => $perfexBase . 'admin/authentication/logout',
];

/*
|--------------------------------------------------------------------------
| Button state
|--------------------------------------------------------------------------
*/

if ($isStaff && $isClient) {
    $buttonLabel = 'Mon compte';
    $buttonIcon  = '◉';
} elseif ($isStaff) {
    $buttonLabel = 'Administration';
    $buttonIcon  = '◆';
} elseif ($isClient) {
    $buttonLabel = 'Espace client';
    $buttonIcon  = '●';
} else {
    $buttonLabel = 'Connexion';
    $buttonIcon  = '↗';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Numerimondes</title>

    <style>
        :root {
            --account-bg: #ffffff;
            --account-fg: #111111;
            --account-muted: #6b7280;
            --account-border: rgba(0, 0, 0, .12);
            --account-hover: #f5f5f5;
            --account-shadow: 0 16px 45px rgba(0, 0, 0, .12);
            --account-radius: 12px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --account-bg: #111111;
                --account-fg: #f5f5f5;
                --account-muted: #a3a3a3;
                --account-border: rgba(255, 255, 255, .14);
                --account-hover: #1d1d1d;
                --account-shadow: 0 18px 50px rgba(0, 0, 0, .45);
            }
        }

        * {
            box-sizing: border-box;
        }

        .nm-account {
            position: relative;
            display: inline-block;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .nm-account-button {
            appearance: none;
            border: 1px solid var(--account-border);
            background: var(--account-bg);
            color: var(--account-fg);

            min-height: 42px;
            padding: 0 14px;

            display: inline-flex;
            align-items: center;
            gap: 9px;

            border-radius: 999px;

            font: inherit;
            font-size: 14px;
            font-weight: 600;

            cursor: pointer;

            transition:
                background .15s ease,
                border-color .15s ease,
                transform .15s ease;
        }

        .nm-account-button:hover {
            background: var(--account-hover);
        }

        .nm-account-button:active {
            transform: scale(.98);
        }

        .nm-account-button-icon {
            width: 24px;
            height: 24px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: var(--account-fg);
            color: var(--account-bg);

            font-size: 11px;
        }

        .nm-account-chevron {
            width: 14px;
            height: 14px;

            transition: transform .18s ease;
        }

        .nm-account[data-open="true"]
        .nm-account-chevron {
            transform: rotate(180deg);
        }

        .nm-account-menu {
            position: absolute;
            z-index: 10000;

            right: 0;
            top: calc(100% + 8px);

            width: 250px;

            padding: 7px;

            border: 1px solid var(--account-border);
            border-radius: var(--account-radius);

            background: var(--account-bg);
            color: var(--account-fg);

            box-shadow: var(--account-shadow);

            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px) scale(.985);

            transform-origin: top right;

            transition:
                opacity .14s ease,
                visibility .14s ease,
                transform .14s ease;
        }

        .nm-account[data-open="true"]
        .nm-account-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .nm-account-section-label {
            padding: 8px 10px 5px;

            color: var(--account-muted);

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: .07em;
        }

        .nm-account-item {
            width: 100%;

            display: flex;
            align-items: center;
            gap: 11px;

            padding: 10px;

            border-radius: 8px;

            color: var(--account-fg);
            text-decoration: none;

            font-size: 14px;

            transition: background .12s ease;
        }

        .nm-account-item:hover {
            background: var(--account-hover);
        }

        .nm-account-item-icon {
            width: 30px;
            height: 30px;

            flex: 0 0 30px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: 1px solid var(--account-border);
            border-radius: 8px;

            font-size: 13px;
        }

        .nm-account-item-content {
            min-width: 0;

            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nm-account-item-title {
            font-weight: 600;
        }

        .nm-account-item-subtitle {
            color: var(--account-muted);

            font-size: 12px;
            font-weight: 400;
        }

        .nm-account-separator {
            height: 1px;

            margin: 6px 4px;

            background: var(--account-border);
        }

        .nm-account-logout {
            color: #dc2626;
        }
    </style>
</head>

<body>

<div
	style="margin-left:9rem;"
    class="nm-account"
    id="nmAccount"
    data-open="false"
>
    <button
        class="nm-account-button"
        id="nmAccountButton"
        type="button"
        aria-haspopup="true"
        aria-expanded="false"
    >
        <span class="nm-account-button-icon">
            <?= htmlspecialchars($buttonIcon) ?>
        </span>

        <span>
            <?= htmlspecialchars($buttonLabel) ?>
        </span>

        <svg
            class="nm-account-chevron"
            viewBox="0 0 20 20"
            fill="none"
            aria-hidden="true"
        >
            <path
                d="M5 7.5L10 12.5L15 7.5"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </button>

    <div
        class="nm-account-menu"
        role="menu"
        aria-labelledby="nmAccountButton"
    >

        <?php if (!$isClient && !$isStaff): ?>

            <div class="nm-account-section-label">
                Se connecter
            </div>

            <a
                class="nm-account-item"
                href="<?= htmlspecialchars($urls['client_login']) ?>"
            >
                <span class="nm-account-item-icon">
                    ●
                </span>

                <span class="nm-account-item-content">
                    <span class="nm-account-item-title">
                        Espace client
                    </span>

                    <span class="nm-account-item-subtitle">
                        Factures, projets, support…
                    </span>
                </span>
            </a>

            <a
                class="nm-account-item"
                href="<?= htmlspecialchars($urls['staff_login']) ?>"
            >
                <span class="nm-account-item-icon">
                    ◆
                </span>

                <span class="nm-account-item-content">
                    <span class="nm-account-item-title">
                        Administration
                    </span>

                    <span class="nm-account-item-subtitle">
                        Accès équipe Numerimondes
                    </span>
                </span>
            </a>

        <?php else: ?>

            <?php if ($isClient): ?>

                <div class="nm-account-section-label">
                    Client
                </div>

                <a
                    class="nm-account-item"
                    href="<?= htmlspecialchars($urls['client']) ?>"
                >
                    <span class="nm-account-item-icon">
                        ●
                    </span>

                    <span class="nm-account-item-content">
                        <span class="nm-account-item-title">
                            Espace client
                        </span>

                        <span class="nm-account-item-subtitle">
                            Ouvrir mon espace
                        </span>
                    </span>
                </a>

            <?php endif; ?>


            <?php if ($isStaff): ?>

                <?php if ($isClient): ?>
                    <div class="nm-account-separator"></div>
                <?php endif; ?>

                <div class="nm-account-section-label">
                    Équipe
                </div>

                <a
                    class="nm-account-item"
                    href="<?= htmlspecialchars($urls['staff']) ?>"
                >
                    <span class="nm-account-item-icon">
                        ◆
                    </span>

                    <span class="nm-account-item-content">
                        <span class="nm-account-item-title">
                            Administration
                        </span>

                        <span class="nm-account-item-subtitle">
                            Ouvrir Perfex CRM
                        </span>
                    </span>
                </a>

            <?php endif; ?>


            <div class="nm-account-separator"></div>


            <?php if ($isClient): ?>

                <a
                    class="nm-account-item nm-account-logout"
                    href="<?= htmlspecialchars($urls['client_logout']) ?>"
                >
                    <span class="nm-account-item-icon">
                        ↪
                    </span>

                    <span class="nm-account-item-content">
                        <span class="nm-account-item-title">
                            Déconnexion client
                        </span>
                    </span>
                </a>

            <?php endif; ?>


            <?php if ($isStaff): ?>

                <a
                    class="nm-account-item nm-account-logout"
                    href="<?= htmlspecialchars($urls['staff_logout']) ?>"
                >
                    <span class="nm-account-item-icon">
                        ↪
                    </span>

                    <span class="nm-account-item-content">
                        <span class="nm-account-item-title">
                            Déconnexion administration
                        </span>
                    </span>
                </a>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>


<script>
(() => {
    const account = document.getElementById('nmAccount');
    const button = document.getElementById('nmAccountButton');

    if (!account || !button) {
        return;
    }

    function setOpen(open) {
        account.dataset.open = open ? 'true' : 'false';

        button.setAttribute(
            'aria-expanded',
            open ? 'true' : 'false'
        );
    }

    button.addEventListener('click', (event) => {
        event.stopPropagation();

        setOpen(
            account.dataset.open !== 'true'
        );
    });

    account.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    document.addEventListener('click', () => {
        setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
            button.focus();
        }
    });
})();
</script>

</body>
</html>
