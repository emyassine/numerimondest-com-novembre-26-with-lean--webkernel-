<?php declare(strict_types=1);

session_start();
$autoload = getenv('NM_PARTNER_AUTOLOAD') ?: __DIR__.'/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    echo 'Partner storefront dependencies are not installed.';
    exit;
}
require $autoload;
require __DIR__.'/Storefront.php';

$baseUrl = getenv('NM_API_BASE_URL') ?: '';
$partnerId = getenv('NM_PARTNER_ID') ?: '';
$token = getenv('NM_PARTNER_TOKEN') ?: '';
$cacheDirectory = getenv('NM_OFFERINGS_CACHE') ?: dirname(__DIR__, 2).'/var/offerings-cache';
if ($baseUrl === '' || $partnerId === '' || $token === '') {
    http_response_code(503);
    echo 'Partner API configuration is incomplete.';
    exit;
}
try {
    $storefront = new PartnerStorefront(new Numerimondes\Offerings\CatalogueClient(
        $baseUrl,
        $partnerId,
        $token,
        new Numerimondes\Offerings\SnapshotStore($cacheDirectory),
    ));
} catch (Throwable) {
    http_response_code(503);
    echo 'Partner API configuration is invalid.';
    exit;
}
$currency = strtoupper(substr((string) ($_POST['currency'] ?? 'MAD'), 0, 3));
if (!preg_match('/^[A-Z]{3}$/', $currency)) $currency = 'MAD';
$catalogue = null;
$catalogueError = null;
try {
    $catalogue = $storefront->catalogue();
} catch (Throwable) {
    $catalogueError = 'The Numerimondes catalogue is temporarily unavailable and this partner has no saved catalogue snapshot yet.';
    http_response_code(503);
}
$message = null;
$quote = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $catalogue !== null) {
    $postedToken = (string) ($_POST['csrf'] ?? '');
    if (!is_string($_SESSION['partner_storefront_csrf'] ?? null) || !hash_equals($_SESSION['partner_storefront_csrf'], $postedToken)) {
        $message = 'The form expired. Reload the page and try again.';
    } else {
        try {
            $ids = $_POST['offering_ids'] ?? [];
            $quantities = $_POST['quantities'] ?? [];
            if (!is_array($ids) || !is_array($quantities)) throw new InvalidArgumentException('Select one or more offerings.');
            $items = [];
            foreach (array_values($ids) as $id) {
                if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $id)) throw new InvalidArgumentException('Invalid offering selection.');
                $quantity = filter_var($quantities[$id] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
                if (!is_int($quantity)) throw new InvalidArgumentException('Invalid offering quantity.');
                $items[] = ['offering_id' => $id, 'quantity' => $quantity];
            }
            $quote = $storefront->quote($catalogue, $items, $currency);
        } catch (Throwable) {
            $message = 'A quote could not be prepared. Review the selection and try again.';
        }
    }
}
if (!is_string($_SESSION['partner_storefront_csrf'] ?? null)) $_SESSION['partner_storefront_csrf'] = bin2hex(random_bytes(24));
$escape = static fn (mixed $value): string => htmlspecialchars(is_string($value) ? $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$label = static function (mixed $translations) use ($escape): string {
    if (is_string($translations)) return $escape($translations);
    if (!is_array($translations)) return '';
    return $escape($translations['en'] ?? reset($translations) ?: '');
};
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Partner offering selection</title></head>
<body>
<main>
    <h1>Configure your engagement</h1>
    <?php if ($catalogueError !== null): ?><p role="alert"><?= $escape($catalogueError) ?></p>
    <?php elseif ($catalogue !== null): ?>
        <p>Published catalogue revision <?= $escape((string) $catalogue['catalog_revision']) ?>. Final prices are confirmed by the Numerimondes server.</p>
        <?php if ($message !== null): ?><p role="alert"><?= $escape($message) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $escape($_SESSION['partner_storefront_csrf']) ?>">
            <label>Display currency <select name="currency"><?php foreach (['MAD','EUR','USD','GBP','AED','SAR','CAD','CHF'] as $code): ?><option value="<?= $code ?>"<?= $currency === $code ? ' selected' : '' ?>><?= $code ?></option><?php endforeach; ?></select></label>
            <?php foreach ($catalogue['offerings'] as $offering): ?>
                <fieldset><legend><?= $label($offering['name'] ?? []) ?></legend>
                    <label><input type="checkbox" name="offering_ids[]" value="<?= $escape($offering['id']) ?>"> Select this offering</label>
                    <label>Quantity <input type="number" name="quantities[<?= $escape($offering['id']) ?>]" value="1" min="1" max="1000" inputmode="numeric"></label>
                    <?php foreach ($offering['extensions'] ?? [] as $extension): ?>
                        <div><label><input type="checkbox" name="offering_ids[]" value="<?= $escape($extension['id']) ?>"> <?= $label($extension['name'] ?? []) ?> (select the parent above too)</label>
                        <label>Quantity <input type="number" name="quantities[<?= $escape($extension['id']) ?>]" value="1" min="1" max="1000" inputmode="numeric"></label></div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <button type="submit">Request indicative quote</button>
        </form>
        <?php if ($quote !== null): ?>
            <section aria-live="polite"><h2>Indicative quote</h2>
                <p>Quote <?= $escape($quote['quote_id']) ?> · expires <?= $escape($quote['expires_at']) ?></p>
                <?php foreach ($quote['lines'] as $line): ?><article>
                    <h3><?= $label($line['name'] ?? []) ?></h3>
                    <p>Quantity: <?= $escape((string) $line['quantity']) ?><?= isset($line['unit']) && is_string($line['unit']) ? ' · '.$escape($line['unit']) : '' ?></p>
                    <?php foreach ($line['prices'] as $price): ?><p>
                        <?php if (is_numeric($price['unit_amount'] ?? $price['amount'] ?? null)): ?><?= $escape(number_format((float) ($price['unit_amount'] ?? $price['amount']), 2, '.', ',')) ?> <?= $escape($price['currency'] ?? $currency) ?> per unit<?php if (is_numeric($price['amount'] ?? null) && isset($price['unit_amount'])): ?> · total <?= $escape(number_format((float) $price['amount'], 2, '.', ',')) ?> <?= $escape($price['currency'] ?? $currency) ?><?php endif; ?>
                        <?php elseif (is_numeric($price['percentage'] ?? null)): ?><?= $escape((string) $price['percentage']) ?>% of the applicable basis
                        <?php else: ?>Price to be scoped<?php endif; ?>
                    </p><?php endforeach; ?>
                </article><?php endforeach; ?>
                <small>Catalogue revision <?= $escape($quote['catalog_revision']) ?> · pricing revision <?= $escape($quote['pricing_revision']) ?></small>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
