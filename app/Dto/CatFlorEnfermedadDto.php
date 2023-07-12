<?php

namespace App\Dto;

class CatFlorEnfermedadDto {

    public $id;
    public $nombre_tipo_flor;
    public $updated_at;
    public $enfermedades;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getNombreTipoFlor()
    {
        return $this->nombre_tipo_flor;
    }

    public function setNombreTipoFlor($nombre_tipo_flor)
    {
        $this->nombre_tipo_flor = $nombre_tipo_flor;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at->format('d-m-Y h:M');
    }

    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
    }

    public function getEnfermedades()
    {
        return $this->enfermedades;
    }

    public function setEnfermedades($enfermedad)
    {
       $this->enfermedades = $enfermedad;
    }
}