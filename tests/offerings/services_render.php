<?php declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
$database = sys_get_temp_dir().'/nm-services-render-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue(dirname(__DIR__, 2));
$_SERVER['REQUEST_URI'] = '/services';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
ob_start();
require dirname(__DIR__, 2).'/public/index.php';
$html = (string) ob_get_clean();
if (is_string(getenv('NM_SAVE_RENDER')) && getenv('NM_SAVE_RENDER') !== '') file_put_contents((string) getenv('NM_SAVE_RENDER'), $html);

if (http_response_code() !== 200) throw new RuntimeException('The services route did not return HTTP 200.');
if (!str_contains($html, 'Digital assets, systems and teams, built to compound.')) throw new RuntimeException('Services page hero was not rendered.');
if (!str_contains($html, 'Commissioned work · 17 mandates')) throw new RuntimeException('The services hero count does not include all 17 commissioned offers.');
if (!str_contains($html, 'Education &amp; Training')) throw new RuntimeException('Education & Training is absent from the industry selector.');
if (!str_contains($html, 'Private Schools &amp; Training Centres Module')) throw new RuntimeException('The school software offering is absent from the page.');
foreach (['aria-label="Search offers"', 'aria-label="Filter by practice"', 'aria-label="Filter by engagement type"', 'aria-live="polite"', 'id="nm-welcome-dialog"', 'data-intent="revenue"', 'nm_visited', 'new Intl.Locale'] as $accessibleControl) {
    if (!str_contains($html, $accessibleControl)) throw new RuntimeException('An accessible service-discovery control is missing: '.$accessibleControl);
}
if (!str_contains($html, '46% of monthly salary')) throw new RuntimeException('Percentage-based recruitment price was not resolved.');
if (!str_contains($html, 'Weekly commercial supervision') || !str_contains($html, 'One-time setup')) throw new RuntimeException('Revenue Force components were not rendered.');
$normalizedHtml = str_replace(["\u{202F}", "\u{00A0}"], ' ', $html);
if (!str_contains($normalizedHtml, '7 500 MAD') || !str_contains($normalizedHtml, '4 500 MAD')) throw new RuntimeException('Revenue Force one-time and monthly prices were not connected to the page data.');
if (!str_contains($html, '46% of monthly salary') || !str_contains($html, 'Recruitment fee via The Best Recruit')) throw new RuntimeException('Revenue Force recruitment pricing omitted its percentage or delivery partner.');
if (!str_contains($html, 'See recommendations') || !str_contains($html, 'data-reset-fit')
    || !str_contains($html, "fitForm?.addEventListener('reset'") || !str_contains($html, "addEventListener('click', () => fitForm.reset())")) {
    throw new RuntimeException('The recommendation selector reset control is not wired to clear its state.');
}
if (!str_contains($html, 'Services in Brand') || !str_contains($html, 'Services in Digital') || !str_contains($html, 'All practices')) throw new RuntimeException('The catalogue is not grouped and filtered by practice.');
if (!str_contains($html, 'Leads to: Institutional Brand Doctrine')) throw new RuntimeException('Playbook gateway presentation is not resolved from the canonical relation.');
if (preg_match('/Fatal error|Warning:|Undefined variable|ParseError/', $html)) throw new RuntimeException('The services route rendered an application error.');
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">'.$html);
$xpath = new DOMXPath($document);
foreach (['revenue-force', 'modules', 'playbooks'] as $anchor) {
    if ($xpath->query('//*[@id="'.$anchor.'"]')->length !== 1) throw new RuntimeException('Recommendation target is missing: '.$anchor);
}
$cards = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' nm-marketplace-card ')]");
if ($cards === false || $cards->length !== 16) throw new RuntimeException('The unified mandate catalogue did not render all 16 catalog offerings.');
$extensionCounts = [
    'brand-system' => 1, 'brand-doctrine' => 1, 'dig-flagship' => 2, 'dig-cocon' => 3,
    'dig-multi' => 2, 'sys-sprint' => 2, 'sys-crm' => 2, 'sys-saas' => 3,
    'sys-ent' => 1, 'intel-study' => 1, 'sales-desk' => 2,
];
foreach ($extensionCounts as $id => $count) {
    if (!str_contains($html, 'marketplace-service--'.$id)) throw new RuntimeException('A catalogue mandate is missing: '.$id);
}
foreach ([1 => 8, 2 => 10, 3 => 4] as $extensions => $expectedOccurrences) {
    $label = 'Configurable · '.$extensions.' '.($extensions === 1 ? 'extension' : 'extensions').' available';
    if (substr_count($html, $label) !== $expectedOccurrences) throw new RuntimeException('The page did not render all expected card and detail extension counts.');
}

echo 'Services route render passed: '.strlen($html).' bytes. '.PHP_EOL;
putenv('NM_OFFERINGS_DATABASE');
foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
