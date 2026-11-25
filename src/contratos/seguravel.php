<?php

interface Seguravel
{
    public function calcularSeguro(): float;

    public function gerarApolice(): string;
}
