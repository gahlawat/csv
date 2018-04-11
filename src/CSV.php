<?php

namespace Gahlawat\Csv;

class CSV
{
    private $path;
    private $handle;
    private $headers;
    private $rowsCount;

    public function __construct($path=null, $mode='r+', $withHeaders=true, $headers=null)
    {
        $this->path   = $path;
        $this->handle = fopen($path, $mode);

        if ($withHeaders) {
            $this->setHeaders($headers);
        }
    }

    public static function openFile($path, $zipped=false, $withHeaders=true)
    {
        if ($zipped) {
            $pathInfo = pathinfo($path);

            $zip        = new \ZipArchive;
            $fileOpened = $zip->open($path);

            if (! $fileOpened) {
                return null;
            }

            $zip->extractTo($pathInfo['dirname']);
            $zip->close();
            unlink($path);

            $path = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'];
        }

        if (! is_readable($path)) {
            return null;
        }

        return new static($path, 'r+', $withHeaders);
    }

    public static function composeFile($path, $headers=null)
    {
        return new static($path, 'w+', $withHeaders=true, $headers);
    }

    public function setData($data)
    {
        foreach ($data as $datum) {
            $this->setRow($datum);
        }

        $this->closeFile();

        return $this->path;
    }

    public function setRow($row)
    {
        return fputcsv($this->handle, $this->justifyColumns($row));
    }

    public function setHeaders($headers=null)
    {
        if ($headers) {
            $this->headers = $headers;
            fputcsv($this->handle, $this->headers);

            return $this;
        }

        $this->headers = fgetcsv($this->handle);

        return $this;
    }

    public function closeFile()
    {
        return fclose($this->handle);
    }

    public function getRow()
    {
        $row = fgetcsv($this->handle);

        if ($row == false) {
            return null;
        }

        return array_combine($this->getHeaders(), $row);
    }

    public function getData()
    {
        $data = [];
        while ($row = $this->getRow()) {
            $data[] = $row;
        }

        $this->closeFile();

        return $data;
    }

    public function each(callable $callback)
    {
        while ($row = $this->getRow()) {
            if (isset($row)) {
                $callback($row);
            }
        }
    }

    public function getHeaders()
    {
        return $this->headers ?? [];
    }

    public function countRows()
    {
        if (isset($this->rowsCount)) {
            return $this->rowsCount;
        }

        $file = new \SplFileObject($this->path, 'r');
        $file->seek(PHP_INT_MAX);

        $this->rowsCount = isset($this->headers) ? $file->key() : $file->key() + 1;

        return $this->rowsCount;
    }

    public function justifyColumns($row)
    {
        if (empty($this->getHeaders())) {
            return $row;
        }

        $data = [];
        foreach ($this->getHeaders() as $key => $value) {
            $data[$key] = $row[$value] ?? null;
        }

        return $data;
    }
}
