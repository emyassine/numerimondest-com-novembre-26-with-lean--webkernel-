<?php declare(strict_types=1);

namespace App\Studio\Http;

use App\Studio\Catalog\ProjectCatalog;
use App\Studio\Catalog\ServiceCatalog;
use App\Studio\Integration\Perfex\PerfexAccountSession;
use App\Studio\Integration\Perfex\PerfexLeadGateway;
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
        $catalog = ServiceCatalog::all();
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
        $catalog = ServiceCatalog::all();
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
        $catalog = ServiceCatalog::all();
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

    public static function submit(Request $request): RedirectResponse
    {
        $values = $request->all();
        $catalog = ServiceCatalog::all();
        $selectedSlugs = self::selectedSlugs();
        $selectedServices = array_map(static fn (string $slug): array => $catalog[$slug], $selectedSlugs);
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
        $recruitmentOffers = array_values(array_filter($selectedServices, static fn (array $offer): bool => isset($offer['recruitment_category'])));
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
            && mb_strlen($hiringTimeline) <= 120;

        if (! $valid) {
            return self::returnToForm('invalid');
        }

        $leadMessage = [
            'Numerimondes private discussion request',
            'Preferred quote currency: '.$preferredCurrency,
        ];
        if ($selectedServices === [] && $selectedProjects === []) {
            $leadMessage[] = 'Enquiry type: General enquiry (no offer selected)';
        }
        foreach ($selectedServices as $offer) {
            $offerLabel = isset($offer['parent'], $offer['parent_name'])
                ? 'Extension · '.$offer['parent_name'].' — '.$offer['name']
                : $offer['name'];
            $leadMessage[] = 'Selected service: '.$offerLabel;
            $leadMessage[] = 'Offer format: '.$offer['format'];
            $leadMessage[] = ! empty($offer['price_display']) ? 'Published pricing: '.$offer['price_display'] : '';
        }
        foreach ($selectedProjects as $project) {
            $leadMessage[] = 'Project of interest: '.$project['name'];
        }
        if ($isRecruitment) {
            foreach ($recruitmentOffers as $offer) {
                $leadMessage[] = 'Recruitment category: '.$offer['name'];
            }
            $leadMessage[] = 'Recruitment role: '.$recruitmentRole;
            $leadMessage[] = 'Work location: '.$recruitmentLocation;
            $leadMessage[] = $employmentType !== '' ? 'Employment type: '.$employmentType : '';
            $leadMessage[] = $hiringTimeline !== '' ? 'Target start: '.$hiringTimeline : '';
            if ($monthlySalary !== null) {
                $feePercentage = (float) $recruitmentOffer['fee_percentage'];
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

        if (! PerfexLeadGateway::create($lead)) {
            return self::returnToForm('delivery');
        }

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

    /** @param array<string, array<string, mixed>> $catalog
     *  @return list<string>
     */
    private static function validSlugs(array $requestedSlugs, array $catalog): array
    {
        return array_values(array_unique(array_filter(
            $requestedSlugs,
            static fn (mixed $slug): bool => is_string($slug) && isset($catalog[$slug]),
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
