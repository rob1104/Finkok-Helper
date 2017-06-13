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
JorgeAndrade\Finkok provee un mecanismo para user el servio de timbrado de Finkok.

```php
require 'vendor/autoload.php';
use JorgeAndrade\Finkok;
use JorgeAndrade\Exceptions\FinkokException;

$username = "";
$password = "";
$sandbox = false; // por defecto esta en true

$finkok = new Finkok($username, $password, $sandbox);

try {
  $finkok->createNewClient($rfc);

} catch (FinkokException $e) {
  var_dump($e->getMessage());
}
```

## Instalación
Simplemente instala el paquete con composer:

```php
composer require jorgeandrade/finkok
```
Una vez composer termine de instalar el paquete simplemente importa el paquete y crea una nueva instancia pasando los parametros correspondientes:

```php
require 'vendor/autoload.php';

use JorgeAndrade\Finkok;
use JorgeAndrade\Exceptions\FinkokException;

$username = "";
$password = "";
$sandbox = false; // por defecto esta en true

$finkok = new Finkok($username, $password, $sandbox);
```

## Uso
Agregar clientes, obtener clientes, timbrar y cancelar es extremadamente facil.
Si algo sale mal las funciones arrojaran una exception de tipo **JorgeAndrade\Exceptions\FinkokException**.
## Agregar clientes
```php
$finkok->createNewClient($rfc);
```

##Obtener clientes
```php
$clientes = $finkok->getClients();
```

## Obtener cliente por rfc
```php
$client = $finkok->getClient($rfc);
```

## Timbrar
```php
$response = $finkok->timbrar($xml);
```

## Cancelar
```php
$response = $finkok->cancelar($rfc, $uuids = [], $cer, $key);
```

## Licencia

Finkok es un programa de codigo abierto bajo la licencia [MIT license](http://opensource.org/licenses/MIT)
