<?php

abstract class Veiculo
{
    protected string $placa;
    protected string $modelo;
    protected int $ano;
    protected float $capacidadeKg;
    protected bool $disponivel = true;

    public function __construct(string $placa, string $modelo, int $ano, float $capacidadeKg)
    {
        $this->placa = $placa;
        $this->modelo = $modelo;
        $this->ano = $ano;
        $this->capacidadeKg = $capacidadeKg;
    }

    public function getPlaca(): string
    {
        return $this->placa;
    }

    public function getModelo(): string
    {
        return $this->modelo;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function getCapacidadeKg(): float
    {
        return $this->capacidadeKg;
    }

    public function isDisponivel(): bool
    {
        return $this->disponivel;
    }

    public function setDisponivel(bool $disponivel): void
    {
        $this->disponivel = $disponivel;
    }

    public function podeTransportar(array $volumes, float $distanciaKm): bool
    {
        if (!$this->disponivel) {
            return false;
        }

        $pesoTotal = 0.0;

        foreach ($volumes as $volume) {
            $pesoTotal += $volume->getPesoKg();
        }

        return $pesoTotal <= $this->capacidadeKg;
    }

    abstract public function getTipo(): string;

    abstract public function getDetalhes(): string;

    abstract public function calcularCustoKm(): float;

    abstract public function calcularTaxaKg(): float;
}
