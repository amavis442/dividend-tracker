<?php

namespace App\Service\Trading212;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PieService
{
	public const API_URL = 'https://live.trading212.com/api/v0/equity/pies';
	protected string $apiKey;

	public function __construct(protected HttpClientInterface $client)
	{
		$this->client = $client;
	}

	public function setApiKey(?string $apiKey): void
	{
		$this->apiKey = $apiKey;
	}

    public function getPies(): ?array {
        $url = self::API_URL;

        $response = $this->client->request('GET', $url, [
			'headers' => [
                'Authorization' => $this->apiKey,
            ]
		]);

        $content = $response->getContent(true);

        $content = str_replace("\xEF\xBB\xBF", '', $content);

		$data = json_decode($content, true);

        return $data;
    }

    public function getPie(int $id): ?array {
        $url = self::API_URL. '/'.$id;

        $response = $this->client->request('GET', $url, [
			'headers' => [
                'Authorization' => $this->apiKey,
            ]
		]);

        $content = $response->getContent(true);

        $content = str_replace("\xEF\xBB\xBF", '', $content);

		$data = json_decode($content, true);

        return $data;
    }
}
