<?php

class Utilitario extends Veiculo
{
    private float $volumeM3;
    private bool $refrigerado;

    public function __construct(
        string $placa,
        string $modelo,
        int $ano,
        float $capacidadeKg,
        float $volumeM3,
        bool $refrigerado
    ) {
        parent::__construct($placa, $modelo, $ano, $capacidadeKg);
        $this->volumeM3 = $volumeM3;
        $this->refrigerado = $refrigerado;
    }

    public function getTipo(): string
    {
        return 'Utilitário';
    }

    public function getDetalhes(): string
    {
        return 'Baú: ' . $this->volumeM3 . ' m³ | Refrigerado: ' . ($this->refrigerado ? 'Sim' : 'Não');
    }

    public function calcularCustoKm(): float
    {
        return 2.50;
    }

    public function calcularTaxaKg(): float
    {
        return 0.25;
    }

    public function podeTransportar(array $volumes, float $distanciaKm): bool
    {
        if (!parent::podeTransportar($volumes, $distanciaKm)) {
            return false;
        }

        foreach ($volumes as $volume) {
            if ($volume->getTipo() === 'Refrigerada' && !$this->refrigerado) {
                return false;
            }
        }

        return true;
    }
}
