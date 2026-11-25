<?php

class Evento
{
    private string $data;
    private string $descricao;

    public function __construct(string $descricao)
    {
        $this->data = date('d/m/Y H:i:s');
        $this->descricao = $descricao;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }
}
