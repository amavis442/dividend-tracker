<?php

namespace App\Service;

use DateTime;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\Contracts\DividendRetrievalInterface;

class ISharesService implements DividendRetrievalInterface
{
    public const SEMB_FEED = 'https://www.blackrock.com/nl/particuliere-beleggers/produkten/251824/ishares-jp-morgan-emerging-markets-bond-ucits-etf/1495092399598.ajax?tab=distributions&fileType=json&subtab=table';
    
    /**
     * Http client
     *
     * @var HttpClientInterface
     */
    protected $client;

    /*
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }
    */

    public function setClient(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getLatest(string $ticker): ?array
    {
        $url = '';
        switch($ticker) {
            case 'SEMB':
                $url = self::SEMB_FEED;
                break;
        }

        $response = $this->client->request(
            'GET',
            $url
        );
        $content = $response->getContent(true);
        $content = str_replace("\xEF\xBB\xBF",'',$content); 

        /*
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        */
        $data = json_decode($content, true);
        $row = $data['table']['aaData'];
        $item = [];
        $item['DeclaredDate'] = $row[0][0]['raw'];
        $item['RecordDate'] = $row[0][1]['raw'];
        $item['ExDate'] = $row[0][2]['raw'];
        $item['PayDate'] = $row[0][3]['raw'];
        $item['DividendAmount'] = $row[0][4]['raw'];
        $item['Type'] = 'Distribution';
        $item['Currency'] = 'USD'; //strpos('USD', $row[0][4]['display']) 

        return $item;
    }



}
