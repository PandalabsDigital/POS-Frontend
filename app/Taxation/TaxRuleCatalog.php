<?php

namespace App\Taxation;

class TaxRuleCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rules(): array
    {
        return array_merge(
            self::india(),
            self::gccVat('AE', 'uae', 5),
            self::gccVat('SA', 'ksa', 15),
            self::gccVat('BH', 'bhr', 10),
            self::gccVat('OM', 'omn', 5),
            self::notApplicable('QA', 'qat', 'VAT'),
            self::notApplicable('KW', 'kwt', 'VAT'),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function india(): array
    {
        return [
            self::rule('in_gst_restaurant_5', 'IN', 'GST', 'Restaurant Service', 'standard', 'in_gst_5', 'intra', 5, 'none', true, [
                ['CGST', 2.5], ['SGST', 2.5],
            ]),
            self::rule('in_igst_restaurant_5', 'IN', 'GST', 'Interstate Restaurant', 'standard', 'in_gst_5', 'inter', 5, 'none', false, [
                ['IGST', 5],
            ]),
            self::rule('in_gst_premises_18', 'IN', 'GST', 'Specified Premises Restaurant', 'standard', 'in_gst_18', 'intra', 18, 'available', false, [
                ['CGST', 9], ['SGST', 9],
            ]),
            self::rule('in_igst_premises_18', 'IN', 'GST', 'Interstate Specified Premises', 'standard', 'in_gst_18', 'inter', 18, 'available', false, [
                ['IGST', 18],
            ]),
            self::rule('in_gst_catering_18', 'IN', 'GST', 'Outdoor Catering', 'standard', 'in_gst_catering', 'intra', 18, 'available', false, [
                ['CGST', 9], ['SGST', 9],
            ]),
            self::rule('in_igst_catering_18', 'IN', 'GST', 'Interstate Outdoor Catering', 'standard', 'in_gst_catering', 'inter', 18, 'available', false, [
                ['IGST', 18],
            ]),
            self::rule('in_gst_zero', 'IN', 'GST', 'Zero Rated', 'zero_rated', 'in_gst_zero', 'any', 0, 'na', false, []),
            self::rule('in_gst_exempt', 'IN', 'GST', 'Exempt', 'exempt', 'in_gst_exempt', 'any', 0, 'na', false, []),
            self::rule('in_gst_oos', 'IN', 'GST', 'Out of Scope', 'out_of_scope', 'in_gst_oos', 'any', 0, 'na', false, []),
            self::rule('in_gst_na', 'IN', 'GST', 'Tax Not Applicable', 'not_applicable', 'in_gst_na', 'any', 0, 'na', false, []),
            self::rule('in_gst_reduced', 'IN', 'GST', 'Reduced Rated', 'reduced', 'in_gst_reduced', 'any', 0, 'na', false, [], false),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function gccVat(string $country, string $prefix, float $standardRate): array
    {
        return [
            self::rule("{$prefix}_vat_standard", $country, 'VAT', 'Standard Rated', 'standard', "{$prefix}_vat", 'any', $standardRate, 'na', true, [
                ['VAT', $standardRate],
            ]),
            self::rule("{$prefix}_vat_zero", $country, 'VAT', 'Zero Rated', 'zero_rated', "{$prefix}_vat_zero", 'any', 0, 'na', false, []),
            self::rule("{$prefix}_vat_exempt", $country, 'VAT', 'Exempt', 'exempt', "{$prefix}_vat_exempt", 'any', 0, 'na', false, []),
            self::rule("{$prefix}_vat_oos", $country, 'VAT', 'Out of Scope', 'out_of_scope', "{$prefix}_vat_oos", 'any', 0, 'na', false, []),
            self::rule("{$prefix}_vat_na", $country, 'VAT', 'Tax Not Applicable', 'not_applicable', "{$prefix}_vat_na", 'any', 0, 'na', false, []),
            self::rule("{$prefix}_vat_reduced", $country, 'VAT', 'Reduced Rated', 'reduced', "{$prefix}_vat_reduced", 'any', 0, 'na', false, [], false),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function notApplicable(string $country, string $prefix, string $taxName): array
    {
        return [
            self::rule("{$prefix}_vat_na", $country, $taxName, 'Not Currently Applicable', 'not_applicable', "{$prefix}_none", 'any', 0, 'na', true, []),
            self::rule("{$prefix}_vat_standard", $country, $taxName, 'Standard Rated (future)', 'standard', "{$prefix}_vat", 'any', 0, 'na', false, [
                ['VAT', 0],
            ], false),
            self::rule("{$prefix}_excise", $country, 'Excise Tax', 'Excise', 'standard', "{$prefix}_excise", 'any', 0, 'na', false, [], false),
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: float}>  $components
     * @return array<string, mixed>
     */
    private static function rule(
        string $code,
        string $country,
        string $taxName,
        string $taxType,
        string $classification,
        string $family,
        string $supplyScope,
        float $rate,
        string $itc,
        bool $isDefault,
        array $components,
        bool $isActive = true,
    ): array {
        return [
            'code' => $code,
            'country_code' => $country,
            'tax_name' => $taxName,
            'tax_type' => $taxType,
            'classification' => $classification,
            'family' => $family,
            'supply_scope' => $supplyScope,
            'rate' => $rate,
            'itc' => $itc,
            'is_default' => $isDefault,
            'is_active' => $isActive,
            'components' => array_map(fn (array $row) => [
                'name' => $row[0],
                'rate' => $row[1],
            ], $components),
        ];
    }
}
