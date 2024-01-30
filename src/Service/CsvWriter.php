<?php

namespace App\Service;

class CsvWriter
{
  private string $filename;
  private string $delimiter = ",";
  private bool $hasHeader = true;
  private array $rows = [];
  private array $headers = [];

  public function __construct(string $filename)
  {
    $this->filename = $filename;
  }

  public function setFieldDelimiter($delimiter)
  {
    $this->delimiter = $delimiter;
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

  public function write()
  {
    $rows = [];
    $f = fopen($this->filename, "w");
    if ($f) {
      $headers = "";
      foreach ($this->headers as $header) {
        $headers .= $header . $this->delimiter;
      }
      $headers = trim($headers, $this->delimiter) . "\n";
      fwrite($f, $headers, strlen($headers));

      $line = "";
      foreach ($this->rows as $row) {
        foreach ($row as $value) {
          $escape = is_string($value) ? '"' : '';
          $line .= $escape . $value . $escape . $this->delimiter;
        }
        $line = trim($line, $this->delimiter) . "\n";
        fwrite($f, $line, strlen($line));
      }
      fclose($f);
    }
  }
}
