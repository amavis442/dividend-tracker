<?php
namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PimcoService implements DividendDatePluginInterface
{
    public const SSHY_FEED = 'https://nl.pimco.com/en-NL/data/DataExport/DownloadXlsx?exportName=dividendsCapitalGains&cusip=G7110H164&columnNames=DistributionDate,DistributionNav,Dividens,DistributionFactor';
    public const STHS_FEED = 'https://nl.pimco.com/en-NL/data/DataExport/DownloadXlsx?exportName=dividendsCapitalGains&cusip=G7110H321&columnNames=DistributionDate,DistributionNav,Dividens,DistributionFactor';
    public const NO_FEED = -1;
    
    public $calendar = [
        '2021-05-20' => ['2021-05-28', '2021-05-21'],
        '2021-06-17' => ['2021-06-30', '2021-06-18'],
        '2021-07-15' => ['2021-07-30', '2021-07-16'],
        '2021-08-19' => ['2021-08-31', '2021-08-20'],
        '2021-09-16' => ['2021-09-30', '2021-09-17'],
        '2021-10-21' => ['2021-10-29', '2021-10-22'],
        '2021-11-18' => ['2021-11-30', '2021-11-19'],
        '2021-12-16' => ['2021-12-30', '2021-12-17'],
    ];

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

    public function getData(string $ticker): ?array
    {
        $url = '';
        $currency = 'USD';
        switch ($ticker) {
            case 'SSHY':
                $url = self::SSHY_FEED;
                $currency = 'USD';
                break;
            case 'STHS':
                $url = self::STHS_FEED;
                $currency = 'GB';
                break;
            default:
                $url = self::NO_FEED;
        }
        if ($url === self::NO_FEED) {
            return null;
        }

        $response = $this->client->request(
            'GET',
            $url
        );

        if ($response->getStatusCode() !== 200) {
            return null;
        }
        $filename = '/tmp/' . $ticker . '.xlsx';

        $content = $response->getContent(true);
        $items = [];
        if (file_put_contents($filename, $content)) {
            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->open($filename);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowid => $row) {
                    /**
                     * @var \Box\Spout\Common\Entity\Cell[] $cells
                     */
                    $cells = $row->getCells();
                    if ($rowid == 3) {
                        $exDate = $cells[0]->getValue()->format('Y-m-d');
                        $dividendAmount = $cells[3]->getValue();

                        if (isset($this->calendar[$exDate])) {
                            $divDates = $this->calendar[$exDate];
                            $item = [];
                            $item['DeclaredDate'] = date('Y-m-d');
                            $item['RecordDate'] = $divDates[1];
                            $item['ExDate'] = $exDate;
                            $item['PayDate'] = $divDates[0];
                            $item['DividendAmount'] = $dividendAmount;
                            $item['Type'] = 'Distribution';
                            $item['Currency'] = $currency;

                            $items[] = $item;
                        }

                        break;
                    }
                }
            }
            
            $reader->close();
            unlink($filename);
        }

        return $items;
    }
}
