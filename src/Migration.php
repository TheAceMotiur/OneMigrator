<?php
namespace OneMigrator;

class Migration
{
    private string $version;
    private string $description;
    private string $sql;
    private string $checksum;

    public function __construct(string $version, string $description, string $sql)
    {
        // Ensure version follows pattern like '001', '002'
        if (!preg_match('/^\d{3}$/', $version)) {
            throw new \InvalidArgumentException('Version must be a 3-digit number like 001');
        }

        $this->version = $version;
        $this->description = $description;
        $this->sql = $sql;
        $this->checksum = $this->calculateChecksum();
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    private function calculateChecksum(): string
    {
        return hash('sha256', $this->sql);
    }
}
