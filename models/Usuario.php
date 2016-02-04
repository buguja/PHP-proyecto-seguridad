<?php
    class Usuario extends Persona{
        private $rol;
        private $intentosFallidosLogin;
        private $antiguedadPass;
        
        public function __construct($correo, $nombres, $apellidos, $rol/*, $intentosFallidosLogin*/, $antiguedadPass){
            parent::setCorreo($correo);
            parent::setNombres($nombres);
            parent::setApellidos($apellidos);
            self::setRol($rol);
            /*self::setIntentosFallidosLogin= $intentosFallidosLogin;*/
            self::setAntiguedadPass($antiguedadPass);
        }
        
        public function getRol(){
            return $this->rol;
        }
        public function setRol($rol){
            $this->rol= $rol;
        }
        public function getIntenosFallidosLogin(){
            return $this->intenosFallidosLogin;
        }
        public function setIntenosFallidosLogin($intenosFallidosLogin){
            $this->intenosFallidosLogin= $ntenosFallidosLogin;
        }
        public function getAntiguedadPass(){
            return $this->antiguedadPass;
        }
        public function setAntiguedadPass($antiguedadPass){
            $this->antiguedadPass= $antiguedadPass;
        }
    }
?>