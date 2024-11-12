<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ISharesService implements DividendDatePluginInterface
{
    public const SEMB_FEED = 'https://www.blackrock.com/nl/particuliere-beleggers/produkten/251824/ishares-jp-morgan-emerging-markets-bond-ucits-etf/1495092399598.ajax?tab=distributions&fileType=json&subtab=table';
    public const ISPA_FEED = 'https://www.ishares.com/nl/particuliere-belegger/nl/producten/251973/ishares-stoxx-global-select-dividend-100-ucits-etf-de-fund/1497735778843.ajax?tab=distributions&fileType=json&subtab=table';
    public const INRG_SEED = 'https://www.ishares.com/nl/particuliere-belegger/nl/producten/251911/ishares-global-clean-energy-ucits-etf/1497735778843.ajax?tab=distributions&fileType=json&subtab=table';
    public const IPRP_SEED = 'https://www.ishares.com/nl/particuliere-belegger/nl/producten/251808/ishares-european-property-yield-ucits-etf/1497735778843.ajax?tab=distributions&fileType=json&subtab=table';
    public const ISF_SEED = 'https://www.ishares.com/nl/particuliere-belegger/nl/producten/251795/ishares-ftse-100-ucits-etf-inc-fund/1497735778843.ajax?tab=distributions&fileType=json&subtab=table';
    public const IUS3_SEED = 'https://www.ishares.com/nl/particuliere-belegger/nl/producten/251920/ishares-sp-smallcap-600-ucits-etf/1497735778843.ajax?tab=distributions&fileType=json&subtab=table';
    public const IWDP_SEED = 'https://www.ishares.com/nl/particuliere-belegger/nl/producten/251801/ishares-developed-markets-property-yield-ucits-etf/1497735778843.ajax?tab=distributions&fileType=json&subtab=table';

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

    public function setApiKey(?string $api_key): void
    {

    }

    public function getData(string $symbol): ?array
    {
        $url = '';
        $currency = 'USD';
        switch ($symbol) {
            case 'SEMB':
                $url = self::SEMB_FEED;
                break;
            case 'ISPA':
                $url = self::ISPA_FEED;
                $currency = 'EUR';
                break;
            case 'INRG':
                $url = self::INRG_SEED;
                $currency = 'USD';
                break;
            case 'IPRP':
                $url = self::IPRP_SEED;
                $currency = 'EUR';
                break;
            case 'ISF':
                $url = self::ISF_SEED;
                $currency = 'GB';
                break;
            case 'IUS3':
                $url = self::IUS3_SEED;
                $currency = 'USD';
                break;
            case 'IWDP':
                $url = self::IWDP_SEED;
                $currency = 'USD';
                break;
        }

        $response = $this->client->request(
            'GET',
            $url
        );
        $content = $response->getContent(true);
        $content = str_replace("\xEF\xBB\xBF", '', $content);

        /*
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
         */
        $data = json_decode($content, true);

        $row = $data['table']['aaData'];



        $item = [];
        //$item['DeclaredDate'] = $row[0][0]['raw'];
        $item['RecordDate'] = $row[0][1]['raw'];
        $item['ExDate'] = $row[0][0]['raw'];
        $item['PayDate'] = $row[0][2]['raw'];
        $item['DividendAmount'] = $row[0][3]['raw'];
        $item['Type'] = 'Distribution';
        $item['Currency'] = $currency; //strpos('USD', $row[0][4]['display'])
        return [$item];
    }
}
