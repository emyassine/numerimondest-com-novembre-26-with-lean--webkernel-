<?php declare(strict_types=1);

namespace App\Studio\Http;

use App\Studio\Catalog\ProjectCatalog;
use App\Studio\Catalog\OfferingCatalog;
use App\Studio\Analytics\MarketFitAnalytics;
use App\Studio\Catalog\Offering;
use App\Studio\Integration\Perfex\PerfexAccountSession;
use App\Studio\Integration\Perfex\PerfexLeadGateway;
use App\Studio\Persistence\OfferingStore;
use App\Studio\Persistence\QuoteStore;
use App\Studio\Pricing\CurrencyRates;
use App\Studio\Pricing\OfferingPriceResolver;
use App\Studio\Pricing\QuoteSnapshot;
use App\Studio\Shortlist\ServiceShortlist;
use Webkernel\HttpFoundation\RedirectResponse;
use Webkernel\Route\Request;
use Webkernel\Session\Session;

final class ServiceInquiryController
{
    private const string SELECTION_SESSION_KEY = 'numerimondes.service_inquiry.services';
    private const string PROJECTS_SESSION_KEY = 'numerimondes.service_inquiry.projects';
    private const string ERROR_SESSION_KEY = 'numerimondes.service_inquiry.error';

    public static function selectedSlugs(): array
    {
        $catalog = OfferingCatalog::all();
        if (! Session::has(self::SELECTION_SESSION_KEY)) {
            return self::syncSelection(ServiceShortlist::all());
        }

        $selected = Session::get(self::SELECTION_SESSION_KEY, []);
        if (! is_array($selected)) {
            return [];
        }

        return self::validSlugs($selected, $catalog);
    }

    /** @param list<string> $slugs
     *  @return list<string>
     */
    public static function syncSelection(array $slugs): array
    {
        $catalog = OfferingCatalog::all();
        $selected = array_slice(self::validSlugs($slugs, $catalog), 0, 30);
        Session::put(self::SELECTION_SESSION_KEY, $selected);

        return $selected;
    }

    /** @return list<string> */
    public static function selectedProjects(): array
    {
        $selected = Session::get(self::PROJECTS_SESSION_KEY, []);
        if (! is_array($selected)) {
            return [];
        }

        return array_slice(self::validProjectSlugs($selected, ProjectCatalog::all()), 0, 30);
    }

    public static function select(Request $request): RedirectResponse
    {
        $values = $request->all();
        $catalog = OfferingCatalog::all();
        $requestedSlugs = $values['services'] ?? [];
        $requestedSlugs = is_array($requestedSlugs) ? self::validSlugs($requestedSlugs, $catalog) : [];
        $selectedSlugs = self::validSlugs([...self::selectedSlugs(), ...$requestedSlugs], $catalog);

        $selectedSlugs = self::syncSelection($selectedSlugs);
        ServiceShortlist::save($selectedSlugs);

        $projectCatalog = ProjectCatalog::all();
        $requestedProjects = $values['projects'] ?? [];
        $requestedProjects = is_array($requestedProjects) ? self::validProjectSlugs($requestedProjects, $projectCatalog) : [];
        $selectedProjects = self::validProjectSlugs([...self::selectedProjects(), ...$requestedProjects], $projectCatalog);
        Session::put(self::PROJECTS_SESSION_KEY, array_slice($selectedProjects, 0, 30));
        Session::forget(self::ERROR_SESSION_KEY);

        return new RedirectResponse(simple_site('studio', 'service-inquiry'), 303);
    }

    public static function removeProject(Request $request): RedirectResponse
    {
        $values = $request->all();
        $slug = self::stringValue($values['project'] ?? null);
        $selected = array_values(array_filter(
            self::selectedProjects(),
            static fn (string $selectedSlug): bool => $selectedSlug !== $slug,
        ));
        Session::put(self::PROJECTS_SESSION_KEY, $selected);

        return new RedirectResponse(simple_site('studio', 'service-inquiry'), 303);
    }

    public static function submit(Request $request, ?callable $leadCreator = null): RedirectResponse
    {
        $values = $request->all();
        $catalog = OfferingCatalog::all();
        $selectedSlugs = self::selectedSlugs();
        $selectedServices = array_map(static fn (string $slug): array => $catalog[$slug], $selectedSlugs);
        foreach ($selectedServices as &$selectedService) {
            $parent = OfferingCatalog::parentOf((string) ($selectedService['id'] ?? ''), 'extension');
            if ($parent !== null) {
                $selectedService['parent'] = $parent['id'];
                $selectedService['parent_name'] = $parent['name'];
            }
        }
        unset($selectedService);
        $selectedProjectSlugs = self::selectedProjects();
        $projectCatalog = ProjectCatalog::all();
        $selectedProjects = array_map(static fn (string $slug): array => $projectCatalog[$slug], $selectedProjectSlugs);

        if (count($selectedServices) > 30) {
            Session::flash(self::ERROR_SESSION_KEY, 'invalid');
            return new RedirectResponse(simple_site('studio', 'service-inquiry'), 303);
        }

        if (self::stringValue($values['website'] ?? null) !== '') {
            Session::forget(self::SELECTION_SESSION_KEY);
            Session::forget(self::PROJECTS_SESSION_KEY);
            return new RedirectResponse(simple_site('studio', 'service-request-received'), 303);
        }

        $name = trim(self::stringValue($values['name'] ?? null));
        $email = trim(self::stringValue($values['email'] ?? null));
        $company = trim(self::stringValue($values['company'] ?? null));
        $phone = trim(self::stringValue($values['phone'] ?? null));
        $message = trim(self::stringValue($values['message'] ?? null));
        $recruitmentOffers = array_values(array_filter($selectedServices, static fn (array $offer): bool => is_numeric($offer['prices'][0]['percentage'] ?? null)));
        $recruitmentOffer = $recruitmentOffers[0] ?? null;
        $isRecruitment = is_array($recruitmentOffer);
        $recruitmentRole = trim(self::stringValue($values['recruitment_role'] ?? null));
        $recruitmentLocation = trim(self::stringValue($values['recruitment_location'] ?? null));
        $monthlySalaryInput = trim(self::stringValue($values['monthly_salary'] ?? null));
        $monthlySalary = $monthlySalaryInput !== '' && is_numeric($monthlySalaryInput)
            ? (float) $monthlySalaryInput
            : null;
        $salaryCurrency = strtoupper(trim(self::stringValue($values['salary_currency'] ?? 'MAD')));
        $employmentType = trim(self::stringValue($values['employment_type'] ?? null));
        $hiringTimeline = trim(self::stringValue($values['hiring_timeline'] ?? null));
        $currencyConfig = config('nm-studio.currencies', []);
        $currencyOptions = is_array($currencyConfig) && is_array($currencyConfig['options'] ?? null)
            ? $currencyConfig['options']
            : [];
        $currencyCookie = is_array($currencyConfig) && is_string($currencyConfig['cookie'] ?? null)
            ? $currencyConfig['cookie']
            : 'numerimondes_currency';
        $preferredCurrency = self::stringValue($values['quote_currency'] ?? null);
        if (! isset($currencyOptions[$preferredCurrency])) {
            $preferredCurrency = is_string($_COOKIE[$currencyCookie] ?? null)
                && isset($currencyOptions[$_COOKIE[$currencyCookie]])
                ? $_COOKIE[$currencyCookie]
                : 'MAD';
        }
        $consent = self::stringValue($values['privacy_consent'] ?? null) === '1';
        $quantityResult = self::selectedQuantities($values['quantities'] ?? [], $selectedServices);
        $quantitiesValid = $quantityResult !== null;
        $quantities = $quantityResult ?? [];

        $valid = $name !== ''
            && mb_strlen($name) <= 120
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && mb_strlen($email) <= 254
            && mb_strlen($company) <= 160
            && mb_strlen($phone) <= 80
            && mb_strlen($message) >= 12
            && mb_strlen($message) <= 5000
            && $consent
            && (! $isRecruitment || ($recruitmentRole !== '' && mb_strlen($recruitmentRole) <= 160))
            && (! $isRecruitment || ($recruitmentLocation !== '' && mb_strlen($recruitmentLocation) <= 160))
            && ($monthlySalaryInput === '' || ($monthlySalary !== null && is_finite($monthlySalary) && $monthlySalary > 0 && $monthlySalary <= 1000000000))
            && ($monthlySalary === null || isset($currencyOptions[$salaryCurrency]))
            && mb_strlen($employmentType) <= 40
            && mb_strlen($hiringTimeline) <= 120
            && $quantitiesValid;

        if (! $valid) {
            return self::returnToForm('invalid');
        }

        $leadMessage = [
            'Numerimondes private discussion request',
            'Preferred quote currency: '.$preferredCurrency,
        ];
        $quotePrices = [];
        if ($selectedServices !== []) {
            try {
                $quote = self::createQuoteSnapshot($selectedServices, $preferredCurrency, $monthlySalary, $salaryCurrency, $quantities);
                (new QuoteStore())->save($quote);
                foreach ($quote->lines as $line) {
                    if (is_string($line['offering_id'] ?? null)) {
                        $quotePrices[$line['offering_id']] = self::formatQuotedPrices($line['prices'] ?? []);
                    }
                }
                $leadMessage[] = 'Offer quote reference: '.$quote->quoteId;
                $leadMessage[] = 'Catalogue revision: '.$quote->catalogRevision;
                $leadMessage[] = 'Pricing revision: '.$quote->pricingRevision;
            } catch (\Throwable $error) {
                error_log('Service enquiry quote snapshot failed: '.$error->getMessage());
                return self::returnToForm('pricing');
            }
        }
        if ($selectedServices === [] && $selectedProjects === []) {
            $leadMessage[] = 'Enquiry type: General enquiry (no offer selected)';
        }
        foreach ($selectedServices as $offer) {
            $parent = OfferingCatalog::parentOf((string)$offer['id'], 'extension');
            $offerLabel = is_array($parent)
                ? 'Extension · '.__t($parent['name'] ?? []).' — '.__t($offer['name'] ?? [])
                : __t($offer['name'] ?? []);
            $leadMessage[] = 'Selected service: '.$offerLabel;
            $quantity = $quantities[$offer['id']] ?? 1;
            $unit = self::offeringUnit($offer);
            $leadMessage[] = 'Quantity: '.$quantity.($unit !== null ? ' '.$unit.($quantity === 1 ? '' : 's') : '');
            $leadMessage[] = 'Offer format: '.($offer['format'] ?? $offer['prices'][0]['billing'] ?? 'Extension');
            $leadMessage[] = ! empty($quotePrices[$offer['id']]) ? 'Published pricing: '.$quotePrices[$offer['id']] : '';
        }
        foreach ($selectedProjects as $project) {
            $leadMessage[] = 'Project of interest: '.$project['name'];
        }
        if ($isRecruitment) {
            foreach ($recruitmentOffers as $offer) {
                $leadMessage[] = 'Recruitment category: '.__t($offer['name'] ?? []);
            }
            $leadMessage[] = 'Recruitment role: '.$recruitmentRole;
            $leadMessage[] = 'Work location: '.$recruitmentLocation;
            $leadMessage[] = $employmentType !== '' ? 'Employment type: '.$employmentType : '';
            $leadMessage[] = $hiringTimeline !== '' ? 'Target start: '.$hiringTimeline : '';
            if ($monthlySalary !== null) {
                $feePercentage = (float) $recruitmentOffer['prices'][0]['percentage'];
                $feeEstimate = number_format($monthlySalary * $feePercentage / 100, 2, '.', ',');
                $leadMessage[] = 'Monthly salary budget: '.number_format($monthlySalary, 2, '.', ',').' '.$salaryCurrency;
                $leadMessage[] = 'Indicative recruitment fee ('.$feePercentage.'%): '.$feeEstimate.' '.$salaryCurrency;
            }
        }
        $leadMessage[] = '';
        $leadMessage[] = $message;

        $lead = [
            'name' => $name,
            'email' => $email,
            'company' => $company,
            'phone' => $phone,
            'message' => implode("\n", array_filter($leadMessage, static fn (string $line): bool => $line !== '')),
        ];

        $leadCreated = $leadCreator === null
            ? PerfexLeadGateway::create($lead)
            : (bool) $leadCreator($lead);
        if (! $leadCreated) {
            return self::returnToForm('delivery');
        }

        MarketFitAnalytics::recordEvent('enquiry_submit');

        Session::forget(self::SELECTION_SESSION_KEY);
        Session::forget(self::PROJECTS_SESSION_KEY);
        Session::forget(self::ERROR_SESSION_KEY);

        return new RedirectResponse(
            simple_site('studio', 'service-request-received'),
            303,
        );
    }

    private static function returnToForm(string $error): RedirectResponse
    {
        Session::flash(self::ERROR_SESSION_KEY, $error);

        return new RedirectResponse(simple_site('studio', 'service-inquiry'), 303);
    }

    /** @param list<array<string,mixed>> $selectedServices */
    private static function createQuoteSnapshot(array $selectedServices, string $currency, ?float $salary, string $salaryCurrency, array $quantities): QuoteSnapshot
    {
        $catalogue = (new OfferingStore())->current('published');
        if ($catalogue === null) throw new \RuntimeException('No published catalogue is available for quoting.');
        $rateData = CurrencyRates::forPricing();
        $pricingRevision = hash('sha256', json_encode([
            'rates' => $rateData['rates'], 'dates' => $rateData['dates'],
            'sources' => $rateData['sources'], 'checked_at' => $rateData['checked_at'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $resolver = new OfferingPriceResolver();
        $lines = [];
        foreach ($selectedServices as $record) {
            $offering = Offering::fromArray($record);
            $basisValues = $salary !== null && $salary > 0 ? ['monthly_salary' => $salary] : [];
            $basisCurrencies = $basisValues !== [] ? ['monthly_salary' => $salaryCurrency] : [];
            $quantity = $quantities[$offering->id] ?? 1;
            $prices = $resolver->resolve($offering, $currency, null, null, $basisValues, $basisCurrencies, $rateData['rates'], $quantity);
            foreach ($prices as &$price) {
                unset($price['basis_amount'], $price['basis_currency']);
            }
            unset($price);
            $line = [
                'offering_id' => $offering->id,
                'name' => $offering->name,
                'type' => $offering->type->value,
                'quantity' => $quantity,
                'unit' => self::offeringUnit($record),
                'prices' => $prices,
            ];
            $parent = OfferingCatalog::parentOf($offering->id, 'extension');
            if ($parent !== null) {
                $line['parent_id'] = $parent['id'];
                $line['parent_name'] = $parent['name'];
            }
            $lines[] = $line;
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return new QuoteSnapshot(
            'q_'.bin2hex(random_bytes(16)), 'direct', (string)$catalogue['revision'], $pricingRevision,
            $currency, $lines, $now, $now->modify('+30 days'),
        );
    }

    /** @param list<array<string,mixed>> $components */
    private static function formatQuotedPrices(array $components): string
    {
        $parts = [];
        foreach ($components as $component) {
            $currency = strtoupper((string)($component['currency'] ?? 'MAD'));
            $label = __t($component['label'] ?? []);
            $amount = is_numeric($component['amount'] ?? null) ? (float)$component['amount'] : null;
            $minimum = is_numeric($component['minimum'] ?? null) ? (float)$component['minimum'] : null;
            $maximum = is_numeric($component['maximum'] ?? null) ? (float)$component['maximum'] : null;
            if (is_numeric($component['percentage'] ?? null)) {
                $basis = is_array($component['basis'] ?? null) ? $component['basis'] : [];
                $basisLabel = (($basis['kind'] ?? null) === 'monthly_salary' || ($basis['value_key'] ?? null) === 'monthly_salary') ? 'monthly salary' : 'the stated basis';
                $percentageText = number_format((float)$component['percentage'], 0).'% of '.$basisLabel;
                $part = $label !== '' && stripos($label, $percentageText) !== false
                    ? $label
                    : $percentageText.($label !== '' ? ' · '.$label : '');
                if ($amount !== null) $part .= ' · '.price($amount, $currency, $currency);
            } elseif ($minimum !== null && $maximum !== null) {
                $part = ($label !== '' ? $label.' · ' : '').price($minimum, $currency, $currency).'–'.price($maximum, $currency, $currency);
            } elseif ($amount !== null) {
                $part = ($label !== '' ? $label.' · ' : '').price($amount, $currency, $currency);
            } else {
                $part = $label;
            }
            if (is_string($component['unit'] ?? null)) {
                $unitAmount = is_numeric($component['unit_amount'] ?? null) ? (float) $component['unit_amount'] : null;
                $quantity = is_int($component['quantity'] ?? null) ? $component['quantity'] : 1;
                $part .= ' · '.$quantity.' '.$component['unit'].($quantity === 1 ? '' : 's');
                if ($unitAmount !== null) $part .= ' × '.price($unitAmount, $currency, $currency).' each';
            }
            $part .= match ($component['billing'] ?? '') {
                'monthly' => ' / month', 'quarterly' => ' / quarter', 'annual' => ' / year', default => '',
            };
            if ($part !== '') $parts[] = $part;
        }
        return implode(' + ', $parts);
    }

    /** @param list<array<string,mixed>> $offerings @return array<string,int>|null */
    private static function selectedQuantities(mixed $input, array $offerings): ?array
    {
        if (!is_array($input)) return null;
        $selected = [];
        foreach ($offerings as $offering) {
            $id = $offering['id'] ?? null;
            if (!is_string($id) || $id === '') return null;
            $selected[$id] = true;
        }
        if (array_diff_key($input, $selected) !== []) return null;
        $quantities = [];
        foreach ($offerings as $offering) {
            $id = (string) $offering['id'];
            $raw = $input[$id] ?? 1;
            if (is_string($raw) && preg_match('/^[0-9]{1,4}$/', $raw)) $raw = (int) $raw;
            if (!is_int($raw) || $raw < 1 || $raw > 1000) return null;
            if ($raw > 1 && self::offeringUnit($offering) === null) return null;
            $quantities[$id] = $raw;
        }
        return $quantities;
    }

    /** @param array<string,mixed> $offering */
    private static function offeringUnit(array $offering): ?string
    {
        $units = [];
        foreach ($offering['prices'] ?? [] as $component) {
            if (is_array($component) && is_string($component['unit'] ?? null)) $units[$component['unit']] = true;
        }
        return count($units) === 1 ? (string) array_key_first($units) : null;
    }

    /** @param array<string, array<string, mixed>> $catalog
     *  @return list<string>
     */
    private static function validSlugs(array $requestedSlugs, array $catalog): array
    {
        return array_values(array_unique(array_filter(
            $requestedSlugs,
            static fn (mixed $slug): bool => is_string($slug) && isset($catalog[$slug]) && ($catalog[$slug]['active'] ?? false) === true,
        )));
    }

    /** @param array<string, array<string, mixed>> $catalog
     *  @return list<string>
     */
    private static function validProjectSlugs(array $requestedSlugs, array $catalog): array
    {
        return array_values(array_unique(array_filter(
            $requestedSlugs,
            static fn (mixed $slug): bool => is_string($slug) && isset($catalog[$slug]),
        )));
    }

    private static function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
