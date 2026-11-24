<?php declare(strict_types=1);

namespace App\Studio\Catalog\Taxonomy;

/** Maps existing offers to the market taxonomy used by the Services selector. */
final class OfferMarketFit
{
    /** @var list<Industry> */
    private const ALL_INDUSTRIES = [
        Industry::AdvancedManufacturingServices, Industry::AerospaceDefense, Industry::Agribusiness,
        Industry::Automotive, Industry::Aviation, Industry::Chemicals, Industry::ConstructionInfrastructure,
        Industry::ConsumerProducts, Industry::EnergyNaturalResources, Industry::FinancialServices,
        Industry::HealthcareLifeSciences, Industry::MachineryEquipment, Industry::MediaEntertainment,
        Industry::Mining, Industry::OilGas, Industry::PaperPackaging, Industry::PrivateEquity,
        Industry::Retail, Industry::SocialImpact, Industry::Technology, Industry::Telecommunications,
    ];

    /** @var array<string, array{industries?: list<Industry>, business_needs?: list<BusinessNeed>, inherits?: string}> */
    private const OFFERS = [
        'brand-core' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::HealthcareLifeSciences, Industry::FinancialServices],
            'business_needs' => [BusinessNeed::CustomerExperience, BusinessNeed::MarketExpansion, BusinessNeed::Innovation],
        ],
        'brand-system' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::HealthcareLifeSciences, Industry::FinancialServices, Industry::MediaEntertainment],
            'business_needs' => [BusinessNeed::CustomerExperience, BusinessNeed::DigitalStrategy, BusinessNeed::MarketExpansion, BusinessNeed::SalesMarketing],
        ],
        'brand-doctrine' => [
            'industries' => [Industry::PrivateEquity, Industry::FinancialServices, Industry::HealthcareLifeSciences, Industry::AdvancedManufacturingServices, Industry::Technology],
            'business_needs' => [BusinessNeed::Organization, BusinessNeed::Transformation, BusinessNeed::ChangeManagement, BusinessNeed::MarketExpansion],
        ],
        'dig-flagship' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::FinancialServices, Industry::HealthcareLifeSciences, Industry::Technology, Industry::MediaEntertainment],
            'business_needs' => [BusinessNeed::DigitalStrategy, BusinessNeed::CustomerExperience, BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing],
        ],
        'dig-cocon' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::FinancialServices, Industry::HealthcareLifeSciences, Industry::Automotive, Industry::Aviation],
            'business_needs' => [BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing, BusinessNeed::MarketExpansion, BusinessNeed::DigitalStrategy],
        ],
        'dig-multi' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::FinancialServices, Industry::HealthcareLifeSciences, Industry::Agribusiness, Industry::AdvancedManufacturingServices],
            'business_needs' => [BusinessNeed::MarketExpansion, BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing, BusinessNeed::DigitalStrategy],
        ],
        'sys-sprint' => [
            'industries' => [Industry::AdvancedManufacturingServices, Industry::Automotive, Industry::Retail, Industry::HealthcareLifeSciences, Industry::FinancialServices, Industry::Technology, Industry::Agribusiness, Industry::ConstructionInfrastructure],
            'business_needs' => [BusinessNeed::Operations, BusinessNeed::CostReduction, BusinessNeed::Transformation, BusinessNeed::ChangeManagement],
        ],
        'sys-crm' => [
            'industries' => [Industry::Retail, Industry::ConsumerProducts, Industry::FinancialServices, Industry::Technology, Industry::HealthcareLifeSciences, Industry::Telecommunications, Industry::Automotive, Industry::Aviation],
            'business_needs' => [BusinessNeed::SalesMarketing, BusinessNeed::RevenueGrowth, BusinessNeed::CustomerExperience, BusinessNeed::DataAnalytics, BusinessNeed::DigitalStrategy],
        ],
        'sys-saas' => [
            'industries' => [Industry::Technology, Industry::FinancialServices, Industry::HealthcareLifeSciences, Industry::Retail, Industry::Telecommunications, Industry::AdvancedManufacturingServices],
            'business_needs' => [BusinessNeed::Innovation, BusinessNeed::DigitalStrategy, BusinessNeed::DataAnalytics, BusinessNeed::Transformation],
        ],
        'sys-ent' => [
            'industries' => self::ALL_INDUSTRIES,
            'business_needs' => [BusinessNeed::DataAnalytics, BusinessNeed::DigitalStrategy, BusinessNeed::Operations, BusinessNeed::Organization, BusinessNeed::Transformation, BusinessNeed::CostReduction],
        ],
        'intel-diag' => [
            'industries' => self::ALL_INDUSTRIES,
            'business_needs' => [BusinessNeed::Operations, BusinessNeed::Organization, BusinessNeed::CostReduction, BusinessNeed::Transformation, BusinessNeed::RevenueGrowth],
        ],
        'intel-study' => [
            'industries' => self::ALL_INDUSTRIES,
            'business_needs' => [BusinessNeed::MarketExpansion, BusinessNeed::DataAnalytics, BusinessNeed::Innovation, BusinessNeed::DigitalStrategy],
        ],
        'intel-dd' => [
            'industries' => [Industry::PrivateEquity, Industry::FinancialServices, Industry::Technology, Industry::HealthcareLifeSciences, Industry::AdvancedManufacturingServices, Industry::ConstructionInfrastructure, Industry::Telecommunications],
            'business_needs' => [BusinessNeed::CostReduction, BusinessNeed::DataAnalytics, BusinessNeed::DigitalStrategy, BusinessNeed::Operations],
        ],
        'rec-ops' => [
            'industries' => self::ALL_INDUSTRIES,
            'business_needs' => [BusinessNeed::Organization, BusinessNeed::Operations, BusinessNeed::ChangeManagement],
        ],
        'rec-exec' => [
            'industries' => self::ALL_INDUSTRIES,
            'business_needs' => [BusinessNeed::Organization, BusinessNeed::ChangeManagement, BusinessNeed::Transformation],
        ],
        'sales-desk' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::FinancialServices, Industry::AdvancedManufacturingServices, Industry::Agribusiness, Industry::HealthcareLifeSciences, Industry::Telecommunications],
            'business_needs' => [BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing, BusinessNeed::MarketExpansion, BusinessNeed::CustomerExperience],
        ],
        'sales-force' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::FinancialServices, Industry::AdvancedManufacturingServices, Industry::Agribusiness, Industry::HealthcareLifeSciences, Industry::Telecommunications],
            'business_needs' => [BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing, BusinessNeed::Organization],
        ],
        'mod-book' => [
            'industries' => [Industry::Retail],
            'business_needs' => [BusinessNeed::Operations, BusinessNeed::CostReduction, BusinessNeed::DigitalStrategy],
        ],
        'mod-auto' => [
            'industries' => [Industry::EducationTraining, Industry::Automotive],
            'business_needs' => [BusinessNeed::Operations, BusinessNeed::CostReduction, BusinessNeed::DigitalStrategy],
        ],
        'mod-school' => [
            'industries' => [Industry::EducationTraining],
            'business_needs' => [BusinessNeed::Operations, BusinessNeed::CostReduction, BusinessNeed::DigitalStrategy],
        ],
        'brand-kit-essential' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology],
            'business_needs' => [BusinessNeed::CustomerExperience, BusinessNeed::MarketExpansion],
        ],
        'brand-kit-complete' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::HealthcareLifeSciences, Industry::FinancialServices, Industry::MediaEntertainment],
            'business_needs' => [BusinessNeed::CustomerExperience, BusinessNeed::DigitalStrategy, BusinessNeed::MarketExpansion, BusinessNeed::SalesMarketing],
        ],
        'brand-book' => [
            'industries' => [Industry::PrivateEquity, Industry::FinancialServices, Industry::HealthcareLifeSciences, Industry::AdvancedManufacturingServices, Industry::Technology],
            'business_needs' => [BusinessNeed::Organization, BusinessNeed::Transformation, BusinessNeed::ChangeManagement],
        ],
        'website-build' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::FinancialServices, Industry::HealthcareLifeSciences, Industry::Technology],
            'business_needs' => [BusinessNeed::DigitalStrategy, BusinessNeed::CustomerExperience, BusinessNeed::RevenueGrowth],
        ],
        'automation-sprint' => [
            'industries' => [Industry::AdvancedManufacturingServices, Industry::Automotive, Industry::Retail, Industry::HealthcareLifeSciences, Industry::FinancialServices, Industry::Technology, Industry::Agribusiness, Industry::ConstructionInfrastructure],
            'business_needs' => [BusinessNeed::Operations, BusinessNeed::CostReduction, BusinessNeed::Transformation],
        ],
        'crm-client-portal' => [
            'industries' => [Industry::Retail, Industry::ConsumerProducts, Industry::FinancialServices, Industry::Technology, Industry::HealthcareLifeSciences, Industry::Telecommunications],
            'business_needs' => [BusinessNeed::SalesMarketing, BusinessNeed::RevenueGrowth, BusinessNeed::CustomerExperience, BusinessNeed::DataAnalytics],
        ],
        'revenue-desk-pilot' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::FinancialServices, Industry::AdvancedManufacturingServices, Industry::Agribusiness, Industry::HealthcareLifeSciences, Industry::Telecommunications],
            'business_needs' => [BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing, BusinessNeed::MarketExpansion],
        ],
        'revenue-desk-growth' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::FinancialServices, Industry::AdvancedManufacturingServices, Industry::Agribusiness, Industry::HealthcareLifeSciences, Industry::Telecommunications],
            'business_needs' => [BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing, BusinessNeed::MarketExpansion],
        ],
        'revenue-desk-scale' => [
            'industries' => [Industry::ConsumerProducts, Industry::Retail, Industry::Technology, Industry::FinancialServices, Industry::AdvancedManufacturingServices, Industry::Agribusiness, Industry::HealthcareLifeSciences, Industry::Telecommunications],
            'business_needs' => [BusinessNeed::RevenueGrowth, BusinessNeed::SalesMarketing, BusinessNeed::MarketExpansion],
        ],
        'ops-audit' => [
            'industries' => self::ALL_INDUSTRIES,
            'business_needs' => [BusinessNeed::Operations, BusinessNeed::Organization, BusinessNeed::CostReduction, BusinessNeed::Transformation],
        ],
        'remote-talent-search' => [
            'industries' => self::ALL_INDUSTRIES,
            'business_needs' => [BusinessNeed::Organization, BusinessNeed::Operations, BusinessNeed::ChangeManagement],
        ],
        'pb-sem' => ['inherits' => 'dig-cocon'],
        'pb-rem' => ['inherits' => 'rec-ops'],
        'pb-out' => ['inherits' => 'sales-desk'],
        'pb-saas' => ['inherits' => 'sys-saas'],
        'pb-brand' => ['inherits' => 'brand-doctrine'],
        'x-brand-kit' => ['inherits' => 'brand-system'],
        'x-brand-multi' => ['inherits' => 'brand-doctrine'],
        'x-flag-bi' => ['inherits' => 'dig-flagship'],
        'x-flag-lead' => ['inherits' => 'dig-flagship'],
        'x-cocon-10' => ['inherits' => 'dig-cocon'],
        'x-cocon-ml' => ['inherits' => 'dig-cocon'],
        'x-cocon-links' => ['inherits' => 'dig-cocon'],
        'x-multi-mkt' => ['inherits' => 'dig-multi'],
        'x-multi-gov' => ['inherits' => 'dig-multi'],
        'x-sprint-flow' => ['inherits' => 'sys-sprint'],
        'x-sprint-train' => ['inherits' => 'sys-sprint'],
        'x-crm-int' => ['inherits' => 'sys-crm'],
        'x-crm-app' => ['inherits' => 'sys-crm'],
        'x-saas-admin' => ['inherits' => 'sys-saas'],
        'x-saas-pay' => ['inherits' => 'sys-saas'],
        'x-saas-sec' => ['inherits' => 'sys-saas'],
        'x-ent-team' => ['inherits' => 'sys-ent'],
        'x-study-geo' => ['inherits' => 'intel-study'],
        'x-desk-seg' => ['inherits' => 'sales-desk'],
        'x-desk-mo' => ['inherits' => 'sales-desk'],
    ];

    /** @param array<string|int, array<string, mixed>> $offers
     *  @return array<string|int, array<string, mixed>>
     */
    public static function apply(array $offers): array
    {
        foreach ($offers as $key => &$offer) {
            if (! is_array($offer)) {
                continue;
            }

            $offerId = is_string($offer['id'] ?? null) ? $offer['id'] : (is_string($key) ? $key : '');
            $fit = self::for($offerId);
            if ($fit === null && is_string($offer['parent'] ?? null)) {
                $fit = self::for($offer['parent']);
            }
            if ($fit === null && is_string($offer['inherits'] ?? null)) {
                $fit = self::for($offer['inherits']);
            }
            $offer['industries'] = self::values($fit['industries'] ?? []);
            $offer['business_needs'] = self::values($fit['business_needs'] ?? []);
        }
        unset($offer);

        return $offers;
    }

    /** @param list<array<string, mixed>> $offers
     *  @return array{industries: array<string, int>, business_needs: array<string, int>}
     */
    public static function coverage(array $offers): array
    {
        $coverage = [
            'industries' => array_fill_keys(array_keys(Industry::options()), 0),
            'business_needs' => array_fill_keys(array_keys(BusinessNeed::options()), 0),
        ];

        foreach ($offers as $offer) {
            foreach (($offer['industries'] ?? []) as $industry) {
                if (isset($coverage['industries'][$industry])) {
                    $coverage['industries'][$industry]++;
                }
            }
            foreach (($offer['business_needs'] ?? []) as $need) {
                if (isset($coverage['business_needs'][$need])) {
                    $coverage['business_needs'][$need]++;
                }
            }
        }

        return $coverage;
    }

    /** @return array{industries: list<Industry>, business_needs: list<BusinessNeed>}|null */
    private static function for(string $id): ?array
    {
        if (! isset(self::OFFERS[$id])) {
            return null;
        }

        $mapping = self::OFFERS[$id];
        if (isset($mapping['inherits'])) {
            return self::for($mapping['inherits']);
        }

        return $mapping;
    }

    /** @param list<Industry|BusinessNeed> $cases
     *  @return list<string>
     */
    private static function values(array $cases): array
    {
        return array_values(array_map(static fn (Industry|BusinessNeed $case): string => $case->value, $cases));
    }
}
