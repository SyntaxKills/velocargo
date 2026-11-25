<?php

class ClienteCorporativo extends Cliente
{
    public function isCorporativo(): bool
    {
        return true;
    }
}
