<?php

class Motocicleta extends Veiculo
{
    private int $cilindradas;
    private bool $bau;

    public function __construct(
        string $placa,
        string $modelo,
        int $ano,
        float $capacidadeKg,
        int $cilindradas,
        bool $bau
    ) {
        parent::__construct($placa, $modelo, $ano, $capacidadeKg);
        $this->cilindradas = $cilindradas;
        $this->bau = $bau;
    }

    public function getTipo(): string
    {
        return 'Motocicleta';
    }

    public function getDetalhes(): string
    {
        return $this->cilindradas . 'cc | Baú: ' . ($this->bau ? 'Sim' : 'Não');
    }

    public function calcularCustoKm(): float
    {
        return 1.20;
    }

    public function calcularTaxaKg(): float
    {
        return 0.15;
    }

    public function podeTransportar(array $volumes, float $distanciaKm): bool
    {
        if (!parent::podeTransportar($volumes, $distanciaKm) || $distanciaKm > 100) {
            return false;
        }

        foreach ($volumes as $volume) {
            if ($volume->getTipo() === 'Refrigerada') {
                return false;
            }
        }

        return true;
    }
}
