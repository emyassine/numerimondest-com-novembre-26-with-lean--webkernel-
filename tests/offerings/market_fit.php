<?php declare(strict_types=1);

require dirname(__DIR__, 2).'/internal/dependencies/autoload.php';

$root = dirname(__DIR__, 2);
$database = sys_get_temp_dir().'/nm-market-fit-catalog-'.bin2hex(random_bytes(6)).'.sqlite';
putenv('NM_OFFERINGS_DATABASE='.$database);
require __DIR__.'/initialize_test_catalogue.php';
initializeTestCatalogue($root);

use App\Studio\Http\MarketFitController;
use Webkernel\Route\Request;

$directory = sys_get_temp_dir().'/nm-market-fit-'.bin2hex(random_bytes(6));
try {
    $industry = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['industry' => 'education-training']), $directory);
    if ($industry->getStatusCode() !== 200 || $industry->getData(true)['success'] !== true) throw new RuntimeException('Partial industry selection was rejected.');
    $schoolRecommendations = $industry->getData(true)['recommendations'] ?? [];
    if (!in_array('mod-school', array_column($schoolRecommendations, 'id'), true)) throw new RuntimeException('An industry-only selection did not recommend the matching software module.');
    $need = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['business_need' => 'operations']), $directory);
    if ($need->getStatusCode() !== 200 || $need->getData(true)['success'] !== true) throw new RuntimeException('Partial business need selection was rejected.');
    $both = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['industry' => 'education-training', 'business_need' => 'operations']), $directory);
    if ($both->getStatusCode() !== 200 || $both->getData(true)['success'] !== true) throw new RuntimeException('Complete selector response was rejected.');
    $recommended = $both->getData(true)['recommendations'] ?? [];
    if (!is_array($recommended) || $recommended === []) throw new RuntimeException('The catalogue did not return recommendations for a complete answer pair.');
    foreach ($recommended as $item) if (!is_array($item) || !is_array($item['name'] ?? null) || !isset($item['name']['en'])) throw new RuntimeException('Recommendations lost their translated catalogue names.');
    $playbook = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['industry' => 'retail', 'business_need' => 'sales-marketing']), $directory);
    $playbookRecommendations = $playbook->getData(true)['recommendations'] ?? [];
    if (!in_array('pb-out', array_column($playbookRecommendations, 'id'), true) || !in_array('playbook', array_column($playbookRecommendations, 'type'), true)) throw new RuntimeException('A matching playbook was not returned from canonical catalogue data.');
    $revenueForce = $playbook;
    $revenueRecommendations = $revenueForce->getData(true)['recommendations'] ?? [];
    if (!in_array('sales-force', array_column($revenueRecommendations, 'id'), true) || !in_array('service', array_column($revenueRecommendations, 'type'), true)) throw new RuntimeException('Revenue Force was not returned as a recommendation: '.json_encode($revenueRecommendations));
    $journey = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['team_size' => '11-50', 'profit_margin' => '0-10', 'ownership' => 'partners', 'discovery' => 'months']), $directory);
    if ($journey->getStatusCode() !== 200 || $journey->getData(true)['success'] !== true) throw new RuntimeException('Optional business journey responses were rejected.');
    $invalid = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['industry' => 'made-up']), $directory);
    if ($invalid->getStatusCode() !== 422) throw new RuntimeException('Invalid selector values were accepted.');
    $invalidJourney = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['team_size' => 'all-employees']), $directory);
    if ($invalidJourney->getStatusCode() !== 422) throw new RuntimeException('Invalid business journey values were accepted.');
    foreach (['shortlist_add', 'shortlist_remove', 'detail_open', 'extension_add', 'currency_change', 'welcome_complete', 'enquiry_submit'] as $event) {
        $result = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['event' => $event]), $directory);
        if ($result->getStatusCode() !== 200 || $result->getData(true)['success'] !== true) throw new RuntimeException('An allow-listed aggregate interaction event was rejected: '.$event);
    }
    $invalidEvent = MarketFitController::record(new Request('POST', '/services/recommendations', body: ['event' => 'email:visitor@example.test']), $directory);
    if ($invalidEvent->getData(true)['success'] !== false) throw new RuntimeException('An unrecognized or identifying event value was stored.');
    $events = json_decode((string) file_get_contents($directory.'/service-events.json'), true, 512, JSON_THROW_ON_ERROR);
    $eventDay = reset($events['counts']);
    if (count($eventDay) !== 7 || ($eventDay['welcome_complete'] ?? 0) !== 1) throw new RuntimeException('The aggregate interaction event counters are not recorded as daily counts.');
    $data = json_decode((string) file_get_contents($directory.'/service-market-fit.json'), true, 512, JSON_THROW_ON_ERROR);
    $daily = reset($data['counts']);
    if (($daily['industries']['education-training'] ?? 0) !== 2 || ($daily['industries']['retail'] ?? 0) !== 1 || ($daily['business_needs']['operations'] ?? 0) !== 2 || ($daily['business_needs']['sales-marketing'] ?? 0) !== 1 || count($daily['combinations'] ?? []) !== 2
        || ($daily['journey']['team_size']['11-50'] ?? 0) !== 1 || ($daily['journey']['ownership']['partners'] ?? 0) !== 1) {
        throw new RuntimeException('Aggregate selector counts are incorrect or incomplete.');
    }
    echo "Market-fit selector and cross-type recommendation checks passed.\n";
} finally {
    @unlink($directory.'/service-market-fit.json');
    @unlink($directory.'/service-events.json');
    @rmdir($directory);
    putenv('NM_OFFERINGS_DATABASE');
    foreach ([$database, $database.'-wal', $database.'-shm'] as $file) if (is_file($file)) @unlink($file);
}
