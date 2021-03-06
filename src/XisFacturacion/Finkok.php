<?php

    //V 1.0.19
	namespace XisFacturacion;

	use App\Emisor_CFDI;

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
			// $contenidoXml = file_get_contents($archivoXml); //Guardo el contenido del xml en una variable

			$soapclient = new \SoapClient("{$this->url}stamp.wsdl"); //Instancia del objeto \SoapClient (con la url stamp)

			/******Parametros requeridos por el webservice******/
			$params = array(
				"xml" => $archivoXml,
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

			return $response->stampResult;
		}

		public function cancelar($rfcemisor, $uuid, $cer, $key, $pass)
		{
			//Se obtiene el contenido de los archivos de certificado y llave encriptados para poder pasarlos al we bservice
			$cer_content = file_get_contents($cer);
			$storagePath = \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
			$q = Emisor_CFDI::whereRfc($rfcemisor)->first();
			$file = $storagePath . "emisores/" . $rfcemisor . "/CSD/CANCELACIONES_" . $rfcemisor . ".enc";
			$cmd = 'openssl rsa -in ' . $key . ' -des3 -out ' . $file . ' -passout pass:'. $pass;
            shell_exec($cmd);
            $key_content = file_get_contents($file);
            $client = new \SoapClient("{$this->url}cancel.wsdl");  //Instancia del objeto SoapClient (con la url cancel)
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

			return $response->cancelResult;
		}

		public function getAcuse($rfcemisor, $uuid, $type)
        {
            $params = array(
                "username" => $this->username,
                "password" => $this->password,
                "taxpayer_id" => $rfcemisor,
                "uuid" => $uuid,
                "type" => $type
            );

            $client = new \SoapClient("{$this->url}cancel.wsdl");  //Instancia del objeto SoapClient (con la url cancel)
            $response = $client->__soapCall("get_receipt", array($params));
            return $response;
        }

		public function getClientePorRfc($rfc)
		{
			$soapclient = new \SoapClient("{$this->url}registration.wsdl");
	        /******Parametros requeridos por el webservice******/
			$params = array(
			  "reseller_username" => $this->username,
			  "reseller_password" => $this->password,
			  "taxpayer_id" => $rfc
			);
	        $response = $client->__soapCall('get', array($params));
	        return $response->getResult;
		}

		public function getTimbresRestantesPorRfc($rfc)
		{
			$soapclient = new \SoapClient("{$this->url}utilities.wsdl");
			$params = array(
			  "username" => $this->username,
			  "password" => $this->password,
			  "taxpayer_id" => $rfc
			);
			 
			# Response envia al webservice los datos del array al método report_credit
			$response = $soapclient->__soapCall("report_credit", array($params));
			 
			# La variable credits almacena EL REPORTE de los créditos que regresa el web service
			$credits = $response->report_creditResult->result;
			 
			# Se verifica si existen créditos
			if (property_exists($credits, 'ReportTotalCredit')) {
				return $credits->ReportTotalCredit[$i]->credit;
			}
			return "Usuario Ilimitado";
		}

		public function getClientes()
		{
			$soapclient = new \SoapClient("{$this->url}registration.wsdl");
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
	        $client = new \SoapClient("{$this->url}registration.wsdl");
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
