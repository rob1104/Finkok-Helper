<?php	
	namespace XisFacturacion;

	class Finkok
	{
		/**************************************P R O P I E D A D E S**************************************************/
		//Usuario y contraseña asignados por FINKOK
		private $username;
		private $password;
		private $url;		
		/***********************************************************************************************************/


		//******C O N S T R U C T O R *******/
		public function __construct($username, $password, $sandbox = false)
		{
			$this->username = $username;
			$this->password = $password;
			$this->url = $sandbox ? 'http://demo-facturacion.finkok.com/servicios/soap/' : 'https://facturacion.finkok.com/servicios/soap/';
		}	
		
		/* Funcion para timbrar o intentar timbrar un comprobante
		*/
		public function timbrar($archivoXml)
		{
			$contenidoXml = file_get_contents($archivoXml); //Guardo el contenido del xml en una variable

			$soapclient = new SoapClient("{$this->url}stamp.wsdl"); //Instancia del objeto SoapClient (con la url stamp)

			/******Parametros requeridos por el webservice******/
			$params = array(
				"xml" => $contenidoXml,
				"username" => $this->username,
				"password" => $this->password
			);
			/**********************************************/

			$response = $soapclient->__soapCall("stamp", array($params));   //Ejecuta la peticion al web service, y guardamos la respuesta en un stdClass		

			if(!isset($response->stampResult->UUID)) {
				if($response->stampResult->Incidencias->Incidencia->CodigoError == 307)
					$response->stampResult->CodEstatus = "Comprobante timbrado previamente";
				else
					$response->stampResult->CodEstatus = "Error al timbrar CFDI, verifique las incidencias";
			}

			if($response->stampResult->CodEstatus == "Comprobante timbrado satisfactoriamente")
				file_put_contents($archivoXml, $response->stampResult->xml); //Guardamos el XML ya timbrado reemplazando al anterior

			return $response->stampResult;
		}

		public function cancelar($rfcemisor, $uuid)
		{
			// Generar el certificado y llave en formato .pem
			shell_exec("openssl x509 -inform DER -outform PEM -in c.cer -pubkey -out c.pem");
			shell_exec("openssl pkcs8 -inform DER -in l.key -passin pass:12345678a -out l.pem");
			shell_exec("openssl rsa -in l.pem -des3 -out cancelaciones.enc -passout pass:Facturacion2017$");
			 
			
			//Se obtiene el contenido de los archivos de certificado y llave encriptados para poder pasarlos al we bservice
			$cer_content = file_get_contents("c.pem");
			$key_content = file_get_contents("cancelaciones.enc");
			
			$client = new SoapClient("{$this->url}cancel.wsdl");  //Instancia del objeto SoapClient (con la url cancel)

			$uuid = array($uuid);  // Hago el casting de string a array porque asi lo requiere el Web Service (Aunque solo se cancele un comprobante)

			/******Parametros requeridos por el webservice******/
			$params = array(
			  "UUIDS" => array('uuids' => $uuid),
			  "username" => $this->username,
			  "password" => $this->password,
			  "taxpayer_id" => $rfcemisor,
			  "cer" => $cer_content,
			  "key" => $key_content
			);
			/**************************************************/

			$response = $client->__soapCall("cancel", array($params));   //Ejecuta la peticion al web service, y guardamos la respuesta en un stdClass
			
			/* Si cancela correctamente devuelve un acuse y lo guardo en un archivo unico*/
			if(isset($response->cancelResult->Acuse))
				file_put_contents("Acuse Cancelacion - " . $uuid[0] . ".xml", $response->cancelResult->Acuse);

			return $response->cancelResult;
		}

		public function getClientePorRfc($rfc)
		{
			$soapclient = new SoapClient("{$this->url}registration.wsdl");
	        /******Parametros requeridos por el webservice******/
			$params = array(
			  "reseller_username" => $this->username,
			  "reseller_password" => $this->password,
			  "taxpayer_id" => $rfc
			);
	        $response = $client->__soapCall('get', array($params));
	        return $response->getResult;
		}

		public function getClientes()
		{
			$soapclient = new SoapClient("{$this->url}registration.wsdl");
	        /******Parametros requeridos por el webservice******/
			$params = array(
			  "reseller_username" => $this->username,
			  "reseller_password" => $this->password
			);
	        $response = $client->__soapCall('get', array($params));
	        return $response->getResult->users;
		}
		
		public function newCliente($rfc)
	    {
	        $client = new SoapClient("{$this->url}registration.wsdl");
	        /******Parametros requeridos por el webservice******/
	        $params = array(
			  "reseller_username" => $this->username,
			  "reseller_password" => $this->password,
			  "taxpayer_id" => $rfc
			);
	        $response = $client->__soapCall('add', array($params));
	        if ($response->addResult->success) {
	            return $response->addResult->message;
	        }
	        throw new Exception($response->addResult->message);
	    }

	    public function getErrorMessage($err)
	    {
	    	$errores = [
	            201 => "UUID Cancelado exitosamente",
	            202 => "UUID Previamente cancelado",
	            203 => "UUID No corresponde el RFC del Emisor y de quien solicita la cancelación",
	            205 => "UUID No existe",
	            300 => "Usuario y contraseña inválidos",
	            301 => "XML mal formado",
	            302 => "Sello mal formado o inválido",
	            303 => "Sello no corresponde a emisor",
	            304 => "Certificado Revocado o caduco",
	            305 => "La fecha de emisión no esta dentro de la vigencia del CSD del Emisor",
	            306 => "El certificado no es de tipo CSD",
	            307 => "El CFDI contiene un timbre previo",
	            308 => "Certificado no expedido por el SAT",
	            401 => "Fecha y hora de generación fuera de rango",
	            402 => "RFC del emisor no se encuentra en el régimen de contribuyentes",
	            403 => "La fecha de emisión no es posterior al 01 de enero de 2012",
	            501 => "Autenticación no válida",
	            703 => "Cuenta suspendida",
	            704 => "Error con la contraseña de la llave Privada",
	            705 => "XML estructura inválida",
	            706 => "Socio Inválido",
	            707 => "XML ya contiene un nodo TimbreFiscalDigital",
	            708 => "No se pudo conectar al SAT",
	        ];
	        return $errores[$err];
	    }
		
	}
