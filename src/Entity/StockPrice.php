<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *  collectionOperations={"get"},
 *  itemOperations={"get"}
 * )
 */
class StockPrice
{
    /**
     * @ApiProperty(identifier=true)
     */
    private $symbol;

    /**
     * Market price
     *
     * @var float|null
     */
    private $price;

    /**
     * Forward dividend yield based on current marketprice
     *
     * @var float|null
     */
    private $forwardNetDividendYieldPerShare;

    /**
     * Get the value of symbol
     */
    public function getSymbol()
    {
        return $this->symbol;
    }


    /**
     * Set the value of symbol
     *
     * @return  self
     */
    public function setSymbol($symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get market price
     *
     * @return  float|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * Set market price
     *
     * @param  float  $price  Market price
     *
     * @return  self
     */
    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get forward dividend yield based on current marketprice
     *
     * @return  float|null
     */ 
    public function getForwardNetDividendYieldPerShare()
    {
        return $this->forwardNetDividendYieldPerShare;
    }

    /**
     * Set forward dividend yield based on current marketprice
     *
     * @param  float|null  $forwardNetDividendYieldPerShare  Forward dividend yield based on current marketprice
     *
     * @return  self
     */ 
    public function setForwardNetDividendYieldPerShare($forwardNetDividendYieldPerShare)
    {
        $this->forwardNetDividendYieldPerShare = $forwardNetDividendYieldPerShare;

        return $this;
    }

}
