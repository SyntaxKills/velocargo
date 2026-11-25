<?php

class Pedido implements Seguravel
{
    private string $codigo;
    private Cliente $cliente;
    private string $destinatario;
    private float $distanciaKm;
    private string $status = 'Aguardando Coleta';
    private ?Veiculo $veiculo = null;
    private array $volumes = [];
    private array $historico = [];
    private float $frete = 0.0;
    private float $seguro = 0.0;
    private string $apolice = 'Não aplicável';

    public function __construct(string $codigo, Cliente $cliente, string $destinatario, float $distanciaKm)
    {
        $this->codigo = $codigo;
        $this->cliente = $cliente;
        $this->destinatario = $destinatario;
        $this->distanciaKm = $distanciaKm;
        $this->adicionarEvento('Pedido criado');
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
    }

    public function getDestinatario(): string
    {
        return $this->destinatario;
    }

    public function getDistanciaKm(): float
    {
        return $this->distanciaKm;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function mudarStatus(string $status): void
    {
        $this->status = $status;
        $this->adicionarEvento('Status alterado para: ' . $status);
    }

    public function getVeiculo(): ?Veiculo
    {
        return $this->veiculo;
    }

    public function setVeiculo(Veiculo $veiculo): void
    {
        $this->veiculo = $veiculo;
    }

    public function adicionarVolume(Volume $volume): void
    {
        $this->volumes[] = $volume;
    }

    public function getVolumes(): array
    {
        return $this->volumes;
    }

    public function getPesoTotal(): float
    {
        $total = 0.0;

        foreach ($this->volumes as $volume) {
            $total += $volume->getPesoKg();
        }

        return $total;
    }

    public function adicionarEvento(string $descricao): void
    {
        $this->historico[] = new Evento($descricao);
    }

    public function getHistorico(): array
    {
        return $this->historico;
    }

    public function setValores(float $frete, float $seguro, string $apolice): void
    {
        $this->frete = $frete;
        $this->seguro = $seguro;
        $this->apolice = $apolice;
    }

    public function getApolice(): string
    {
        return $this->apolice;
    }

    public function exigeSeguro(): bool
    {
        foreach ($this->volumes as $volume) {
            if (in_array($volume->getTipo(), ['Frágil', 'Refrigerada'], true)) {
                return true;
            }
        }

        return $this->distanciaKm > 100;
    }

    public function calcularSeguro(): float
    {
        return $this->seguro;
    }

    public function gerarApolice(): string
    {
        if ($this->apolice !== 'Não aplicável') {
            return $this->apolice;
        }

        return 'AP-' . str_replace('VC-', '', $this->codigo) . '-' . date('Y');
    }
}
