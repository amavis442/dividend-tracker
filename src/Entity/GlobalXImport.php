<?php

namespace App\Entity;

class GlobalXImport
{
    private $uploadfile;
    private Ticker $ticker;

    public function getUploadFile(): ?string
    {
        return $this->uploadfile;
    }

    public function setUploadFile(string $uploadfile): self
    {
        $this->uploadfile = $uploadfile;

        return $this;
    }

	/**
	 * Get the value of ticker
	 *
	 * @return  Ticker
	 */
	public function getTicker(): Ticker
	{
		return $this->ticker;
	}

	/**
	 * Set the value of ticker
	 *
	 * @param   Ticker  $ticker
	 *
	 * @return  self
	 */
	public function setTicker(Ticker $ticker): self
	{
		$this->ticker = $ticker;

		return $this;
	}
}
