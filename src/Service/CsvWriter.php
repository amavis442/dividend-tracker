<?php

namespace App\Service;

class CsvWriter
{
  private string $filename;
  private string $delimiter = ",";
  private bool $hasHeader = true;
  private array $rows = [];
  private array $headers = [];

  public function __construct(?string $filename)
  {
    if (isset($filename)) {
      $this->setFilename($filename);
    }
  }

  public function setFilename(string $filename): self
  {
    $this->filename = $filename;

    return $this;
  }

  public function setFieldDelimiter($delimiter): self
  {
    $this->delimiter = $delimiter;

    return $this;
  }

  public function setHasHeader(bool $hasHeader): self
  {
    $this->hasHeader = $hasHeader;

    return $this;
  }
  function setHeaders(array $headers): self
  {
    $this->headers = $headers;
    return $this;
  }

  function addRow(array $row): self
  {
    $this->rows[] = $row;
    return $this;
  }

  public function write(): void
  {
    $fh = fopen($this->filename, "w");
    if ($fh) {
      if ($this->hasHeader) {
        $headers = "";
        foreach ($this->headers as $header) {
          $headers .= $header . $this->delimiter;
        }
        $headers = trim($headers, $this->delimiter) . "\n";
        fwrite($fh, $headers, strlen($headers));
      }

      $line = "";
      foreach ($this->rows as $row) {
        foreach ($row as $value) {
          $escape = is_string($value) ? '"' : '';
          $line .= $escape . $value . $escape . $this->delimiter;
        }
        $line = trim($line, $this->delimiter) . "\n";
        fwrite($fh, $line, strlen($line));
      }
      fclose($fh);
    }
  }
}
