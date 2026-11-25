<?php

class Precificacao
{
    public function calcular(Pedido $pedido): array
    {
        $veiculo = $pedido->getVeiculo();

        if ($veiculo === null) {
            throw new RuntimeException('Pedido sem veículo alocado.');
        }

        $base = ($pedido->getDistanciaKm() * $veiculo->calcularCustoKm())
            + ($pedido->getPesoTotal() * $veiculo->calcularTaxaKg());

        $adicionalPercentual = 0.0;

        foreach ($pedido->getVolumes() as $volume) {
            if ($volume->getTipo() === 'Frágil') {
                $adicionalPercentual = max($adicionalPercentual, 0.12);
            }

            if ($volume->getTipo() === 'Refrigerada') {
                $adicionalPercentual = max($adicionalPercentual, 0.18);
            }
        }

        $adicional = $base * $adicionalPercentual;
        $subtotal = $base + $adicional;

        $desconto = (
            $pedido->getCliente()->isCorporativo()
            && $pedido->getDistanciaKm() > 50
        ) ? $subtotal * 0.08 : 0.0;

        $frete = $subtotal - $desconto;
        $seguro = $pedido->exigeSeguro() ? max(10.0, $frete * 0.03) : 0.0;
        $apolice = $pedido->exigeSeguro() ? $pedido->gerarApolice() : 'Não aplicável';

        $pedido->setValores($frete, $seguro, $apolice);

        return [
            'base' => $base,
            'adicional' => $adicional,
            'desconto' => $desconto,
            'frete' => $frete,
            'seguro' => $seguro,
            'total' => $frete + $seguro,
        ];
    }
}
