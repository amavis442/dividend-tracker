<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VanguardService implements DividendDatePluginInterface
{
    public const URL = 'https://api.vanguard.com/rs/gre/gra/1.7.0/datasets/urd-product-port-specific.jsonp?vars=portId:[PORTID],issueType:F&callback=angular.callbacks._4';

    public const NO_PORTID = -1;
    public const VMID_PORTID = 9525;
    public const VJPN_PORTID = 9504;
    public const VEUR_PORTID = 9520;
    public const VAPX_PORTID = 9522;
    public const VUSA_PORTID = 9503;
    public const VGOV_PORTID = 9501;

    /**
     * Http client
     *
     * @var HttpClientInterface
     */
    protected $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getData(string $symbol): ?array
    {
        $url = '';
        $currency = 'USD';
        switch ($symbol) {
            case 'VMID':
                $portid = self::VMID_PORTID;
                $currency = 'USD';
                break;
            case 'VJPN':
                $portid = self::VJPN_PORTID;
                $currency = 'GB';
                break;
            case 'VEUR':
                $portid = self::VEUR_PORTID;
                $currency = 'EUR';
                break;
            case 'VAPX':
                $portid = self::VAPX_PORTID;
                $currency = 'USD';
                break;
            case 'VUSA':
                $portid = self::VUSA_PORTID;
                $currency = 'USD';
                break;
            case 'VGOV':
                $portid = self::VGOV_PORTID;
                $currency = 'GB';
                break;
            default:
                $portid = self::NO_PORTID;
        }
        if ($portid === self::NO_PORTID) {
            return null;
        }

        $url = str_replace('[PORTID]', (string) $portid, self::URL);
        $response = $this->client->request(
            'GET',
            $url
        );

        if ($response->getStatusCode() !== 200) {
            return null;
        }
        $content = $response->getContent(true);
        $content = str_replace('angular.callbacks._4(', '', $content);
        $content = substr($content, 0, strlen($content) - 1);

        $data = json_decode($content);
        //$currencyCode = $data->annualNAVReturns->currencyCode;
        $distributions = $data->distributionHistory->fundDistributionList;
        $mostRecent = $distributions[0];

        if ($mostRecent) {
            $dividendAmount = $mostRecent->mostRecent->value;
            $declaredDate = date('Y-m-d');
            $recordDate = $mostRecent->recordDateUnformatted;
            $exDate = date('Y-m-d', strtotime($mostRecent->exDividendDate));
            $payDate = $mostRecent->payableDateUnformatted;
            $type = $mostRecent->type;

            $item = [];
            $item['DeclaredDate'] = $declaredDate;
            $item['RecordDate'] = $recordDate;
            $item['ExDate'] = $exDate;
            $item['PayDate'] = $payDate;
            $item['DividendAmount'] = $dividendAmount;
            $item['Type'] = $type;
            $item['Currency'] = $currency; //strpos('USD', $row[0][4]['display'])

            return [$item];
        }
        return null;
    }
}
