<?php declare(strict_types=1);

namespace App\Studio\Catalog\Taxonomy;

enum Industry: string
{
    case AdvancedManufacturingServices = 'advanced-manufacturing-services';
    case AerospaceDefense = 'aerospace-defense';
    case Agribusiness = 'agribusiness';
    case Automotive = 'automotive';
    case Aviation = 'aviation';
    case Chemicals = 'chemicals';
    case ConstructionInfrastructure = 'construction-infrastructure';
    case ConsumerProducts = 'consumer-products';
    case EnergyNaturalResources = 'energy-natural-resources';
    case FinancialServices = 'financial-services';
    case HealthcareLifeSciences = 'healthcare-life-sciences';
    case MachineryEquipment = 'machinery-equipment';
    case MediaEntertainment = 'media-entertainment';
    case Mining = 'mining';
    case OilGas = 'oil-gas';
    case PaperPackaging = 'paper-packaging';
    case PrivateEquity = 'private-equity';
    case Retail = 'retail';
    case SocialImpact = 'social-impact';
    case Technology = 'technology';
    case Telecommunications = 'telecommunications';

    public function label(): string
    {
        return match ($this) {
            self::AdvancedManufacturingServices => 'Advanced Manufacturing & Services',
            self::AerospaceDefense => 'Aerospace & Defense',
            self::Agribusiness => 'Agribusiness',
            self::Automotive => 'Automotive',
            self::Aviation => 'Aviation',
            self::Chemicals => 'Chemicals',
            self::ConstructionInfrastructure => 'Construction & Infrastructure',
            self::ConsumerProducts => 'Consumer Products',
            self::EnergyNaturalResources => 'Energy & Natural Resources',
            self::FinancialServices => 'Financial Services',
            self::HealthcareLifeSciences => 'Healthcare & Life Sciences',
            self::MachineryEquipment => 'Machinery & Equipment',
            self::MediaEntertainment => 'Media & Entertainment',
            self::Mining => 'Mining',
            self::OilGas => 'Oil & Gas',
            self::PaperPackaging => 'Paper & Packaging',
            self::PrivateEquity => 'Private Equity',
            self::Retail => 'Retail',
            self::SocialImpact => 'Social Impact',
            self::Technology => 'Technology',
            self::Telecommunications => 'Telecommunications',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $industry) {
            $options[$industry->value] = $industry->label();
        }

        return $options;
    }
}
