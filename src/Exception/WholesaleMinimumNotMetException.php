<?php

namespace App\Exception;

/**
 * Aruncată la checkout când o comandă a unui cont angro nu atinge pragul
 * minim configurat în ShippingSettings (valoare și/sau număr de bucăți).
 */
final class WholesaleMinimumNotMetException extends \RuntimeException
{
}
