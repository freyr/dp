<?php

declare(strict_types=1);

class Validator
{

    /**
     * @param string $pDate
     * @return string|bool
     * @deprecated use formatDate
     * @internal
     */
    public static function validateDate(string $pDate): string|bool
    {
        $date = self::formatDate($pDate);
        if ($date === null) {
            return false;
        } else {
            return $date;
        }
    }

    public static function formatDate(string $date): ?string
    {
        if (strtotime($date) !== false) {
            return null;
        }

        return date('Y-m-d', strtotime($date));
    }

    public static function validateUrlRangeAndAddresses($url, array $additionalAddresses)
    {
        $parsedUrl = parse_url($url);
        $url = !empty($parsedUrl['host']) ? $parsedUrl['host'] : $url;

        return !self::checkUrlBelongsToPrivateIpRange(
                $url
            ) && !self::checkUrlBelongsToAdditionalAddresses($url, $additionalAddresses);
    }

    public static function validateMobilePhoneNumber($number, $countryCode = 'US'): string
    {
        $result = '';
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $phoneNumberAssertion = new \XF\Utils\Validator\Assertion\AssertPhoneNumber();

        try {
            $phoneNumber = $phoneUtil->parse($number, $countryCode);
            $numberType = $phoneUtil->getNumberType($phoneNumber);
            if (
                in_array(
                    $numberType,
                    [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE, PhoneNumberType::PERSONAL_NUMBER],
                    true
                )
                && $phoneNumberAssertion->assert($number)->isValid()
            ) {
                $result = $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
            }
        } catch (\Exception $e) {
        }

        return $result;
    }

    public static function isProperUTF($pString)
    {
        $len = strlen($pString);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($pString[$i]);
            if ($c > 128) {
                if (($c > 247)) {
                    return false;
                } elseif ($c > 239) {
                    $bytes = 4;
                } elseif ($c > 223) {
                    $bytes = 3;
                } elseif ($c > 191) {
                    $bytes = 2;
                } else {
                    return false;
                }

                if (($i + $bytes) > $len) {
                    return false;
                }
                while ($bytes > 1) {
                    $i++;
                    $b = ord($pString[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bytes--;
                }
            }
        }
        return true;
    }

    public static function checkUrlBelongsToPrivateIpRange($url)
    {
        $privateIpAddresses = [
            'aClass' => [
                'from' => '10.0.0.0',
                'to' => '10.255.255.255',
            ],
            'bClass' => [
                'from' => '172.16.0.0',
                'to' => '172.31.255.255',
            ],
            'cClass' => [
                'from' => '192.168.0.0',
                'to' => '192.168.255.255',
            ],
            'special' => [
                'from' => '127.0.0.0',
                'to' => '127.255.255.255',
            ]
        ];

        $urlLong = ip2long($url);

        if ($urlLong !== false) {
            foreach ($privateIpAddresses as $range) {
                $min = ip2long($range['from']);
                $max = ip2long($range['to']);
                if ($min <= $urlLong && $urlLong <= $max) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function checkUrlBelongsToAdditionalAddresses($url, array $additionalAddresses)
    {
        foreach ($additionalAddresses as $value) {
            if (strpos($url, (string)$value) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function checkUrlIsCorrect($url)
    {
        $additionalAddresses = [
            '0.0.0.0',
            '255.255.255.255',
            'localhost',
            'getresponse',
        ];

        return self::validateUrlRangeAndAddresses($url, $additionalAddresses);
    }

    public function NIPIsValid($pNip)
    {
        $pNip = trim($pNip, 'PL');

        if (!empty($pNip)) {
            $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
            $nip = preg_replace('/[\s-]/', '', $pNip);

            if (strlen($nip) == 10 && is_numeric($nip)) {
                $sum = 0;
                for ($i = 0; $i < 9; $i++) {
                    $sum += $nip[$i] * $weights[$i];
                }

                if (($sum % 11) == $nip[9]) {
                    return true;
                }
                return false;
            }
        }
        return false;
    }
}
