<?php

require_once __DIR__ . '/src/contratos/seguravel.php';

require_once __DIR__ . '/src/modelos/cliente.php';
require_once __DIR__ . '/src/modelos/cliente_corporativo.php';
require_once __DIR__ . '/src/modelos/evento.php';
require_once __DIR__ . '/src/modelos/volume.php';
require_once __DIR__ . '/src/modelos/veiculo.php';
require_once __DIR__ . '/src/modelos/motocicleta.php';
require_once __DIR__ . '/src/modelos/utilitario.php';
require_once __DIR__ . '/src/modelos/caminhao_refrigerado.php';
require_once __DIR__ . '/src/modelos/pedido.php';

require_once __DIR__ . '/src/servicos/precificacao.php';

class VeloCargoApp
{
    private array $veiculos = [];
    private array $clientes = [];
    private array $pedidos = [];
    private int $proximoCodigo = 1003;
    private Precificacao $precificacao;

    public function __construct()
    {
        $this->precificacao = new Precificacao();
        $this->carregarSeed();
    }

    private function carregarSeed(): void
    {
        $this->veiculos = [
            new Motocicleta('ABC1D23', 'Honda CG 160', 2023, 25, 160, true),
            new Utilitario('EFG4H56', 'Fiat Fiorino', 2022, 650, 3.2, false),
            new CaminhaoRefrigerado('IJK7L89', 'Mercedes Sprinter Frigorífica', 2024, 2500, -15, true),
        ];

        $this->clientes = [
            new Cliente('Ana Souza', '123.456.789-00', 'Rua das Flores, 100 - São Paulo/SP'),
            new ClienteCorporativo('Mercado Central Ltda.', '12.345.678/0001-90', 'Av. Brasil, 500 - Campinas/SP'),
        ];

        $pedido1 = new Pedido('VC-1001', $this->clientes[1], 'Carlos Lima - Campinas/SP', 80);
        $pedido1->adicionarVolume(new Volume('Caixas de vidro', 120, 'Frágil'));
        $this->despacharSeed($pedido1, $this->veiculos[1], 'Alocado ao veículo EFG4H56');
        $pedido1->mudarStatus('Em Trânsito');

        $pedido2 = new Pedido('VC-1002', $this->clientes[0], 'Farmácia Vida - Santos/SP', 140);
        $pedido2->adicionarVolume(new Volume('Medicamentos refrigerados', 300, 'Refrigerada'));
        $this->despacharSeed($pedido2, $this->veiculos[2], 'Alocado ao veículo IJK7L89');

        $this->pedidos = [$pedido1, $pedido2];
    }

    private function despacharSeed(Pedido $pedido, Veiculo $veiculo, string $evento): void
    {
        $veiculo->setDisponivel(false);
        $pedido->setVeiculo($veiculo);
        $pedido->adicionarEvento($evento);
        $this->precificacao->calcular($pedido);
    }

    private function dinheiro(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    private function pausar(): void
    {
        echo "\nPressione Enter para continuar...";
        fgets(STDIN);
    }

    private function listarFrota(): void
    {
        echo "\n=== FROTA ===\n";

        foreach ($this->veiculos as $veiculo) {
            echo sprintf(
                "[%s] %s | %s | %d | %.1f kg | %s\n  %s\n",
                $veiculo->isDisponivel() ? 'DISPONÍVEL' : 'EM ROTA',
                $veiculo->getTipo(),
                $veiculo->getPlaca(),
                $veiculo->getAno(),
                $veiculo->getCapacidadeKg(),
                $veiculo->getModelo(),
                $veiculo->getDetalhes()
            );
        }
    }

    private function buscarPedido(string $codigo): ?Pedido
    {
        foreach ($this->pedidos as $pedido) {
            if (strtoupper($pedido->getCodigo()) === strtoupper(trim($codigo))) {
                return $pedido;
            }
        }

        return null;
    }

    private function detalharPedido(Pedido $pedido): void
    {
        $veiculo = $pedido->getVeiculo();
        $valores = $this->precificacao->calcular($pedido);

        echo "\n=== PEDIDO {$pedido->getCodigo()} ===\n";
        echo "Cliente: {$pedido->getCliente()->getNome()} ({$pedido->getCliente()->getDocumento()})\n";
        echo "Destinatário: {$pedido->getDestinatario()}\n";
        echo "Distância: {$pedido->getDistanciaKm()} km\n";
        echo "Status: {$pedido->getStatus()}\n";
        echo 'Veículo: ' . ($veiculo ? $veiculo->getTipo() . ' - ' . $veiculo->getPlaca() : 'Não alocado') . "\n";
        echo "Peso total: {$pedido->getPesoTotal()} kg\n";
        echo "Volumes:\n";

        foreach ($pedido->getVolumes() as $volume) {
            echo " - {$volume->getDescricao()} | {$volume->getPesoKg()} kg | {$volume->getTipo()}\n";
        }

        echo "\nFinanceiro:\n";
        echo "Base: {$this->dinheiro($valores['base'])}\n";
        echo "Adicionais: {$this->dinheiro($valores['adicional'])}\n";
        echo "Desconto: -{$this->dinheiro($valores['desconto'])}\n";
        echo "Frete: {$this->dinheiro($valores['frete'])}\n";
        echo "Seguro: {$this->dinheiro($valores['seguro'])} | Apólice: {$pedido->getApolice()}\n";
        echo "Total: {$this->dinheiro($valores['total'])}\n";
        echo "\nHistórico:\n";

        foreach ($pedido->getHistorico() as $evento) {
            echo " - {$evento->getData()} | {$evento->getDescricao()}\n";
        }
    }

    private function consultarPedido(): void
    {
        echo "Código de rastreio: ";
        $pedido = $this->buscarPedido(trim(fgets(STDIN)));

        if (!$pedido) {
            echo "Pedido não encontrado.\n";
            return;
        }

        $this->detalharPedido($pedido);
    }

    private function relatorio(): void
    {
        $bruto = 0.0;
        $seguros = 0.0;
        $peso = 0.0;
        $maior = null;

        foreach ($this->pedidos as $pedido) {
            $valores = $this->precificacao->calcular($pedido);
            $bruto += $valores['frete'];
            $seguros += $valores['seguro'];
            $peso += $pedido->getPesoTotal();

            if ($maior === null || $valores['total'] > $maior['total']) {
                $maior = ['pedido' => $pedido, 'total' => $valores['total']];
            }
        }

        echo "\n=== RELATÓRIO FINANCEIRO DA SESSÃO ===\n";
        echo 'Pedidos registrados: ' . count($this->pedidos) . "\n";
        echo 'Faturamento bruto (fretes): ' . $this->dinheiro($bruto) . "\n";
        echo 'Total de seguros: ' . $this->dinheiro($seguros) . "\n";
        echo 'Peso movimentado: ' . number_format($peso, 2, ',', '.') . " kg\n";
        echo 'Pedido de maior valor: ' . ($maior
            ? $maior['pedido']->getCodigo() . ' - ' . $this->dinheiro($maior['total'])
            : 'N/A') . "\n";
    }

    private function alterarStatus(): void
    {
        echo "Código do pedido: ";
        $pedido = $this->buscarPedido(trim(fgets(STDIN)));

        if (!$pedido) {
            echo "Pedido não encontrado.\n";
            return;
        }

        echo "Novo status (1-Em Trânsito, 2-Entregue, 3-Ocorrência): ";
        $opcao = trim(fgets(STDIN));

        if ($opcao === '1') {
            $status = 'Em Trânsito';
        } elseif ($opcao === '2') {
            $status = 'Entregue';
        } elseif ($opcao === '3') {
            echo "Descreva a ocorrência: ";
            $descricao = trim(fgets(STDIN));

            if ($descricao === '') {
                echo "A descrição da ocorrência não pode ficar vazia.\n";
                return;
            }

            $pedido->adicionarEvento('Ocorrência: ' . $descricao);
            echo "Ocorrência registrada.\n";
            return;
        } else {
            echo "Opção inválida.\n";
            return;
        }

        $pedido->mudarStatus($status);

        if ($status === 'Entregue' && $pedido->getVeiculo() !== null) {
            $pedido->getVeiculo()->setDisponivel(true);
        }

        echo "Status atualizado.\n";
    }

    private function criarPedido(): void
    {
        echo "\n=== NOVO PEDIDO ===\nClientes cadastrados:\n";

        foreach ($this->clientes as $indice => $cliente) {
            echo ($indice + 1) . '. ' . $cliente->getNome()
                . ($cliente->isCorporativo() ? ' (Corporativo)' : '') . "\n";
        }

        echo "Selecione o cliente: ";
        $indiceCliente = (int) trim(fgets(STDIN)) - 1;

        if (!isset($this->clientes[$indiceCliente])) {
            echo "Cliente inválido.\n";
            return;
        }

        echo "Nome do destinatário: ";
        $destinatario = trim(fgets(STDIN));

        if ($destinatario === '') {
            echo "Destinatário inválido.\n";
            return;
        }

        echo "Distância em km: ";
        $distancia = (float) str_replace(',', '.', trim(fgets(STDIN)));

        if ($distancia <= 0) {
            echo "Distância inválida.\n";
            return;
        }

        $volumes = [];

        do {
            echo "Descrição do volume: ";
            $descricao = trim(fgets(STDIN));

            echo "Peso (kg): ";
            $peso = (float) str_replace(',', '.', trim(fgets(STDIN)));

            if ($peso <= 0 || $descricao === '') {
                echo "Dados inválidos.\n";
                return;
            }

            echo "Tipo (1-Geral, 2-Frágil, 3-Refrigerada): ";
            $opcaoTipo = trim(fgets(STDIN));
            $tipos = ['1' => 'Geral', '2' => 'Frágil', '3' => 'Refrigerada'];
            $tipo = $tipos[$opcaoTipo] ?? null;

            if ($tipo === null) {
                echo "Tipo inválido.\n";
                return;
            }

            $volumes[] = new Volume($descricao, $peso, $tipo);

            echo "Adicionar outro volume? (s/n): ";
            $mais = strtolower(trim(fgets(STDIN))) === 's';
        } while ($mais);

        $pedido = new Pedido(
            'VC-' . $this->proximoCodigo++,
            $this->clientes[$indiceCliente],
            $destinatario,
            $distancia
        );

        foreach ($volumes as $volume) {
            $pedido->adicionarVolume($volume);
        }

        echo "\nVeículos disponíveis e compatíveis:\n";
        $opcoes = [];

        foreach ($this->veiculos as $veiculo) {
            if ($veiculo->podeTransportar($volumes, $distancia)) {
                $opcoes[] = $veiculo;
                echo count($opcoes) . '. ' . $veiculo->getTipo()
                    . ' - ' . $veiculo->getPlaca()
                    . ' (' . $veiculo->getCapacidadeKg() . " kg)\n";
            }
        }

        if (count($opcoes) === 0) {
            echo "Nenhum veículo compatível. Pedido não criado.\n";
            return;
        }

        echo "Selecione o veículo: ";
        $indiceVeiculo = (int) trim(fgets(STDIN)) - 1;

        if (!isset($opcoes[$indiceVeiculo])) {
            echo "Seleção inválida. Pedido não criado.\n";
            return;
        }

        $veiculo = $opcoes[$indiceVeiculo];
        $veiculo->setDisponivel(false);
        $pedido->setVeiculo($veiculo);
        $pedido->adicionarEvento('Alocado ao veículo ' . $veiculo->getPlaca());
        $pedido->mudarStatus('Em Trânsito');

        $valores = $this->precificacao->calcular($pedido);
        $this->pedidos[] = $pedido;

        echo "Pedido criado: {$pedido->getCodigo()} | Total: {$this->dinheiro($valores['total'])}\n";
    }

    public function executar(): void
    {
        do {
            echo "\n====================================\n";
            echo " VELOCARGO | LOGÍSTICA & DISTRIBUIÇÃO\n";
            echo "====================================\n";
            echo "1. Listar frota\n";
            echo "2. Consultar pedido / rastreio\n";
            echo "3. Relatório financeiro\n";
            echo "4. Criar pedido de frete\n";
            echo "5. Atualizar status / registrar ocorrência\n";
            echo "0. Encerrar\n";
            echo "Escolha: ";

            $opcao = trim(fgets(STDIN));

            switch ($opcao) {
                case '1':
                    $this->listarFrota();
                    break;
                case '2':
                    $this->consultarPedido();
                    break;
                case '3':
                    $this->relatorio();
                    break;
                case '4':
                    $this->criarPedido();
                    break;
                case '5':
                    $this->alterarStatus();
                    break;
                case '0':
                    echo "Expediente encerrado. Obrigado por utilizar a VeloCargo!\n";
                    break;
                default:
                    echo "Opção inválida. Tente novamente.\n";
            }

            if ($opcao !== '0') {
                $this->pausar();
            }
        } while ($opcao !== '0');
    }
}

$app = new VeloCargoApp();
$app->executar();
