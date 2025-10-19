<?php

namespace App\Service\Cvs;

class CsvReader
{
  private string $filename;
  private string $delimiter = ",";
  private bool $hasHeader = true;

  public function __construct(?string $filename)
  {
    if (isset($filename)) {
      $this->setFilename($filename);
    }
  }

  public function setFilename(string $filename): self
  {
    $this->filename = $filename;
    if (!file_exists($filename)) {
      throw new \RuntimeException("Csv filename does not exist");
    }

    return $this;
  }

  public function setHasHeader(bool $hasHeader): self
  {
    $this->hasHeader = $hasHeader;

    return $this;
  }

  public function setFieldDelimiter($delimiter): self
  {
    $this->delimiter = $delimiter;

    return $this;
  }

  public function getRows(): array
  {
    $rows = [];
    $f = fopen($this->filename, "r");
    if ($f) {
      $header = [];
      $line = 0;
      while (($buffer = fgets($f, 4096)) !== false) {
        $buffer = trim($buffer);
        $data = explode($this->delimiter, $buffer);
        if ($line == 0 && $this->hasHeader) {
          $header = $data;
        }
        if ($line > 0) {
          for ($i = 0; $i < count($data); $i++) {
            $item = trim($data[$i]);
            $item = trim($item, "'");
            $item = trim($item, '"');
            $rows[$line - 1][$header[$i]] = $item;
          }
        }
        $line++;
      }
      fclose($f);
    }

    return $rows;
  }
}
