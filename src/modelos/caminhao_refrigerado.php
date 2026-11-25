<?php

class CaminhaoRefrigerado extends Veiculo
{
    private float $temperaturaMinima;
    private bool $rastreador;

    public function __construct(
        string $placa,
        string $modelo,
        int $ano,
        float $capacidadeKg,
        float $temperaturaMinima,
        bool $rastreador
    ) {
        parent::__construct($placa, $modelo, $ano, $capacidadeKg);
        $this->temperaturaMinima = $temperaturaMinima;
        $this->rastreador = $rastreador;
    }

    public function getTipo(): string
    {
        return 'Caminhão Refrigerado';
    }

    public function getDetalhes(): string
    {
        return 'Temp. mín: ' . $this->temperaturaMinima . '°C | Rastreador: ' . ($this->rastreador ? 'Ativo' : 'Inativo');
    }

    public function calcularCustoKm(): float
    {
        return 4.80;
    }

    public function calcularTaxaKg(): float
    {
        return 0.40;
    }
}
