<?php

namespace App\Dto;

class AbonoDto {
    public $cantidad;
    public $fecha_abono;
    public $id;

    // Add a constructor to initialize the properties
    public function __construct($cantidad, $fecha_abono, $id)
    {
        $this->cantidad = $cantidad;
        $this->fecha_abono = $fecha_abono;
        $this->id = $id;
    }

    // Getter for cantidad
    public function getCantidad()
    {
        return $this->cantidad;
    }

    // Setter for cantidad
    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;
    }

    // Getter for fecha_abono
    public function getFechaAbono()
    {
        return $this->fecha_abono;
    }

    // Setter for fecha_abono
    public function setFechaAbono($fecha_abono)
    {
        $this->fecha_abono = $fecha_abono;
    }

    // Getter for id
    public function getId()
    {
        return $this->id;
    }

    // Setter for id
    public function setId($id)
    {
        $this->id = $id;
    }


}
