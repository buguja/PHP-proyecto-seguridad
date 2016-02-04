<?php
    class BDManager{
        private static $bd_server= "127.0.0.1";
        private static $bd_port= 3306;
        private static $bd_name= "examenenlinea_seguridad";
        private static $bd_usuario= "root";
        private static $bd_pass= "";
        private $con= null;
        
        private function realizarConexion(){
            try{
                $this->con= new PDO('mysql:host=' . self::$bd_server . ';port=' . self::$bd_port . ';dbname=' . self::$bd_name . ';charset=utf8', self::$bd_usuario, self::$bd_pass);
            }catch(PDOException $e){
                print "¡Error!: " . $e->getMessage() . "<br/>";
                die();
            }
        }
        
        public function realizarConsulta($consulta){
            $this->realizarConexion();
            try{
                $prepare= $this->con->prepare($consulta);
                $prepare->execute();
                $this->cerrarConexion();
                return $prepare->fetchAll(PDO::FETCH_ASSOC);
            }catch(PDOException $e){
                print "¡Error!: " . $e.getMessage() . "<br/>";
                $this->cerrarConexion();
                die();
            }
        }
        
        public function realizarAccion($accion){
            $this->realizarConexion();
            try{
                $prepare= $this->con->prepare($accion);
                $prepare->execute();
                $this->cerrarConexion();
                return $prepare->fetchAll(PDO::FETCH_ASSOC);
            }catch(PDOException $e){
                print "¡Error!: " . $e.getMessage() . "<br/>";
                $this->cerrarConexion();
                die();
            }
        }
        
        private function cerrarConexion(){
            $this->con= null;
        }
    }
?>