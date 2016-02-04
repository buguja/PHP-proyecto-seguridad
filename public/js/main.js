jQuery(document).ready(function(){
    var validarPass= $(".validarPass");
    validarPass.keyup(function(){
        if(validarFortalezaPass(validarPass.val())){
            validarPass.css({
                "border-color": "green",
                "border-width": "3px"
            });
        }else{
            validarPass.css({
                "border-color": "red",
                "border-width": "3px"
            });
        }
    });
});

/**
 * [Validar la fortaleza de una contraseña, recibe la contraseña sin encriptar]
 * @param  [String]   $pass    [contraseña a validar, esta contraseña no debe estar encriptada]
 * @param  [Int]      $tamañoMimínimo de caracteres que debe contener la contraseña]
 * @param  [Int]      $tamañoMamáximo de caracteres que debe tener la contraseña]
 * @return [Int]      [Resultado de la validación, retorna:
 *                             -1 si la contraseña no es válida(no cumple con la cantidad de caracteres permitidos)
 *                             0 si la contraseña es debil(Si no tiene al menos una minúscula, un número y una mayúscula).
 *                             1 si la contraseña es fuerte(Si contiene al menos una minúscula, un número, una mayúscula y cumpla
 *                             con el tamaño de caracteres permitido).]
 */
function validarFortalezaPass(pass){
    var re= /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}/;
    return re.test(pass);
}
