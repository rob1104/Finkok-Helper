# Finkok
Este paquete te permite hacer uso del web service de Finkok

- [Introducción](#introduccion)
- [Instalación](#instalacion)
- [Uso](#uso)
  - [Agregar clientes](#agregar-clientes)
  - [Obtener clientes](#obtener-clientes)
  - [Obtener cliente por rfc](#obtener-cliente-por-rfc)
  - [Timbrar](#timbrar)
  - [Cancelar](#cancelar)
- [Licencia](#licencia)


## Introducción
Finkok-Helper provee un mecanismo para usar el servicio de timbrado de Finkok.

## Instalación
Simplemente instala el paquete con composer:

```php
composer require xisfacturacion/finkok
```
Una vez composer termine de instalar el paquete se debe importar el paquete y crear una nueva instancia pasando los parametros correspondientes:

```php
require_once __DIR__ . '\vendor\autoload.php'; // Autoload files using Composer autoload

use XisFacturacion\Finkok;

$username = "";   //Usuario finkok
$password = "";   //Contraseña finkok
$sandbox = false; // por defecto esta en true

$finkok = new Finkok($username, $password, $sandbox);
```

## Uso
Agregar clientes, obtener clientes, timbrar y cancelar es muy fácil, los metodos contienen la siguiente firma y devuelven un valor adecuado para trabajar con el resultado.

## Agregar clientes
```php
$finkok->newCliente($rfc);
```

## Obtener clientes
```php
$clientes = $finkok->getClientes();
```

## Obtener cliente por rfc
```php
$client = $finkok->getClientePorRfc($rfc);
```

## Timbrar
```php
$response = $finkok->timbrar($xml);
```

## Cancelar
```php
$response = $finkok->cancelar($rfcemisor, $uuid);
```

## Licencia

Finkok-Helper es un programa de codigo abierto bajo la licencia [MIT license](http://opensource.org/licenses/MIT)
