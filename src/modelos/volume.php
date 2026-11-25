<?php

class Volume
{
    private string $descricao;
    private float $pesoKg;
    private string $tipo;

    public function __construct(string $descricao, float $pesoKg, string $tipo)
    {
        $this->descricao = $descricao;
        $this->pesoKg = $pesoKg;
        $this->tipo = $tipo;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getPesoKg(): float
    {
        return $this->pesoKg;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }
}
