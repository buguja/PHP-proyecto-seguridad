<?php
    require "../vendor/autoload.php";
    require "../models/BDManager.php";
    require "../models/Persona.php";
    require "../models/Usuario.php";

    use Symfony\Component\HttpFoundation\Request;

    $app = new \Slim\Slim();

    $twig= new Twig_Environment(new Twig_Loader_Filesystem("views/"));

    $app->get('/', function() use($app, $twig){
        $bdManager= new BDManager();
        $url= "principal.html";
        $resultadoConsulta= $bdManager->realizarConsulta("SELECT ip FROM ipLock WHERE ip='". $app->request()->getIp(). "'");
        if($resultadoConsulta){
            $url= "ipBloqueada.html";
            guardarBitacora("Intento de acceso, con ip bloqueada ", $app->request()->getIp());
        }
        renderPrincipal($twig, $url, "");
    })->name('root');

    $app->get('/administracion', function() use($app, $twig){
        if(isSessionExpired() || empty($_SESSION["user"])){
            //$app->redirect("logout");
            echo $twig->render("principal.html",
            array(
                    "path"=> "",
                    "navbar_brand"=> "Examen en línea",
                    "menu_nvabar"=> ""
                )
            );
        }else{
            $bdManager= new BDManager();
            $ips= array();
            $usuarios= array();
            foreach($bdManager->realizarConsulta("SELECT ipLock_id, ip FROM ipLock") as $value) {
                array_push($ips, array(
                    "descripcion"=> $value["ip"],
                    "id"=> $value["ipLock_id"]
                ));
            }
            foreach($bdManager->realizarConsulta("SELECT persona_id FROM usuario WHERE intentos>=3") as $value) {
                array_push($usuarios, $value["persona_id"]);
            }
            echo $twig->render("administracion.html",
                array(
                    "path"=> "",
                    "navbar_brand"=> $_SESSION["user"]->getNombres() . " " . $_SESSION["user"]->getApellidos(),
                    "menu_navbar"=> array(
                        array("href"=> "calificar-examen",
                              "item"=> "Calificar examen"),
                        array("href"=> "aplicar-examen",
                              "item"=> "Aplicar examen"),
                        array("href"=> "reportes",
                              "item"=> "Reportes"),
                        array("href"=> "logout",
                        "item"=> "Salir")
                    ),
                    "ip_bloqueadas"=> $ips,
                    "usuarios_bloqueados"=> $usuarios,
                    "contentAlert"=> mostrarMensajeContrasenia()
                )
            );
        }
    })->name('admon');

    $app->get('/alumno', function() use($app, $twig){
        if(isSessionExpired() || empty($_SESSION["user"])){
            $app->redirect("logout");
        }else{
            echo $twig->render("alumno.html",
                array(
                    "path"=> "",
                    "navbar_brand"=> $_SESSION["user"]->getNombres() . " " . $_SESSION["user"]->getApellidos(),
                    "menu_navbar"=> array(
                        array("href"=> "resolver-examen",
                        "item"=> "Resolver examen"),
                        array("href"=> "consultar-calificacion",
                              "item"=> "Consultar calificacin"),
                        array("href"=> "logout",
                              "item"=> "Salir")
                    ),
                    "contentAlert"=> mostrarMensajeContrasenia()
                )
            );
        }
    })->name('alumno');

    $app->get('/logout', function() use($app){
        session_start();
        if(!empty($_SESSION["user"])){
            guardarBitacora("Cierre de sesión <" . $_SESSION["user"]->getCorreo(). ">", $app->request()->getIp());
        }
        session_unset();
        session_destroy();
        $app->redirect($app->urlFor('root'));
    });

/* ********************* POST ********************* */
    $app->post("/login", function() use($app){
        iniciarSesionExpirar();
        $bdManager= new BDManager();
        $user= $app->request->post("user");
        $pass= $app->request->post("pass");
        if(empty($_SESSION["intentosIp"])){
            $_SESSION["intentosIp"]= 0;
        }
        if(empty($_SESSION["$user"])){
            $resultadoConsulta= $bdManager->realizarConsulta("SELECT intentos FROM usuario WHERE persona_id='$user'");
            if($resultadoConsulta){
                $_SESSION["$user"]= $resultadoConsulta[0]["intentos"];
            }else{
                $_SESSION["$user"]= 0;
            }
        }
        $resultadoConsulta= $bdManager->realizarConsulta("SELECT pass.pass
        FROM pass, usuario
        WHERE pass.persona_id=usuario.persona_id AND pass.persona_id='$user' AND usuario.intentos<3 ORDER BY pass.antiguedad DESC LIMIT 1");
        if($resultadoConsulta && (strcmp($resultadoConsulta[0]["pass"], sha1($pass)) == 0)){
            $consulta= "SELECT persona.persona_id as correo, persona.nombres, persona.apellidos, usuario.rol, usuario.ultimointento, pass.antiguedad FROM persona, pass, usuario WHERE persona.persona_id=usuario.persona_id AND pass.persona_id=usuario.persona_id and usuario.persona_id='$user' and pass.pass=sha1('$pass')";
            $resultadoConsulta= $bdManager->realizarConsulta($consulta);
            $_SESSION["user"]= new Usuario(
                $resultadoConsulta[0]["correo"],
                $resultadoConsulta[0]["nombres"],
                $resultadoConsulta[0]["apellidos"],
                $resultadoConsulta[0]["rol"],
                $resultadoConsulta[0]["antiguedad"]
            );
            guardarBitacora("Inicio de sesión <" . $_SESSION["user"]->getCorreo() . ">", $app->request()->getIp());
            if($_SESSION["user"]->getRol() === "admin"){
                $app->redirect("administracion");
            }else{
                $app->redirect("alumno");
            }
        }else{
            $_SESSION["intentosIp"]+= 1;
            $_SESSION["$user"]+=1;
            $bdManager->realizarAccion(
                "UPDATE usuario SET intentos=" . $_SESSION["$user"] . " WHERE persona_id='" . $user ."'"
            );
            if($_SESSION["$user"] > 2){
                guardarBitacora("<". $_SESSION["$user"]. "> Usuario bloqueado por exceso de intentos <$user>", $app->request()->getIp());
            }
            if($_SESSION["intentosIp"] > 8){
                $bdManager->realizarAccion(
                    "INSERT INTO ipLock(ip) VALUES('". $app->request()->getIp(). "')"
                );
                guardarBitacora(
                    "<". $_SESSION["intentosIp"]. "> Ip bloqueada por demaciados intentos fallidos de conexión <$user>", $app->request()->getIp()
                );
                $_SESSION["intentosIp"]= 0;
                $app->redirect("logout");
            }
            guardarBitacora(
                "[ip:". $_SESSION["intentosIp"]. "]Intento de inicio de sesión <$user:". $_SESSION["$user"]. ">", $app->request()->getIp()
            );
            $app->redirect($app->urlFor('root'));
        }
    });

    $app->post('/eliminarUser', function() use($app){
        if(isSessionExpired()){
            $app->redirect("logout");
        }
        $bdManager= new BDManager();
        $users= $app->request->post("users");
        if(!empty($users)){
            foreach ($users as $value) {
                $bdManager->realizarAccion("UPDATE usuario SET intentos=0 WHERE persona_id='$value'");
                guardarBitacora("<". $_SESSION["user"]->getCorreo(). "> Liberó usuario bloqueado: <$value>", $app->request()->getIp());
            }
        }
        $app->redirect("administracion");
    });

    $app->post('/eliminarIp', function() use($app){
        if(isSessionExpired()){
            $app->redirect("logout");
        }
        $bdManager= new BDManager();
        $ips= $app->request->post("ips");
        if(!empty($ips)){
            foreach ($ips as $value) {
                $bdManager->realizarAccion("DELETE FROM ipLock WHERE ipLock_id=$value");
                guardarBitacora("<". $_SESSION["user"]->getCorreo(). "> Liberó ip bloqueada: <$value>", $app->request()->getIp());
            }
        }
        $app->redirect("administracion");
    });

    $app->post('/nuevoUsuario', function() use($app, $twig){
        $bdManager= new BDManager();
        $name= $app->request->post("name");
        $apellidos= $app->request->post("apellidos");
        $user= $app->request->post("user");
        $pass= $app->request->post("pass");

        $resultadoConsulta= $bdManager->realizarConsulta("SELECT persona_id FROM usuario WHERE persona_id='$user'");
        if($resultadoConsulta){
            renderPrincipal($twig, "principal.html", "El usuario ya existe, intente con otro usuario");
            $app->stop();
        }
        $bdManager->realizarAccion("INSERT INTO usuario(persona_id, rol, intentos) VALUES('$user', 'alumno', 0)");
        $bdManager->realizarAccion("INSERT INTO persona(persona_id, nombres, apellidos) VALUES('$user', '$name', '$apellidos')");
        $bdManager->realizarAccion("INSERT INTO pass(persona_id, pass, antiguedad) VALUES('$user', sha1('$pass'), NOW())");
        renderPrincipal($twig, "principal.html", "Usuario registrado correctamente");
    });

    $app->post('/nuevaContrasenia', function() use($app){
        if(!isSessionExpired()){
            $bdManager= new BDManager();
            $currentPass= $app->request->post("currentPass");
            $newPass= $app->request->post("newPass");
            $resultadoConsulta= $bdManager->realizarConsulta("SELECT antiguedad FROM pass WHERE persona_id='". $_SESSION["user"]->getCorreo(). "' AND pass=sha1('" . $currentPass . "')");
            if($resultadoConsulta){
                $resultadoConsulta= $bdManager->realizarConsulta("SELECT antiguedad FROM pass WHERE persona_id='". $_SESSION["user"]->getCorreo(). "' and pass=sha1('" . $newPass . "')");
                if($resultadoConsulta){
                    echo "La contraseña nueva ya fue usada alguna vez.";
                }else{
                    $resultadoConsulta= $bdManager->realizarConsulta("SELECT pass, antiguedad FROM pass WHERE persona_id='". $_SESSION["user"]->getCorreo(). "' ORDER BY antiguedad DESC LIMIT 3");
                    if(count($resultadoConsulta) >= 3){
                        $resultadoConsulta= $bdManager->realizarAccion("DELETE FROM pass WHERE persona_id='". $_SESSION["user"]->getCorreo(). "' ORDER BY antiguedad ASC LIMIT 1");
                    }
                    $bdManager->realizarAccion("INSERT INTO pass VALUES('" . $_SESSION["user"]->getCorreo() . "', sha1('" . $newPass . "'), NOW())");
                    echo "La contraseña se cambió correctamente.";
                    guardarBitacora("<". $_SESSION["user"]->getCorreo(). "> Cambio de contraseña. ", $app->request()->getIp());
                }
            }else{
                echo "La contraseña actual que proporsionó no existe.";
            }
        }else{
            echo "Tu sesión fue terminada.";
            guardarBitacora("<". $_SESSION["user"]->getCorreo(). "> Sesión expirada antes de cambio de contraseña. ", $app->request()->getIp());
        }
        echo "<br><a href='logout'>Regresar a principal</a>";
    });

/* ********************* FUNCIONES ********************* */

    function escribirArchivo($archivo, $string, $tipo){
        $file= fopen($archivo, $tipo);
        fwrite($file, $string. PHP_EOL);
        fclose($file);
    }

    function guardarBitacora($string, $ip){
        date_default_timezone_set("America/Mexico_City");
        escribirArchivo("bitacora.log", date("Y-m-d H:m:s"). " ". $string. " ". $ip, "a");
    }

    function iniciarSesionExpirar(){
        @session_start();
        $_SESSION["intervalo"]= 1;
        $_SESSION["inicio"]= time();
    }

    function isSessionExpired(){
        @session_start();
        $segundos= time();
        $tiempoTranscurrido= $segundos;
        if(empty($_SESSION["inicio"]) || empty($_SESSION["intervalo"])){
            return false;
        }
        $tiempoMaximo= $_SESSION["inicio"] + ($_SESSION["intervalo"] * 60);
        if($tiempoMaximo < $tiempoTranscurrido){
            return true;
        }
    }

    function renderPrincipal($twig, $url, $user){
        echo $twig->render($url,
            array(
                "path"=> "",
                "navbar_brand"=> "Examen en línea",
                "menu_nvabar"=> "",
                "contentAlert"=> $user
            )
        );
    }

    function mostrarMensajeContrasenia(){
        $date= date("Ymd", strtotime("+1 month", strtotime($_SESSION["user"]->getAntiguedadPass())));
        if($date < date("Ymd")){
            return "Contraseña vieja";
        }
        return "";
    }

    $app->run();
?>
