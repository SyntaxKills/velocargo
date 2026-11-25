<?php

class Cliente
{
    protected string $nome;
    protected string $documento;
    protected string $endereco;

    public function __construct(string $nome, string $documento, string $endereco)
    {
        $this->nome = $nome;
        $this->documento = $documento;
        $this->endereco = $endereco;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getDocumento(): string
    {
        return $this->documento;
    }

    public function getEndereco(): string
    {
        return $this->endereco;
    }

    public function isCorporativo(): bool
    {
        return false;
    }
}
