<?php
    class Persona{
        private $correo;
        private $nombres;
        private $apellidos;
        
        public function getCorreo(){
            return $this->correo;
        }
        public function setCorreo($correo){
            $this->correo= $correo;
        }
        public function setNombres($nombres){
            $this->nombres= $nombres;
        }
        public function getNombres(){
            return $this->nombres;
        }
        public function setApellidos($apellidos){
            $this->apellidos= $apellidos;
        }
        public function getApellidos(){
            return $this->apellidos;
        }
    }
?>