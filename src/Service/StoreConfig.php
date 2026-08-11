<?php

namespace App\Service;

use App\ValueObject\ThemeColors;

/**
 * Identitatea magazinului curent, citită din .env.local. Nicio valoare
 * de brand nu trebuie scrisă direct în cod sau template-uri — totul trece
 * prin acest serviciu, injectat global în Twig ca `store`.
 */
final readonly class StoreConfig
{
    public function __construct(
        public string $name,
        public string $slogan,
        public string $domain,
        public string $defaultMetaDescription,
        public string $email,
        public string $phone,
        public string $companyName,
        public string $companyCui,
        public string $companyRegCom,
        public string $companyAddress,
        public bool $vatPayer,
        public string $vatRate,
        public string $invoiceSeries,
        public ThemeColors $themeColors,
        public string $logoPath,
        public string $faviconPath,
        public string $adsenseClientId,
        public string $googleAdsConversionId,
        public string $googleAnalyticsId,
        public string $bankAccount,
        public string $bankName,
        public string $themeMode,
    ) {
    }
}
