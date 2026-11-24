<?php declare(strict_types=1);

namespace App\Studio\Catalog\Taxonomy;

enum BusinessNeed: string
{
    case ChangeManagement = 'change-management';
    case CostReduction = 'cost-reduction';
    case CustomerExperience = 'customer-experience';
    case DataAnalytics = 'data-analytics';
    case DigitalStrategy = 'digital-strategy';
    case Innovation = 'innovation';
    case MarketExpansion = 'market-expansion';
    case Operations = 'operations';
    case Organization = 'organization';
    case RevenueGrowth = 'revenue-growth';
    case SalesMarketing = 'sales-marketing';
    case Transformation = 'transformation';

    public function label(): string
    {
        return match ($this) {
            self::ChangeManagement => 'Change Management',
            self::CostReduction => 'Cost Reduction',
            self::CustomerExperience => 'Customer Experience',
            self::DataAnalytics => 'Data Analytics',
            self::DigitalStrategy => 'Digital Strategy',
            self::Innovation => 'Innovation',
            self::MarketExpansion => 'Market Expansion',
            self::Operations => 'Operations',
            self::Organization => 'Organization',
            self::RevenueGrowth => 'Revenue Growth',
            self::SalesMarketing => 'Sales & Marketing',
            self::Transformation => 'Transformation',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $need) {
            $options[$need->value] = $need->label();
        }

        return $options;
    }
}
