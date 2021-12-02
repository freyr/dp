<?php

use Getresponse\App\Application;
use Getresponse\App\Domain\Account\AccountService;
use Getresponse\App\Domain\ApplicationSettings\ApplicationClientFactory;
use Getresponse\App\DomainModel\Event\ApplicationEvents;
use Getresponse\Foundation\Account\ReadModel\LegacyPanelTranslation\LegacyPanelTranslationSpace;
use Getresponse\Foundation\LanguageDomain\ReadModel\LanguageDomainReadModel;
use Getresponse\Foundation\SharedKernel\Language\Language;
use Getresponse\XEncoder\Alphabet;
use Getresponse\XEncoder\XEncoderCachedRepository;
use Getresponse\XEncoder\XEncoderDbRepository;
use Getresponse\XEncoder\XEncoderException;
use Getresponse\XEncoder\XEncoderService;
use Getresponse\XEncoder\XLink;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use XF\Amqp\AMQPException;
use XF\Amqp\Client\AMQPClientFactory;
use XF\Amqp\Message\AMQPMessage;
use XF\CacheFactory;
use XF\Config\Config;
use XF\Db\Connection\Configuration\DbConfiguration;
use XF\Db\DbFactory;
use XF\ErrorLogger\ErrorLoggerTrait;
use XF\Http\Client\HttpClientFactory;
use XF\Http\Request\ServerRequestProxyFactory;
use XF\Logger\Configuration\InvalidLoggerConfigurationException;
use XF\Logger\LoggerException;
use XF\Logger\LoggerFactory;
use XF\Utils\ArrayUtils;
use XF\Utils\IP\IP;
use XF\Utils\Json\JsonSerializer;
use XF\Utils\Json\JsonSerializerException;
use XF\Utils\Url\Url;
use XF\Utils\Validator\Assertion\AssertPhoneNumber;
use XF\Uuid\Uuid;

/**
 * Klasa zawiera metody ktore mozna uzywac w calym serwisie
 * @package GetResponse-classes
 *
 * @version $Id: CommonExtended.class.php 12622 2009-09-29 08:58:09Z pjar $
 */
class CommonExtended extends DataSource
{
    use ErrorLoggerTrait;

    const DEFAULT_CRYPTO_ID = 1;

    /**
     * @var XEncoderService
     */
    private static $xEncoderService;

    /**
     * @var array XLink[]
     */
    private static $xLinkEncoders = [];

    // lista alphabetow
    /**
     * Zaawansowne ustawienia kampani marketingowych
     * jezyk_kampani => typ_kampani_z_campaigns_map => nazwa_preferencji => lista preferencji z ustawieniami
     *
     * remove_on_move_from_campaigns:
     *  obslugiwane z poziomu skryptu, w momencie dodania do nowej,
     *  usuniecie z poprzedniej kampani (from_campaigns_type) dla danego jezyka (only_from_lang - gdy nie podano, usuwa ze wszystkich)
     *  uzywane przy upgrade free -> pro
     *
     * move_subscriber_to_campaign_with_previous_sequence_from_campaigns:
     *  pobieranie ustawienia dnia sekwencji autoresponderowej
     *  w aktualnej kampani (from_campaigns_type)
     *  w danym jezyku (only_from_lang - gdy nie podano,  ) i przeniesienie go do nowej
     *  :increase_sequence_if_autoresponder_from_previous_sent
     *   zwieksza dzien sekwencji jezeli autoresponder z pierwotnej kampanii został wysłany
     *   brak definicji spowoduje ustawienie dania kamapanii docelowej na taki sam jak pierwotnej
     *   uzywane przy upgrade free -> pro
     *
     * @var array
     */
    public static $MarketingCampaignsAdvanceSettings = [
        'pl' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['pl'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
            'SIGN_ME_TO_CAMPAIGN_UPGRADE_NO' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO',
                ],
            ]
        ],
        'en' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['en', 'ru'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
            'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST',
                    'only_from_lang' => ['en', 'gb'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ]
        ],
        'es' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['es'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ]
        ],
        'de' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['de'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ]
        ],
        'ru' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['en', 'ru']
                ]
            ]
        ],
        'pt' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['pt', 'ru']
                ]
            ]
        ],
        'br' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['br', 'ru']
                ]
            ]
        ],
        'ms' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['ms']
                ]
            ]
        ],
        'th' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['th']
                ]
            ]
        ],
        'zh' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['zh']
                ]
            ]
        ],
        'fr' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['fr']
                ]
            ]
        ],
        'it' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['it']
                ]
            ]
        ],
        'tr' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['tr'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'sv' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['sv'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'ro' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['ro'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'nl' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['nl'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'da' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['da'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'hu' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['hu'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'vi' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['vi'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'id' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['id'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'ja' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['ja'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'no' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['no'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'ko' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['ko'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'mx' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['mx'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
        'ie' => [
            'SIGN_ME_TO_CAMPAIGN_UPGRADE' => [
                'remove_on_move_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                ],
                'move_subscriber_to_campaign_with_previous_sequence_from_campaigns' => [
                    'from_campaigns_type' => 'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL',
                    'only_from_lang' => ['ie'],
                    'increase_sequence_if_autoresponder_from_previous_sent' => true,
                ]
            ],
        ],
    ];

    // ustawienia dla exportu danych do CSV
    // ustawienia nie sa zgodne z RFC-4180 ale dzialaja w Excel
    // @link: http://tools.ietf.org/html/rfc4180
    public $delimiter = ';';
    public $text_separator = '"';
    public $replace_text_separator = ['from' => ['"'], 'to' => ['""']];
    public $line_delimiter = "\n";

    // lista parametrow obecnie wykozystywanych w xlinkach
    public $prohibitedDomains = [
        'getresponse.pl',
        'getresponse.de',
        'getresponse.com.pl'
    ];

    public $pFormatCountry = 'us';
    private Validator $validator;

    public function __construct(\xfDb $xfDb = null)
    {
        parent::__construct();
        if ($xfDb) {
            $this->xfDb = $xfDb;
        }
        $this->validator = new Validator();
    }

    /**
     * Zwraca adres zewnatrznych projektow powiazanych z GR w zaleznosci od jezyka.
     *
     * @param string $pLanguage
     * @return array
     */
    public static function getExternalUrlByLanguage($pLanguage = 'en')
    {
        $blog_url = [$pLanguage => 'https://blog.getresponse.com/'];
        $forum_url = [$pLanguage => 'http://forum.getresponse.com/'];
        $devzone_url = [$pLanguage => 'http://dev.getresponse.com/'];

        //custom settings
        switch ($pLanguage) {
            case 'pl':
                $blog_url = [$pLanguage => 'https://blog.getresponse.pl/'];
                $forum_url = [$pLanguage => 'http://forum.getresponse.pl/'];
                break;

            case 'ru':
                $blog_url = [$pLanguage => 'https://blog.getresponse.ru/'];
                break;

            default:
                break;
        }

        // zwracana tablica
        $urls['blog_url'] = $blog_url;
        $urls['forum_url'] = $forum_url;
        $urls['devzone_url'] = $devzone_url;

        return $urls;
    }

    /**
     * Listuje zawartosc katalogow
     *
     * @param string pPathToDir - sciezka listowanego katalogu
     * @param array pForbiddenDirs - wyklucza dane pliki
     * @return array zwraca tablice badz false gdy nie ma mozliwsci listowania
     */
    public static function getDirContent($pPathToDir, $pForbiddenDirs = ["..", ".", ".svn"])
    {
        if (!is_readable($pPathToDir)) {
            return false;
        }

        $handle = opendir($pPathToDir);
        $dir_names = [];

        while (($dir_name = readdir($handle)) != false) {
            if (!in_array($dir_name, $pForbiddenDirs)) {
                $dir_names[] = $dir_name;
            }
        }

        return $dir_names;
    }

    /**
     * Przekierowanie na podany link z parametrami
     *
     * @param string $redirectUrl
     * @param array $data
     * @param string $methodType
     * @param string $quotaType
     * @param int $redirectStatusCode
     */
    public static function redirectDataUrl($redirectUrl, $data, $methodType, $quotaType = 'double', $redirectStatusCode = 301)
    {
        switch ($methodType) {
            case 'get':
                // redirect razem z danymi przez GET'a
                $params = http_build_query($data, '', "&");

                // jezeli nic nie ma to nie dodawaj ?
                $char = null;
                if (!empty($params)) {
                    if (strstr($redirectUrl, "?") !== false) {
                        $char = '&';
                    } else {
                        $char = '?';
                    }
                }
                $xfCommon = new xfCommon();
                $xfCommon->redirect($redirectUrl . $char . $params, $redirectStatusCode, false);
                break;
            case 'post':
                // redirect razem z danymi przez POST'a
                echo '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body style="text-align:center; font-family: Arial,Helvetica,sans-serif; font-size:10px;">';
                echo '<img src="' . XF_BRAND_URL . 'images/core/global/default/icons/ajax-loader.gif" alt="" style="margin-top:200px;"></img>';
                flush();
                echo '<form name="redirectform" action="' . $redirectUrl . '" method="post">';
                echo '<input type="submit" id="button_submit" name="button_submit" style="display:none;"/>';
                if (is_array($data)) {
                    self::recurrsiveFormBuilder($data, '', $quotaType);
                }
                echo '<noscript>JavaScript is turned off in your web browser. Turn it on to take full advantage of this site, then click "Please Confirm".';
                echo '<br/><br/><input type="submit" value="Please Confirm" name="Please Confirm"/></noscript></form>';
                echo '<script language="javascript" type="text/javascript">setTimeout(function(){document.getElementById("button_submit").click();}, 500)</script>';
                echo '</body></html>';
                die;
                break;
            default :
                $xfCommon = new xfCommon();
                $xfCommon->redirect($redirectUrl, $redirectStatusCode, false);
                break;
        }
    }

    /**
     * Array moze miec kilka zaglebien
     *
     * @param Tablica $pData
     * @param Nazwa nadrzednych pol $pName
     */
    static private function recurrsiveFormBuilder($pData, $pName = '', $pQuotaType = 'double')
    {
        foreach ($pData as $subkey => $subval) {
            if (!empty($pName)) {
                $subkey = $pName . '[' . $subkey . ']';
            }
            if (is_array($subval)) {
                self::recurrsiveFormBuilder($subval, $subkey, $pQuotaType);
            } else {
                if ('single' == $pQuotaType) {
                    echo "<input type='hidden' name='" . $subkey . "' value='" . htmlspecialchars($subval, ENT_QUOTES) . "'/>";
                } else {
                    echo '<input type="hidden" name="' . $subkey . '" value="' . htmlspecialchars($subval, ENT_QUOTES) . '"/>';
                }
            }
        }
    }



    #
    # XEncoder deprecated methods, please use directly Xlink class from Dependency-Injection
    #

    /**
     * @param int $clientId
     * @return XLink
     */
    private static function getXLink(int $clientId = null)
    {
        $clientId = $clientId ?? defined('CRYPTO_ID') ? CRYPTO_ID : self::DEFAULT_CRYPTO_ID;

        if (!array_key_exists($clientId, static::$xLinkEncoders)) {
            static::$xLinkEncoders[$clientId] = static::getXEncoderService()->getXLink($clientId);
        }

        return static::$xLinkEncoders[$clientId];
    }

    /**
     * @return XEncoderService
     */
    private static function getXEncoderService(): XEncoderService
    {
        if (!static::$xEncoderService instanceof XEncoderService) {
            $cacheFactory = new CacheFactory();
            $cachePrefix = Config::get('APP_CACHE_PREFIX');
            $cacheHost = Config::get('APP_CACHE_HOST');

            $legacyCachePool = $cacheFactory->createLegacyCachePool($cacheHost);
            $dbCache = new XF\Db\Cache\BaseCache($legacyCachePool);
            $dbConfiguration = new DbConfiguration(
                Config::get('DB_HOST_MANAGEMENT'),
                Config::get('DB_NAME_MANAGEMENT'),
                Config::get('DB_USER_MANAGEMENT'),
                Config::get('DB_PASSWORD_MANAGEMENT'),
                Config::get('DB_PORT_MANAGEMENT'),
                'utf8mb4'
            );
            $dbInstance = DbFactory::createDbInstanceFromDbConfiguration($dbConfiguration, $dbCache);

            $cachePool = $cacheFactory->createCachePool($cachePrefix, $cacheHost);
            $repository = new XEncoderCachedRepository(
                new XEncoderDbRepository($dbInstance),
                $cachePool
            );

            $encoderService = new XEncoderService($repository);
            static::$xEncoderService = $encoderService;
        }
        return static::$xEncoderService;
    }

    /**
     * Zakoduj string domyślnym alfabetem
     *
     * @param $decodedValue
     * @return string
     *
     * @deprecated use XLink::encode()
     */
    public static function encodeXvalueWithDefaultAlphabet($decodedValue)
    {
        return static::getXLink()->encode($decodedValue, static::getXLink()->getDefaultSelector());
    }

    /**
     * Zakodowuje sekwencje
     *
     * @param string pSelector
     * @param integer pDataToEncode
     * @return string
     *
     * @deprecated use XLink::encode()
     */
    public static function encodeXvalue($selector, $decodedValue)
    {
        // preserve old interfce and returning false when data is empty
        if (!is_numeric($decodedValue)) {
            return false;
        }

        try {
            return static::getXLink()->encode($decodedValue, $selector);
        } catch (XEncoderException $exception) {
            // noop - preserve legacy flow
            return false;
        }
    }

    /**
     * Zwraca defaultowy SELECTOR (nie alfabet, chociaz nazwa na to wskazuje) dla danego crypto
     * @return string
     *
     * @deprecated use XLink::getDefaultSelector() with XLink::getAlphabet(), but you shouldn't need Alphabet directly in userland
     */
    public static function getDefaultAlphabet()
    {
        return static::getXLink()->getDefaultSelector();
    }

    /**
     * Zwraca alfabet do rozkodowania
     *
     * @param string pSelector
     *
     * @deprecated use XLink::getAlphabet(), but you shouldn't need Alphabet directly in userland
     */
    public static function getAlphabet($selector)
    {
        if (!is_string($selector)) {
            return null;
        }

        try {
            return static::getXLink()->getAlphabet($selector)->getAlphabet();
        } catch (XEncoderException $exception) {
            // noop - keep old interface compatibility
            return null;
        }
    }


    /**
     * Zakodowuje sekwencje uzywajac konkretnego dicta
     * @param string $alphabet
     * @param int $dataToEncode
     * @return boolean|string
     * @deprecated use XLink::rawEncode()
     */
    public static function encodeXvalueWithDict($alphabet, $dataToEncode)
    {
        if (!is_numeric($dataToEncode)) {
            return false;
        }

        try {
            return XLink::rawEncode((int)$dataToEncode, new Alphabet($alphabet, 'raw'));
        } catch (XEncoderException $exception) {
            // noop - preserve legacy flow
            return false;
        }
    }

    /**
     * Rozkodowuje sekwencje z podanym alphabetem
     *
     * @param string pDict
     * @param string pData
     * @return integer
     * @deprecated use XLink::rawDecode()
     */
    public static function decodeXvalueWithDict($alphabet, $dataToDecode)
    {
        if (null === $alphabet || is_array($dataToDecode)) {
            return null;
        }
        try {
            return XLink::rawDecode($dataToDecode, new Alphabet($alphabet, 'raw'));
        } catch (XEncoderException | TypeError $exception) {
            // noop - preserve legacy flow
            return 0;
        }
    }

    /**
     * Walidacja linka po sumie kontrolnej
     *
     * @return bool jesli link jest ok - true, jesli nie - false
     *
     * @deprecated Use \Getresponse\App\Infrastructure\XLinkValidator for 1:1 replacement or XLink::validateChecksum()
     */
    public static function validateLink($dataToValidate)
    {
        try {
            $result = static::getXLink()->validateChecksum($dataToValidate);
        } catch(\Exception $exception) {
            // noop, preserve legacy flow
            self::logIncorrectXLink();
            return false;
        }

        if (!$result) {
            self::logIncorrectXLink();
            return false;
        }

        return true;
    }

    /**
     *
     */
    private static function logIncorrectXLink(): void
    {
        $serverRequestProxy = ServerRequestProxyFactory::fromGlobals();

        try {
            LoggerFactory::getLogger('incorrect_xlink_logger')->info(
                JsonSerializer::serialize(
                    [
                        'uuid' => Uuid::createRandomUuid()->getUuid(),
                        'host' => $serverRequestProxy->getServerParam('HTTP_HOST', ''),
                        'url' => $serverRequestProxy->getServerParam('SCRIPT_URL', ''),
                        'params' => $serverRequestProxy->getServerParam('QUERY_STRING', ''),
                        'ip' => IP::fromRequest()->getIpAddress(),
                        'browser' => $serverRequestProxy->getServerParam('HTTP_USER_AGENT', ''),
                    ]
                )
            );
        } catch (LoggerException | JsonSerializerException $e) {
            // error shouldn't stop script flow
        }
    }

    /**
     * Oblicza sume kontrolna parametrow XLink`a
     * Dane w wejsciowej tablicy musza byc w postaci liczb dziesietnych (rozkodowane przez algorytm XLinka)
     * Wynik jest zwracany w postaci liczby dziesietnej.
     *
     * @param array
     * @return int
     *
     * @deprecated use XLink->calculateChecksum()
     */
    public static function countXLinkCheckSum($selector, $dataToValidate)
    {
        !is_array($dataToValidate) ? trigger_error(__METHOD__ . ', $pValues must be an array!', E_USER_ERROR) : null;
        $dataToValidate = array_filter($dataToValidate, 'is_numeric');

        empty($dataToValidate) ? trigger_error(__METHOD__ . ', wrong input data', E_USER_ERROR) : null;

        return static::getXLink()->calculateChecksum($dataToValidate, $selector);
    }

    /**
     * Rozkodowuje sekwencje
     *
     * @param string pSelector
     * @param string pData
     * @return integer
     *
     * @deprecated use XLink::decode()
     */
    public static function decodeXvalue($pSelector, $pData)
    {
        // preserve legacy flow
        if (is_null($pSelector) || is_array($pData)) {
            return null;
        }

        try {
            return static::getXLink()->decode($pData, $pSelector);
        } catch (XEncoderException $exception) {
            // noop - preserve legacy flow
            return 0;
        }
    }

    /**
     * Dodatkowo zabezpieczona suma kontrolna
     * @param string $pSelector
     * @param array $pValues
     * @return string
     *
     * @deprecated method will be removed, check usage if it is still relevant part of the application
     */
    public static function countXLinkSafeCheckSum($pSelector, $pValues)
    {
        $alphabet = static::getXLink()->getAlphabet($pSelector);

        if (is_null($alphabet)) {
            $xfCommon = new xfCommon();
            $xfCommon->redirect(XF_ERROR_404_URL);
        }
        !is_array($pValues) ? trigger_error(__METHOD__ . ', $pValues must be an array!', E_USER_ERROR) : null;

        $pValues = array_filter($pValues, 'is_numeric');
        empty($pValues) ? trigger_error(__METHOD__ . ', wrong input data', E_USER_ERROR) : null;

        return substr(md5(array_sum($pValues) . $alphabet->getLength()), 7, 4);
    }

    /**
     * Generujemy JSON'a z wynikami. Wyniki są wyrzucane na ekran, przechwytuje ajax.
     *
     * @param mixed $pError - kod bledu, najlepiej numeryczny bo jakos go obsluzyc ze strony js - klucze, nie mozna przekazywac komunikatow
     * @param array|string|null $pResult - tablica z wynikami dla JSON
     * @param string $pCallbackPrefix
     * @param bool $pDie - jezeli po echo ma zatrzymywac skrypt
     */
    public static function generateJSONResult($pError, $pResult = null, $pCallbackPrefix = null, $pDie = true)
    {
        $table = (!empty($pResult) || is_numeric($pResult)) ? $pResult : "";
        $error = (!empty($pError)) ? $pError : "";
        // generujemy wyniki dla jsona
        $data = ["error" => $error, "table" => $table];

        if (empty($pCallbackPrefix)) {
            $output = json_encode($data);
        } else {
            $output = $pCallbackPrefix . '(' . json_encode($data) . ')';
        }

        header('Content-Type: application/json');
        echo $output;

        if (true === $pDie) {
            die;
        }
    }

    /**
     * Export do pliku CSV
     */
    public static function exportToCSV($pData, $pFileName)
    {
        header("Pragma: public");
        header('Content-type: application/octet-stream; charset="utf-8"');
        header('Content-Disposition: attachment; filename=' . $pFileName . '.csv');
        header('Expires: 0');

        echo $pData;

        die;
    }

    /**
     * Eksportuje dane w postaci pliku CSV lub XML
     *
     * @param array|object $elements Tablica danych do wyeksportowania | lub result set
     * @param string $type Typ eksportu (xml/csv)
     * @param string $fileName Nazwa eksportowanego pliku
     * @param string $fieldsTerminatedBy separator pol
     * @param string $fieldsEnclosedBy
     * @param array $columnsNotToExport
     */
    public static function exportData($elements, $type, $fileName, $fieldsTerminatedBy = ',', $fieldsEnclosedBy = '"', $columnsNotToExport = [])
    {
        switch (strtolower($type)) {
            case 'csv':
                header("Pragma: public");
                header('Content-type: application/octet-stream; charset="utf-8"');
                header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
                header('Expires: 0');

                // aby cudzyslowy i apostrofy nie psuly formatu exportowanego pliku (przy ustawieniach domyslnych)
                $quotations = ["'", '"'];
                $escapedQuots = ['&#39;', '&#34;'];

                foreach ($elements as $date => $statData) {
                    $arrayElement = [];
                    foreach ($statData as $key => $element) {
                        if (in_array($key, $columnsNotToExport)) {
                            continue;
                        }
                        $element = str_replace($quotations, $escapedQuots, $element);
                        $arrayElement[] = $fieldsEnclosedBy . $element . $fieldsEnclosedBy;
                    }

                    echo implode($fieldsTerminatedBy, $arrayElement) . "\r\n";

                    unset($arrayElement);
                }
                die;

            case 'csv_simple_parse':
                header("Pragma: public");
                header('Content-type: application/octet-stream; charset="utf-8"');
                header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
                header('Expires: 0');

                for ($k = 0; $k < ArrayUtils::count($elements); $k++) {
                    echo implode($fieldsTerminatedBy, $elements[$k]) . "\r\n";
                }
                die;

            case 'xml':
                header("Pragma: public");
                header('Content-type: application/xml; charset="utf-8"', true);
                header('Content-Disposition: attachment; filename="' . $fileName . '.xml"');
                header('Expires: 0');

                echo '<?xml version="1.0" encoding="UTF-8"?>';
                echo '<export name="' . $fileName . '">';
                echo '<elements>';
                foreach ($elements as $date => $statData) {
                    if ('headers' != $date) {
                        echo '<element>';
                        foreach ($statData as $keyElement => $element) {
                            if (in_array($keyElement, $columnsNotToExport)) {
                                continue;
                            }
                            echo '<item name="' . $keyElement . '">' . htmlspecialchars($element) . '</item>';
                        }
                        echo '</element>';
                    }
                }
                echo '</elements></export>';
                die;

            case 'survey':

                header("Pragma: public");
                header('Content-type: application/xml; charset="utf-8"', true);
                header('Content-Disposition: attachment; filename="' . $fileName . '.xml"');
                header('Expires: 0');

                echo '<?xml version="1.0" encoding="UTF-8"?>';
                echo '<export name="' . htmlspecialchars($fileName) . '">';
                foreach ($elements as $id => $answers) {
                    echo '<responder id="' . $id . '"';
                    $responder_info = false;
                    foreach ($answers as $index => $question) {
                        if (!$responder_info) {
                            echo ' responder_type="' . (empty($question['id']) ? 'anonymous' : 'subscriber') . '"';
                            echo ' email="' . htmlspecialchars($question['email']) . '"';
                            echo ' country="' . htmlspecialchars($question['country']) . '"';
                            echo ' city="' . htmlspecialchars($question['city']) . '"';
                            echo ' ip="' . htmlspecialchars($question['ip']) . '"';
                            echo ' started_on="' . htmlspecialchars($question['created_on']) . '"';
                            echo ' finished="' . htmlspecialchars($question['finished']) . '"';
                            echo ' finished_on="' . htmlspecialchars($question['finished_on']) . '">';
                            $responder_info = true;
                            continue;
                        }
                        echo '<question value="' . htmlspecialchars($question['value']) . '" type="' . htmlspecialchars($question['type']) . '">';
                        echo '<answers>';
                        foreach ($question['answers'] as $key => $answer) {
                            echo '<answer value="' . htmlspecialchars($answer) . '"/>';
                        }
                        echo '</answers>';
                        echo '</question>';
                    }
                    echo '</responder>';
                }
                echo '</export>';
                die;

            case 'csv_from_resultset':
                if (!is_object($elements)) {
                    die('Empty dataset');
                }

                header("Pragma: public");
                header('Content-type: application/octet-stream; charset="utf-8"');
                header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
                header('Expires: 0');

                $xfDb = xfDb::getInstance();
                $xf = xfApplication::getInstance();
                $translate = new Translate();

                $translatedReasons = [];
                while ($result = $xfDb->fetchNextRow($elements)) {
                    $result['reason'] = $xf->xfCommon->stripStrangeChars($result['reason']);
                    if (!array_key_exists($result['reason'], $translatedReasons)) {
                        $translatedReasons[$result['reason']] = $translate->getValueForLangAndTranslationKey(
                            'ModContactShowRemovalsListReasons' . $result['reason'],
                            LegacyPanelTranslationSpace::REMOVALS_LIST_SPACE_PAGE_ID,
                            Language::fromCode($_SESSION['LANGUAGES']['CURRENT_LANGUAGE'])->getLanguageCode()
                        );
                    }
                    $result['reason'] = $translatedReasons[$result['reason']];

                    $arrayElement = [];
                    foreach ($result as $key => $element) {
                        if (in_array($key, $columnsNotToExport)) {
                            continue;
                        }
                        // aby cudzyslowy i apostrofy nie psuly formatu exportowanego pliku (przy ustawieniach domyslnych)
                        $element = str_replace($fieldsEnclosedBy, $fieldsEnclosedBy.$fieldsEnclosedBy, $element);
                        $arrayElement[] = $fieldsEnclosedBy . $element . $fieldsEnclosedBy;
                    }

                    echo implode($fieldsTerminatedBy, $arrayElement) . "\r\n";
                }
                die;
        }
    }

    /**
     * Sprawdza, czy nazwa kampanii jest prawidłowa
     *
     * @param string campaign_name Nazwa do sprawdzenia
     * @param boolean new True, jeśli ma to być nazwa nwej kampanii
     * @return bool
     */
    public static function checkCampaignName($campaign_name, $new = false)
    {
        if (true == $new) {
            // nowe kampanie nie mogą zawierac w nazwie "-"
            $pattern = "/^\w{3,128}$/";
        } else {
            // stare kampanie moga zawierac w nazwie "-"
            $pattern = "/^[-_a-z0-9]{3,128}$/";
        }

        if (true == preg_match($pattern, $campaign_name)) {
            return true;
        }

        return false;
    }

    /**
     * Sprawdza, czy podana nazwa customa jest prawidłowa
     *
     * @param string custom
     * @return boolean
     */
    public static function checkCustomName($custom_name)
    {
        $pattern = "/^[_0-9a-zA-Z]{2,32}$/";

        if (true == preg_match($pattern, $custom_name)) {
            return true;
        }

        return false;
    }

    /**
     * Konwersja stringa do UTF-8 przy uzyciu mb_convert_encoding z pakietu Multibyte String Functions
     *
     * @param string pString string do konwersji
     * @return string
     */
    public static function convertToUTF8($pString)
    {
        if (!is_array($pString)) {
            $encoding = @mb_detect_encoding($pString);

            // encoding = auto jesli nie ma na liscie dostepnych kodowan
            if (false == in_array($encoding, mb_list_encodings())) {
                $encoding = 'ASCII';
            }

            // konwersja
            if ($encoding != 'UTF-8') {
                $pString = @mb_convert_encoding($pString, 'UTF-8', $encoding);
            }
        }

        // return
        return $pString;
    }

    /**
     * Metoda okreslajaca po nazwie branda prefix do bazy
     * @param $pBrand string
     * @return string
     */
    public static function getPrefix($pBrand)
    {
        $prefix = false;

        if ('core' == $pBrand) {
            $prefix = DB_NAME_GETRESPONSE;
        } else if ('support' == $pBrand) {
            $prefix = DB_NAME_SUPPORT;
        } else if ('api' == $pBrand) {
            $prefix = XF_DB_NAME;
        } else if ('xf' == $pBrand) {
            $prefix = XF_DB_NAME;
        } else if ('mx' == $pBrand) {
            $prefix = XF_DB_NAME;
        } else if ('secure-getresponse' == $pBrand || 'marketing' == $pBrand) {
            $prefix = XF_DB_NAME;
        }

        (false == $prefix) ? trigger_error(__METHOD__ . ', Prefix error - not match! Given:' . $pBrand, E_USER_ERROR) : '';

        return $prefix;
    }

    /**
     * Metoda kodujaca arraye z danymi do stringa, ktorego mozna wysylac w urlu
     * @param $array array
     * @return string
     */
    public static function encodeUrlParams($array)
    {
        return strtr(base64_encode(addslashes(gzcompress(serialize($array), 9))), '+/=', '-_,');
    }

    /**
     * Metoda dekodujaca stringa z urla do arraya z danymi
     * @param $string string
     * @return array
     */
    public static function decodeUrlParams($string)
    {
        return unserialize(gzuncompress(stripslashes(base64_decode(strtr($string, '-_,', '+/=')))));
    }

    /**
     * Dodaje do date godzine 00:00:00
     *
     * @param string $pDatetime
     * @param string $pDateFromat
     * @return string
     */
    public static function ParseStartDatetime($pDatetime, $pDateFromat = 'Y-m-d 00:00:00')
    {
        if (!empty($pDatetime)) {
            $datetime = new DateTime($pDatetime);
            $pDatetime = $datetime->format($pDateFromat);
        }
        return $pDatetime;
    }

    /**
     * Dodaje do date godzine 23:59:59
     *
     * @param string $pDatetime
     * @param string $pDateFromat
     * @return string
     */
    public static function ParseEndDatetime($pDatetime, $pDateFromat = 'Y-m-d 23:59:59')
    {
        if (!empty($pDatetime)) {
            $datetime = new DateTime($pDatetime);
            $pDatetime = $datetime->format($pDateFromat);
        }
        return $pDatetime;
    }

    /**
     * Pobiera aktualny czas z bazy lub z PHP w zaleznosci od XF_REQUIRE_DB
     *
     * @return string
     */
    public static function getNowTimestamp()
    {
        if (false == XF_REQUIRE_DB) {
            return time();
        } else {
            $xfDbInstance = MysqlSharder::getInstance()->chooseShardDb();

            $sql = "
                SELECT NOW();
            ";

            $date = $xfDbInstance->fetchOneData($sql);

            return strtotime($date);
        }
    }

    /**
     *  Usuwa subdomene z podanej domeny
     *
     * @param string $pDomain
     * @param array $pSubdomainArray tablica subdomen. Wszystkie musza sie konczyc kropka.
     * @return mixed
     */
    public static function clearSubdomain($pDomain, $pSubdomainArray = null)
    {
        if (!is_array($pSubdomainArray) || empty($pSubdomainArray)) {
            $envTokenId = getenv('ENV_TOKEN_ID');
            $pSubdomainArray = [
                $envTokenId . 'www.',
                $envTokenId . 'secure.',
                $envTokenId . 'app.',
            ];
        } else {
            foreach ($pSubdomainArray as $prefix) {
                if (!preg_match('/\.$/', $prefix)) {
                    trigger_error(__METHOD__ . ', subdomain must end with dot! Given:' . $prefix, E_USER_ERROR);
                }
            }
        }
        $pattern = '/^(' . implode('|', array_map('preg_quote', $pSubdomainArray)) . ')/';
        return preg_replace($pattern, '', $pDomain);
    }

    /**
     *  Replace date z current timestamp
     *
     * @param string $pFormat
     * @param integer $pUnixTime
     * @return string
     */
    public static function getCurrentDate($pFormat = 'Y-m-d H:i:s', $pUnixTime = null)
    {
        return date($pFormat, (!is_null($pUnixTime) ? $pUnixTime : CURRENT_TIMESTAMP));
    }

    /**
     * Formatowanie czasu wg sesji uzytkownika
     *
     * @param string $pTime
     * @param string $pFormat
     * @return string
     */
    public static function formatTime($pTime, $pFormat)
    {
        if (!is_numeric($pTime)) {
            $pTime = strtotime($pTime);
        }

        if (CommonExtended::is12HourTime()) {
            $pFormat = str_replace(['G', 'H'], ['g', 'h'], $pFormat);
            return date($pFormat . ' A', $pTime);
        } else {
            $pFormat = str_replace(['g', 'h'], ['G', 'H'], $pFormat);
            return date($pFormat, $pTime);
        }
    }

    /**
     * Zwraca informacje czy czas ma wyc wyswietlany w formacie 12h
     *
     * @param string|null $pCountryCode
     * @return bool
     */
    public static function is12HourTime($pCountryCode = null)
    {
        if (is_null($pCountryCode)) {
            if (isset($_SESSION['user']['data']['time_format']) && '12h' == $_SESSION['user']['data']['time_format']) {
                return true;
            } else if (isset($_SESSION['user']['data']['time_format']) and '24h' == $_SESSION['user']['data']['time_format']) {
                return false;
            } else {
                if (!empty($_SESSION['user']['data']['country_code'])) {
                    $pCountryCode = $_SESSION['user']['data']['country_code'];
                } else {
                    $pCountryCode = 'us';
                }
            }
        }

        $time_zone = new TimeZone();
        $format = $time_zone->getTimeFormatByCountryCode($pCountryCode);

        if ('12h' == $format) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sprawdza czy wyswietlic stary widok stron uzytkownika
     * @param $pCampaignId
     * @return boolean
     */
    public static function hasOldPersonalPageView($pCampaignId = null)
    {
        if (!CommonExtended::isNewFeatureSwitchShown(null, $pCampaignId)) {
            return false;
        }

        $settings = [
            'campaign_logo',
            'campaign_logo_url',
            'campaign_description',
            'campaign_title'
        ];

        $old_settings = [
            'message_rss_title',
            'message_rss_description',
            'message_rss_logo_url',
            'subscriber_confirm_logo_url',
            'subscriber_confirm_website_description',
            'subscriber_removal_logo_url',
            'subscriber_removal_website_description'
        ];

        $CampaignInstance = new Campaign();

        if (!empty($pCampaignId)) {
            $CampaignInstance->CampaignId = $pCampaignId;
        } else {
            $CampaignInstance->CampaignId = $_SESSION['campaign']['id'];
        }

        foreach ($settings as $set_name) {
            $data = $CampaignInstance->getPreference($set_name);
            if (!empty($data[0]['value'])) {
                return false;
            }
        }

        // jesli nie ustawione aktualne settingsy, a sa ustawione stare, to true
        foreach ($old_settings as $set_name) {
            $data = $CampaignInstance->getPreference($set_name);
            if (!empty($data[0]['value'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sprawdza czy switch ficzera powinien byc pokazany
     * @param type $pUserId
     * @return boolean
     */
    public static function isNewFeatureSwitchShown($pUserId = null, $pCampaignsId = null)
    {
        $xfDbInstance = MysqlSharder::getInstance()->chooseShardDb();
        if (isset($pUserId) && is_numeric($pUserId)) {
            $SQLpart = "WHERE ud.users_id = " . $xfDbInstance->escape($pUserId) . " AND ";
        } else {
            $SQLpart = "JOIN campaigns c ON ud.users_id = c.users_id AND c.id = " . $xfDbInstance->escape($pCampaignsId) . " WHERE ";
        }

        $sql = "
            SELECT
                ud.users_id
            FROM
                " . DB_NAME_GETRESPONSE . ".user_details AS ud
            " . $SQLpart . "
                ud.created_on <= CONVERT_TZ( '2012-03-30 07:24:00', @@global.time_zone, @@session.time_zone )
            ";
        $users_id = $xfDbInstance->fetchOneData($sql);

        if (is_numeric($users_id)) {
            return true;
        }
        return false;
    }

    /**
     * Dodajemy / zmieniamy flage z featurem
     * @param string $pBetaEventsFlagName - nazwa flagi
     * @param string $pFlagOnOff - on/off
     * @param int $pUserId - id usera
     * @return boolean
     */
    public static function setNewFeatureEvent($pBetaEventsFlagName, $pFlagOnOff = 'on', $pUserId = null)
    {
        if (isset($pUserId) && is_numeric($pUserId) && !empty($pBetaEventsFlagName) && in_array($pFlagOnOff, ['on', 'off'])) {
            $xfDbInstance = MysqlSharder::getInstance()->chooseShardDb();

            $sql = "
                SELECT
                    `id`
                FROM
                    " . DB_NAME_GETRESPONSE . ".`user_beta_tester_events`
                WHERE
                    `users_id` = " . $xfDbInstance->escape($pUserId) . "
                AND
                    `event` LIKE " . $xfDbInstance->escape($pBetaEventsFlagName . '%') . "
                ";
            $eventId = $xfDbInstance->fetchOneData($sql);

            if (empty($eventId)) {
                $sql = "
                    INSERT INTO " . DB_NAME_GETRESPONSE . ".`user_beta_tester_events`
                    (
                        `users_id`,
                        `event`
                    )
                    VALUES
                    (
                        " . $xfDbInstance->escape($pUserId) . ",
                        " . $xfDbInstance->escape($pBetaEventsFlagName . '_' . $pFlagOnOff) . "
                    )";
            } else {
                $sql = "
                    UPDATE
                        " . DB_NAME_GETRESPONSE . ".`user_beta_tester_events`
                    SET
                        `event` = " . $xfDbInstance->escape($pBetaEventsFlagName . '_' . $pFlagOnOff) . "
                    WHERE
                        `id` = " . $xfDbInstance->escape($eventId) . "
                    ";
            }

            $event = $xfDbInstance->fetchOneData($sql);
            return true;
        }
        return false;
    }

    /**
     * Zwraca adres url wiadomosci w webarchive z odpowiednim parametrem wskazującym serwis
     *
     * @param int $pMessageData
     * @param string $pSocialPostfix Postfix dla portali social 'facebook','twitter','google_plus',null
     * @param integer|false $pUserId
     * @return string|false
     */
    public static function GetGrSnipUrlForMessage($pMessageData, $pSocialPostfix = null, $pUserId = null)
    {
        $query = [];

        $socialToQueryMapping = [
            'facebook' => 'f',
            'twitter' => 't',
            'google_plus' => 'g',
            'linkedin' => 'l',
            'pinterest' => 'p',
            'email' => 'e',
        ];

        if (array_key_exists($pSocialPostfix, $socialToQueryMapping)) {
            $query[$socialToQueryMapping[$pSocialPostfix]] = '';
        }

        if (is_array($pMessageData) && !empty($pMessageData['campaign_name']) && !empty($pMessageData['message_subject']) && !empty($pMessageData['id'])) {
            // budowanie urla dokladnie w ten sam sposob co w grsnipie, poniewaz skracacze zawsze są demaskowane i staty liczone są dla docelowej stronki
            $xfCommonInstance = new xfCommon();
            $messageSubject = $xfCommonInstance->stripStrangeChars(xfCommon::validateString($pMessageData['message_subject']));

            $redirect_url = XF_BRAND_URL . 'archive/' . $pMessageData['campaign_name'] . '/' . $messageSubject . '-' . $pMessageData['id'] . '.html';

            if (isset($_GET['s']) && !isset($query['s'])) {
                $query['s'] = $_GET['s'];
            }
        } else {
            if (is_numeric($pMessageData)) // zachowujemy opcje budowania linka GRSnipowego np. dla twittera
            {
                $message_id = $pMessageData;
                $subscriber_id = 0; //Id subscribera potrzebujemy zawsze, zeby prawidlowo budowac linka
            } elseif (is_array($pMessageData) && !empty($pMessageData['id'])) {
                $message_id = $pMessageData['id'];
                $subscriber_id = empty($pMessageData['subscrtiber_id']) ? 0 : $pMessageData['subscrtiber_id'];
            }

            if (empty($message_id)) {
                return false;
            }

            $selector = 'grsnip';
            $host = (in_array(XF_BRAND_NAME, ['core', 'marketing'])) ? GR_SNIP_HOST : XF_BRAND_URL;

            $encodedMessageId = static::getXLink()->encode($message_id, $selector);
            $encodedSubscriberId = static::getXLink()->encode($subscriber_id, $selector);

            $redirect_url = sprintf('%s/r/%s/%s', rtrim($host, '/'), $encodedMessageId, $encodedSubscriberId);

            //Dla linkow skroconych trzeba dodac dodatkowy 3 parametr z zakodowanym ID uzytkownika
            if (!is_null($pUserId)) {
                $redirect_url .= '/' . static::getXLink()->encode($pUserId, $selector);
            }
        }

        $redirectUrlSuffix = !empty($query) ? '?' . http_build_query($query, null, '&') : '';
        return $redirect_url . $redirectUrlSuffix;
    }

    /**
     * Sprawdza poprawnosc stringa UTF
     * @param string $pString
     * @return boolean
     */
    public static function isProperUTF($pString)
    {
        return Validator::isProperUTF($pString);
    }

    /**
     * Skanuj stringa w poszukiwaniu URL i/lub tagow DC, czyli "[[...]]" lub "{{...}}"
     *
     * @param string $pStringToCheck - string do sprawdzenia
     * @param bool $pCheckDomain - czy szukac URL
     * @param bool $pCheckDC - czy szukac tagow DC
     *
     * @return bool
     */
    public static function containsUrlOrDC($pStringToCheck = '', $pCheckDomain = true, $pCheckDC = true)
    {
        // usuwamy tagi i encje
        $strippedAndEncodedString = html_entity_decode(strip_tags((string)$pStringToCheck));

        if (empty($strippedAndEncodedString)) {
            return false;
        }

        $regExp = '/^';

        if ($pCheckDomain) {
            // niewystepowanie protokolow w stringu
            $regExp .= "(?!.*(http|ftp|ssh)s?\:\/\/)";

            // pozwalamy na domeny ale bez URI
            $regExp .= "(?!.*(\w+?\.\w+\/\w+))";
        }

        if ($pCheckDC) {
            // niewystepowanie tagow DC z jakimkolwiek contentem
            $regExp .= "(?!.*(\[\[.+?\]\]))(?!.*(\{\{.+?\}\}))";
        }

        // nie rozrozniamy wielkosci liter - case insensitive
        $regExp .= '.*$/si';

        return !preg_match($regExp, $strippedAndEncodedString);
    }

    /**
     * Sprawdzamy i konvertujemy date na odpowiedni format
     */
    public static function validateDate($pDate)
    {
        return Validator::validateDate($pDate);
    }

    /**
     * @param string $number
     * @param string $countryCode
     * @return string
     */
    public static function validateMobilePhoneNumber($number, $countryCode = 'US'): string
    {
        return Validator::validateMobilePhoneNumber($number, $countryCode);
    }

    /**
     * Wyslanie na output pustego gifa
     */
    public static function ShowEmptyGif()
    {
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache max-age=0');
        echo base64_decode('R0lGODlhAQABAID/AP///wAAACH5BAEAAAAALAAAAAABAAEAQAICRAEAOw==');
        die;
    }

    /**
     * Sprawdza czy user ma feature
     *
     * @param string $pFeatureName
     * @param int $pUsersId
     * @return boolean
     */
    public static function hasFeature($pFeatureName, $pUsersId)
    {
        if (self::checkIfInputIsInInCorrentType($pFeatureName, $pUsersId)) {
            return false;
        }

        $FeaturesLimitationInstance = new FeaturesLimitation();
        $FeaturesLimitationInstance->UserId = $pUsersId;
        return $FeaturesLimitationInstance->checkUserAccessToFeature($pFeatureName);
    }

    /**
     * Zwraca zakodowane users_id
     * @param string $selector
     * @return string
     *
     * @deprecated use XLink::encode()
     */
    public static function encodeXUserIdFromSession($selector = null)
    {
        $selector = $selector ?? static::getXLink()->getDefaultSelector();
        return (isset($_SESSION['user']['data']['users_id'])) ? static::getXLink()->encode($_SESSION['user']['data']['users_id'], $selector) : '';
    }

    /**
     * Pobieramy defaultowy selector alfabetu
     *
     * @param string pSelector
     * @return string
     *
     * @deprecated use XLink::getDefaultSelector()
     */
    public static function getDefaultAlphabetSelector()
    {
        return static::getXLink()->getDefaultSelector() ?? null;
    }

    /**
     * Dodajemy do tablicy informacje czy domena jest blokowana
     * @param array $pEmails - tablica z emailami
     * @param string $pArrayField - pole w tablicy, w ktorym znajduje sie emial
     * @return array
     */
    public static function addISPDomainBlockedFlagToEmail(array $pEmails, $pArrayField = 'email')
    {
        foreach ($pEmails as &$email) {
            if (!empty($email[$pArrayField]) && true === self::isISPDomainBlocked($email[$pArrayField])) {
                $email['ISPDomainBlocked'] = 'yes';
            }
        }
        return $pEmails;
    }

    /**
     * Sprawdzamy czy emial jest z yahoo
     * @param string $pEmail
     * @return boolean
     */
    public static function isISPDomainBlocked($pEmail)
    {
        $domainsList = [
            '@yahoo.',
            '@aol.',
        ];
        foreach ($domainsList as $domain) {
            if (false !== stripos($pEmail, $domain)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sprawdzamy czy polaczenie przychodzi z sieci Tor
     * @param string $pServerIp - ip serwera docelowego
     * @param int $pPort - port
     * @param string $pClientIp - ip clienta
     * @return bool | null
     */
    public static function IsTorRequest($pServerIp = null, $pPort = null, $pClientIp = null)
    {
        if (is_null($pServerIp)) {
            $pServerIp = gethostbyname($_SERVER['HTTP_HOST']);
        }

        if (is_null($pPort)) {
            $pPort = (defined('XF_HTTPS') && true == XF_HTTPS) ? 443 : 80;
        }

        if (is_null($pClientIp)) {
            $pClientIp = !empty($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
        }

        $reverseClientIp = implode('.', array_reverse(explode('.', $pClientIp)));
        $reverseServerIp = implode('.', array_reverse(explode('.', $pServerIp)));
        $hostname = $reverseClientIp . '.' . $pPort . '.' . $reverseServerIp . '.ip-port.exitlist.torproject.org';
        return (gethostbyname($hostname) == '127.0.0.2' ? true : false);

    }

    /**
     * Zwracamy adres url do obrazkow uzytkownika
     * @param int $pUsersId
     * @param string $clientMultimediaUrl
     * @return string|null
     */
    public static function getUserPhotoUrl($pUsersId, string $clientMultimediaUrl): ?string
    {
        if (is_numeric($pUsersId)) {
            return $clientMultimediaUrl . $pUsersId % 1000 . '/' . $pUsersId . '/photos/';
        }
        return null;
    }

    /**
     * zwraca czystego urla (bez zmiennych w GET, #, |)
     * @param string $pUrl
     * @return string
     */
    public static function cleanUrl($pUrl)
    {
        $urlArray = parse_url($pUrl);
        if (empty($pUrl) || empty($urlArray['scheme']) || empty($urlArray['host']) || empty($urlArray['path'])) {
            return '';
        }

        $path = (false !== strpos($urlArray['path'], '|')) ? substr($urlArray['path'], 0, strpos($urlArray['path'], '|')) : $urlArray['path'];
        return $urlArray['scheme'] . '://' . $urlArray['host'] . $path;
    }

    /**
     * Sprawdamy czy istnieje wybrany token + zgadza sie z sessja
     * Zadniem tego jest weryfikacja czy ajaxy ida z przegladarki cx-a, jezeli nie ida prawdopodobnie ktos podkalda dane, w takim wypadku wyloguj + wyslij do nas info + redirect
     */
    public static function checkForTokenInRequest()
    {
        if (!empty($_POST) && (empty($_POST['_t']) || $_POST['_t'] != session_id())) {
            self::logCsrfError();
            xfSecurity::getInstance()->logOut();
            (new xfCommon())->redirect('index.html');
        }
    }

    /**
     * Loguje błąd nieprawidłowego csrf
     *
     * @throws InvalidLoggerConfigurationException
     * @throws \RuntimeException
     */
    private static function logCsrfError()
    {
        self::logError(
            'Incorrect token - probably CSRF attack' . print_r(['request' => $_REQUEST, 'session_id' => session_id(), 'server' => $_SERVER], true),
            'Incorrect token - probably CSRF attack',
            [
                'csrf_token',
                'security',
            ]
        );
    }

    /**
     * @param string $url
     * @return bool
     */
    public static function checkIfUrlIsFromMultimediaStudio($url)
    {
        $parsedUrl = parse_url($url);
        $url = !empty($parsedUrl['host']) ? $parsedUrl['host'] : $url;

        return strpos($url, 'multimedia.getresponse.com') !== false
            || strpos($url, 'multimedia.getresponse360.com') !== false
            || strpos($url, 'multimedia.getresponse360.pl') !== false;
    }

    private static function checkIfInputIsInInCorrentType(string $pFeatureName, int $pUsersId): bool
    {
        return !is_string($pFeatureName) || !is_numeric($pUsersId);
    }

    /**
     * Start transakcji
     *
     * @return true (jezeli udalo sie rozpoczoczac transakcje) lub exception jesli nie
     */
    public function startMysqlTransaction(): bool
    {
        $result = $this->xfDb->startTransaction();
        ApplicationEvents::turnOnEventCollection();
        return $result;
    }

    /**
     * sprawdzamy czy transakcja jest otwarta
     *
     * @return true (jezeli udalo sie rozpoczoczac transakcje) lub exception jesli nie
     */
    public function isTransactionOpened(): bool
    {
        return $this->xfDb->isTransactionOpened();
    }

    /**
     * Commit transakcji
     *
     * @return true (jezeli udalo sie zakomitowac transakcje) lub exception jesli nie
     */
    public function commitMysqlTransaction(): bool
    {
        $result = $this->xfDb->commitTransaction();
        ApplicationEvents::flushPendingEvents();
        return $result;
    }

    /**
     * Rollback (cofniecie) transakcji
     *
     * @return true (jezeli udalo sie cofnac transakcje) lub exception jesli nie
     */
    public function rollbackMysqlTransaction(): bool
    {
        $result =  $this->xfDb->rollbackTransaction();
        ApplicationEvents::clearPendingEvents();
        return $result;
    }

    /**
     * Przygotowuje tablice wejsciowa do postaci ktora mozna zapisac w pliku CSV
     *
     * @param array $pData
     * @return string
     */
    public function prepareDataToExportCSV($pData)
    {
        // tablica wyjsciowa
        $lines_csv = [];

        // tablica z nazwami kolumn
        $column_names = [];

        $counter = 1;
        foreach ($pData as $data) {
            // przygotowujemy pierwsza linie z nazwami kolumn
            if (1 == $counter) {
                $column_names = array_keys($data);
                $lines_csv[] = $this->prepareOneLineToExportCSV($column_names);
            }

            $lines_csv[] = $this->prepareOneLineToExportCSV($data);
            $counter++;
        }

        return implode($this->line_delimiter, $lines_csv);
    }

    /**
     * Przygotowuje 1 linie do pliku CSV
     *
     * @param array $pData
     * @return string
     */
    private function prepareOneLineToExportCSV($pData)
    {
        $one_line = [];
        foreach ($pData as $data) {
            $one_line[] = is_array($data) ? $this->prepareOneLineToExportCSV($data) : $this->text_separator . str_replace($this->replace_text_separator['from'], $this->replace_text_separator['to'], $data) . $this->text_separator;
        }

        return implode($this->delimiter, $one_line);
    }

    /**
     * Skraca stringa do podanej dlugosci
     * Ta funkcja istnieje dlatego, ze uzycie substr() na pustym stringu zwraca false
     * podczas gdy ta funkcja zwroci wejsciowy string.
     *
     * @param string pText do obciecia
     * @param int pTextLength liczba znakow do jakiej ma skrocic
     * @return string skrocony ciag
     */
    public function subString($pText, $pTextLength)
    {
        if (mb_strlen($pText, 'utf8') <= $pTextLength) {
            return $pText;
        } else {
            return mb_substr($pText, 0, $pTextLength, 'utf8');
        }
    }

    /**
     * Sprawdzamy czy domena jest dozwolona
     * @return boolean
     */
    public function checkDomainAllowed($pDomain)
    {
        if (in_array($pDomain, $this->prohibitedDomains)) {
            return false;
        }
        return true;
    }

    /**
     * Z przekazanej tablicy tworzy stringa, ktorego mozna uzyc w zapytaniu w klauzuli IN ()
     * Dane rozne od numerycznych nie sa obecne w wynikowym stringu.
     * Przekazanie pustej tablicy powoduje zwrocenie false.
     * Przekazanie innej struktury niz tablica powoduje blad.
     *
     * @param array pIds wejsciowa tablica
     * @return string eskejpowany ciag znakow | bool false
     */
    public function intArrayToInString($pIds)
    {
        !is_array($pIds) ? trigger_error(__METHOD__ . ', $pIds must be an array!', E_USER_ERROR) : null;

        if (empty($pIds)) {
            return false;
        }

        $pIds = array_filter($pIds, 'is_numeric'); // remove non-numeric values
        $out = implode(',', array_map([$this->xfDb, 'escape'], $pIds));

        return $out;
    }

    /**
     * @param int $usersId
     * @param string $protocol
     * @return string
     */
    public function getGetresponseUrlForUser($usersId, $protocol = 'http://')
    {
        if (!is_null($usersId)) {
            $sql = "
            SELECT
                ud.`name`
            FROM
                `user_domains` ud
            JOIN
                `language_domains` ld
            ON
                ld.`domain` = ud.`name`
            WHERE
                ud.`users_id` = " . $this->xfDb->escape($usersId) . "
            ";

            $result = $this->xfDb->fetchOneData($sql);

            if (false !== $result) {
                return $protocol . $result . '/';
            }
        }

        return $this->getGetresponseUrlForLangauge($usersId, $protocol);
    }

    /**
     * Pobieranie danej domeny w zaleznosci od jezyka ustawione w sessji lub w koncie
     * Ze wzgledu na to ze jezyk moze byc okreslony w sessji uzywamy zmiennych sesyjnych w modelu
     *
     * @param int $usersId
     * @param string $protocol
     *
     * @return string domena
     */
    public function getGetresponseUrlForLangauge($usersId = null, $protocol = 'http://')
    {
        // domyslny jezyk to angielski
        $language = 'en';

        // jesli jest ustawiona sessja to pobieramy jezyk z sessji
        if (!empty($_SESSION['LANGUAGES']['CURRENT_LANGUAGE'])) {
            $language = $_SESSION['LANGUAGES']['CURRENT_LANGUAGE'];
        } // jesli nie ma w sessji a jest okreslony users id to pobieramy jezyk z konta
        else if (!is_null($usersId) && is_numeric($usersId)) {
            $xfDbInstance = MysqlSharder::getInstance()->chooseShardDb();

            $sql = "
                SELECT
                    cc.country_code
                FROM
                    " . DB_NAME_GETRESPONSE . ".user_details AS ud
                JOIN
                    " . DB_NAME_GETRESPONSE . ".country_codes AS cc
                ON
                    cc.id = ud.country_codes_id
                WHERE
                    users_id = " . $this->xfDb->escape($this->UserId);

            $data = $xfDbInstance->fetchSingleData($sql);

            if (!empty($data['country_code'])) {
                $language = $data['country_code'];
            }
        }

        // pobranie domeny dla konkretnego jezyka
        $xfCommon = new xfCommon();
        $domain = $xfCommon->getDomainNameByLanguage($language);

        // jesli w danym jezyku nie ma domeny to sprawdzamy dla jezyka angielskiego
        if (empty($domain)) {
            $language = 'en';
            $domain = $xfCommon->getDomainNameByLanguage($language);

            if (empty($domain)) {
                $container = Application::container();
                $defaultDomain = $container->get(LanguageDomainReadModel::class)->getDefaultDomainForCrypto(
                    ApplicationClientFactory::createFromConstantsForApp()->getName()
                );

                $domain = $defaultDomain->getHost();
            }

            // jesli nie pobralismy zadnego jezyka to jest to blad aplikacji!
            empty($domain) ? trigger_error('No domain for country code: ' . $language) : null;
        }

        return $protocol . $domain . '/';
    }

    /**
     * Zamiana customow, predefinow itp w wiadomosci
     *
     * @param string pContent
     * @param array pData
     * @return string
     */
    public function personalizationDisplayBroadcastArchive($pContent, $pData)
    {
        $pContent = preg_replace("/\[\[sig\]\]/i", $pData['rss_sig'], $pContent);
        $pContent = preg_replace("/\[\[myname\]\]/i", $pData['campaign'], $pContent);
        $pContent = preg_replace("/\[\[list\]\]/i", $pData['campaign'], $pContent);
        $pContent = preg_replace("/\[\[responder\]\]/i", $pData['campaign'], $pContent);
        $pContent = preg_replace("/\[\[myemail\]\]/i", $pData['email'], $pContent);
        $pContent = preg_replace("/\[\[misc\]\]/i", "", $pContent);
        $pContent = preg_replace("/\[\[firstname\]\]\][|\s+|,]\[\[name\]\]/i", "Friend", $pContent);
        $pContent = preg_replace("/\[\[firstname\]\]/i", "Friend", $pContent);
        $pContent = preg_replace("/\[\[name\]\]/i", "Friend", $pContent);
        $pContent = preg_replace("/\[\[email\]\]/i", "", $pContent);
        $pContent = preg_replace("/\[\[remove\]\]/i", "", $pContent);
        $pContent = preg_replace("/\[\[custom_\w+\]\]/i", "", $pContent);
        $pContent = preg_replace("/\[\[next_url\]\]/i", "", $pContent);
        $pContent = preg_replace("/\[\[pre sig\]\]/i", $pData['rss_sig'], $pContent);

        return $pContent;
    }

    /**
     * Algorytm walidacji numeru NIP
     * @param string $pNip
     * @return boolean
     */
    public function NIPIsValid($pNip)
    {
        return $this->validator->NIPIsValid($pNip);
    }

    /**
     * Zmiana formatu liczby ustawiana po country dla setlocale
     *
     * @param int pSetLocaleCountry - okreslenie country w formacie dla setlocale
     * @return boolean pNumber
     */
    public function setLocaleCurrency($pSetLocaleCountry = 'en')
    {
        $this->pFormatCountry = $pSetLocaleCountry;
        return true;
    }

    /**
     * Zmiana formatu liczby ustawiamy po ustawieniach z locale
     * @param int|float|string $number - okreslenie country w formacie dla setlocale
     * @param int $decimals
     * @param bool $cutZeros
     * @return mixed pNumber
     */
    public function moneyFormat($number, $decimals = 0, $cutZeros = false)
    {
        if (!is_numeric($number)) {
            return $number;
        }

        static $mLocalSeparators; //TODO SHOULD BE USED INSTANCE CACHE, NOT STATIC
        if (empty($mLocalSeparators[$this->pFormatCountry])) {
            $mLocalSeparators[$this->pFormatCountry] = $this->getLocalNumberFormat();
        }
        $decimals = (is_numeric($decimals) ? $decimals : 0);

        $decimalSeparator = $mLocalSeparators[$this->pFormatCountry]['decimal_seperator'];
        $thousandSeparator = $mLocalSeparators[$this->pFormatCountry]['thousand_seperator'];
        $formatted = number_format($number, $decimals, $decimalSeparator, $thousandSeparator);

        if (true == $cutZeros && str_pad('', $decimals, '0') == substr($formatted, -1 * $decimals)) {
            $formatted = number_format($number, 0, $decimalSeparator, $thousandSeparator);
        }

        return $formatted;
    }

    /**
     * Pobieramy z bazy formatowanie liczby
     */
    private function getLocalNumberFormat()
    {
        // pobieramy separatory
        $xfDbInstance = MysqlSharder::getInstance()->chooseShardDb();

        $sql = "
            SELECT
                thousand_seperator,
                decimal_seperator
            FROM
                " . DB_NAME_GETRESPONSE . ".country_codes
            WHERE
                country_code = " . $xfDbInstance->escape($this->pFormatCountry) . "
        ";

        $query = $xfDbInstance->startQuery($sql);
        $result = $query->execute('Separators' . $this->pFormatCountry, 28800); // 8h
        $data = $query->getSingleData();

        // jesli dla danego kraju nie ma zdefiniowanego separatora to ustawiamy domyslny
        if (empty($data)) {
            $data['decimal_seperator'] = '.';
            $data['thousand_seperator'] = ',';
        }
        return $data;
    }

    /**
     * Zmiana stringa okreslajacego liczbe wg ustawionej z locala na liczbe
     *
     * @param int $pNumber - okreslenie country w formacie dla setlocale
     * @return boolean pNumber
     */
    public function numberFormatToNumeric($pNumber, $pDecimals = 2)
    {
        $data = $this->getLocalNumberFormat();
        $pNumber = str_replace($data['thousand_seperator'], '', $pNumber);
        $pNumber = str_replace($data['decimal_seperator'], '.', $pNumber);

        return round($pNumber, $pDecimals);
    }

    /**
     * Pobieramy dane o ofertach, kodach promocyjnych itp.
     */
    public function getBillingData($action, $getParams = [])
    {
        // url requestu
        $url = GR_APPLICATION_PRODUCTION . 'json_offer_details.html?t=' . $action;
        if (!empty($getParams)) {
            $url .= '&' . http_build_query($getParams, null, '&');
        }

        $httpClient = HttpClientFactory::createDefaultClient();

        $result = null;
        try {
            $response = $httpClient->get(new Url($url));
            $result = $response->getBody()->getContents();
        } catch (\Exception $exception) {

            $error[] = $url;
            $error[] = $exception->getMessage();

            $message = 'Connection error on cURL session getBillingData - CommonExtended::getBillingData.' . implode("\n",
                    $error);

            self::logError($message, 'Connection error on cURL session session getBillingData ', [
                'common_extended',
                'curl_session',
                'connection_error_on_curl'
            ]);
        }

        if (false !== $result) {
            $result = json_decode($result, true);
            if (!empty($result['error'])) {
                return $result['error'];
            }
            if (!empty($result['table'])) {
                return $result['table'];
            }
        }
        return false;
    }

    /**
     * Pobiera nazwe kampanii marketingowej dla jezyka
     * @param string|null $language
     * @param string|null $getForAllLanguagesByType
     * @return array
     */
    public static function GetMarketingCampaigns($language, $getForAllLanguagesByType = null)
    {
        // mapowanie kampani makretingowych do jezykow
        $campaingsMap = [
            'pl' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => 'getresponse_beta',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_POLISH'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_POLISH'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => 'getresponse_blog',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => 'getresponse_newsletter',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_POLISH'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => 'freetrial_nie',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'en' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => 'getresponse_email_marketing_blog',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => 'getresponse_email_tips_prospects',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '60_days_free_trial',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => 'new_free_trial_getresponse',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => 'getresponse_pro_education_series',
            ],
            'es' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_SPANISH'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_SPANISH'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_SPANISH'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'de' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_GERMAN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_GERMAN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => 'getresponse_email_marketing_blog',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => 'getresponse_email_tips_prospects',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_GERMAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'ru' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_RUSSIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_RUSSIAN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_RUSSIAN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_RUSSIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'pt' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_PORTUGUESE'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_PORTUGUESE'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_PORTUGUESE'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_PORTUGUESE'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'br' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_PORTUGUESE'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_PORTUGUESE'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_PORTUGUESE'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_PORTUGUESE'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'ms' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'th' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'zh' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'fr' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_FRENCH'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_FRENCH'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_FRENCH'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_FRENCH'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'it' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ITALIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_ITALIAN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_ITALIAN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ITALIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'sv' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'ro' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'tr' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'nl' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'da' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'hu' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'vi' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'id' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'ja' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'no' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EUROPEAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'ko' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_ASIAN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'mx' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => Config::get('MARKETING_CAMPAIGNS_FREE_SPANISH'),
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_SPANISH'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_SPANISH'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => '',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => '',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_SPANISH'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => '',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => '',
            ],
            'ie' => [
                'SIGN_ME_TO_CAMPAIGN_FREE' => '',
                'SIGN_ME_TO_CAMPAIGN_PRO' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_CAMPAIGN_UPGRADE' => Config::get('MARKETING_CAMPAIGNS_PRO_EN'),
                'SIGN_ME_TO_BLOG_CAMPAIGN' => 'getresponse_email_marketing_blog',
                'SIGN_ME_TO_CAMPAIGN_UPDATES' => '',
                'SIGN_ME_TO_CAMPAIGN_OFFERS' => '',
                'SIGN_ME_TO_EMAIL_TIPS_PROSPECTS' => 'getresponse_email_tips_prospects',
                'SIGN_ME_TO_EMAIL_EDUCATION_CYCLE' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL' => Config::get('MARKETING_CAMPAIGNS_FREE_EN'),
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_60' => '60_days_free_trial',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_NO' => '',
                'SIGN_ME_TO_CAMPAIGN_PEEP' => '',
                'SIGN_ME_TO_CAMPAIGN_FREE_TRIAL_ABTEST' => 'new_free_trial_getresponse',
                'SIGN_ME_TO_CAMPAIGN_UPGRADE_ABTEST' => 'getresponse_pro_education_series',
            ]
        ];

        // pobierz wybrany typ dla wszystkich jezykow
        if (null !== $getForAllLanguagesByType) {
            $campaignsList = array_map(
                function ($listForLang) use ($getForAllLanguagesByType) {
                    return $listForLang[$getForAllLanguagesByType];
                },
                $campaingsMap
            );
            $campaignsList = array_filter($campaignsList);

            return $campaignsList;
        }

        $campaigns = $campaingsMap['en'];
        // istnieja kampanie dla danego jezyka nadpisz
        if (isset($campaingsMap[$language])) {
            $campaigns = $campaingsMap[$language];
        }

        return $campaigns;
    }

    /**
     * Dodawanie kontaktu do kampanii marketingowych
     *
     * @param $campaignName
     * @param $subscriberDetails
     * @param $customs
     * @param bool|true $addSubscriberInTransaction
     * @param bool|false $forcedOptinInactive
     *
     * @return bool
     * @throws AMQPException
     */
    public function addSubscriberToCampaign($campaignName, $subscriberDetails, $customs, $addSubscriberInTransaction = true, $forcedOptinInactive = false)
    {
        $currentLanguage = null;
        if (isset($_SESSION['LANGUAGES']['CURRENT_LANGUAGE'])) {
            $currentLanguage = $_SESSION['LANGUAGES']['CURRENT_LANGUAGE'];
        }

        $payload = [
            'action' => AccountService::ACTION_ADD,
            'campaignName' => $campaignName,
            'subscriberDetails' => $subscriberDetails,
            'customs' => $customs,
            'addSubscriberInTransaction' => $addSubscriberInTransaction,
            'forcedOptinInactive' => $forcedOptinInactive,
            'language' => $currentLanguage
        ];

        if ('getresponse_free_trial' === $campaignName) {
            $payload['tags'] = $this->getRandomAbTestTagValueForSubscriber();
        }

        $publisher = AMQPClientFactory::getPublisher('handle_marketing_campaign');
        $publisher->publish(new AMQPMessage(json_encode($payload)));

        return true;
    }

    /**
     * @return array
     */
    private function getRandomAbTestTagValueForSubscriber(): array
    {
        return [array_rand(array_flip(['abtest_a', 'abtest_b']))];
    }

    /**
     * Dodaj cx-a do jego defaultowej kampanii
     *
     * @param $usersId
     * @param $name
     * @param $email
     * @param $ip
     */
    public function addCxToDefaultCampaign($usersId, $name, $email, $ip)
    {
        $Campaign = new Campaign();
        $Campaign->UserId = $usersId;

        $this->addSubscriberToCampaign(
            $Campaign->getDefaultCampaignName(),
            [
                'name' => $name,
                'email' => $email,
                'intval' => null,
                'optin' => 'single_optin',
                'origin' => 'api',
                'ip' => $ip
            ],
            null,
            true,
            true
        );
    }

    /**
     * Zmiana formatu liczby per country code
     *
     * @param $pNumber
     * @param int $pDecimals
     * @param bool|false $pCutZeros
     * @return string
     */
    public function numberFormat($pNumber, $pDecimals = 0, $pCutZeros = false)
    {
        if (!is_numeric($pNumber)) {
            return $pNumber;
        }

        static $mLocalSeparators;
        if (empty($mLocalSeparators[$this->pFormatCountry])) {
            $mLocalSeparators[$this->pFormatCountry] = $this->getLocalNumberFormat();
        }
        $pDecimals = (is_numeric($pDecimals) ? $pDecimals : 0);

        $formated = number_format($pNumber, $pDecimals, $mLocalSeparators[$this->pFormatCountry]['decimal_seperator'], $mLocalSeparators[$this->pFormatCountry]['thousand_seperator']);

        // sprawdzamy czy uciac zera
        if (true == $pCutZeros && str_pad('', $pDecimals, '0') == substr($formated, -1 * $pDecimals)) {
            $formated = number_format($pNumber, 0, $mLocalSeparators[$this->pFormatCountry]['decimal_seperator'], $mLocalSeparators[$this->pFormatCountry]['thousand_seperator']);
        }

        return $formated;
    }

    /**
     * Sets a 404 not found header
     * @param null|string $reason
     */
    public static function sendNotFoundHeader($reason = null)
    {
        if (null !== $reason) {
            header('x-gr-reason:' .  $reason);
        }
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 404 Not Found');
    }
}
