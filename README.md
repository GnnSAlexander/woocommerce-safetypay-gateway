# woocommerce-safetypay-gateway
safetypay plugin for woocommerce,
Si usted tiene alguna pregunta o problema, no dude en ponerse en contacto conmigo :) gcarrasquillae@gmail.com

# Requisitos
* Tener una cuenta activa en [Safetypay](https://www.safetypay.com/en/, "Safetypay") para obtener Api Key y Signature Key.
* Tener instalado WordPress (probado 5.7.2) y WooCommerce (probado 5.4.1).
* Acceso a las carpetas donde se encuetra instalado WordPress y WooCommerce.
* Acceso al admin de WordPress.

# Instalación
1. Descarga el plugin.
2. Ingresa al administrador de tu wordPress.
3. Ingresa a Plugins / Añadir-Nuevo / Subir-Plugin.
4. Después de instalar el .zip lo puedes ver en la lista de plugins instalados , puedes activarlo o desactivarlo.
5. Para configurar el plugin debes ir a: WooCommerce / Ajustes / Finalizar Compra y Ubica la pestaña Safetypay.
6. Configura el plugin ingresando el Test Api Key, Test Signature Key , Live Api Key y Live Signature Key, dado por Safetypay.
7. La URL de CALLBACK donde se recibira la respuesta de la transacción se mostrara en el panel de administracion del plugin y esta URL debe ser configurado en el panel de safetypay.
8. Puedes crear paginas de compra exitosa y pago fallido, y configurarla para que safetypay te redireccione dependiendo del estado de la transacción.
9. Realiza una o varias compras para comprobar que todo esté bien para simular el pago, safetypay dará todos los pasos pertinentes.

Si todo está bien recuerda cambiar la variable Modo Prueba a NO y empieza a recibir pagos de forma instantánea y segura.

# Nota
Este plugin esta construido para que se pueda usar en varios sitios con una UNICA cuenta de safetypay, lo unico que debe hacer es modificar la linea 12 del archivo class-wc-safetypay-webhook-handler.php

```php
const COMMERCE = array(
    'sitio2'=> 'DOMAINOFTHESITE2/?wc-api=wc_safetypay',
    'sitio3'=> 'DOMAINOFTHESITE2/?wc-api=wc_safetypay',
    ...);
```
### HOW
* Al numero de la order se le agrega el titulo del comercio en miniscula, sin espacio y seguido de un guion. Ej: sitio1-304, Ej: sitio2-232, con el fin de identificar de cual sitio se realizo la transacción.
* Al recibir la respuesta se procesa el numero de la order y buscando coincidencias con la constante COMMERCE mira si debe o no hacer el enrutamiento
