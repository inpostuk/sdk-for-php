<?php
/**
 * (c) InPost UK Ltd <it_support@inpost.co.uk>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by NMedia Systems Ltd, <info@nmediasystems.com>
 */
set_include_path(get_include_path() . ':' . realpath(__DIR__ . '/../../'));

if (!class_exists('Zend_Http_Client')) {
    require_once 'Zend/Http/Client.php';
}

require_once 'Varien/Object.php';
require_once 'Inpost/Exception.php';
require_once 'Inpost/Models/Machine.php';
require_once 'Inpost/Models/Parcel.php';
require_once 'TCPDF/tcpdf.php';
require_once 'TCPDF/tcpdi.php';

class Inpost_Api_Client
{

    /** @var string production InPost api url */
    const PRODUCTION_API_ENDPOINT = 'https://api-uk.easypack24.net/v4/';

    /** @var string sandbox InPost api url */
    const SANDBOX_API_ENDPOINT = 'https://stage-api-uk.easypack24.net/v4/';

    /** @var string Use if want to get label A4 size */
    const LABEL_SIZE_A4 = 'A4';

    /** @var string Use if want to get label A6 size */
    const LABEL_SIZE_A6 = 'A6P';

    /** @var string Use if want to get label in PDF format */
    const LABEL_FILE_FORMAT_PDF = 'pdf';

    /** @var string Use if want to  */
    const LABEL_FILE_FORMAT_ZPL = 'zpl';

    /** @var string Api token */
    private $token;

    /** @var Zend_Http_Client */
    private $apiClient;

    /** @var string InPost API Endpoint */
    private $apiEndpoint;

    /** @var string InPost merchant email */
    private $merchantEmail;

    /** @var array Allowed to use parcel sizes */
    protected static $allowedParcelSizes = array(
        'A',
        'B',
        'C'
    );

    /**
     * Inpost_Api_Client constructor.
     *
     * If you did not fill $merchantEmail please use setMerchantEmail($email)
     *
     * @param
     *            $token
     * @param string $apiEndpoint
     * @param string $merchantEmail
     */
    public function __construct($token, $apiEndpoint = self::PRODUCTION_API_ENDPOINT, $merchantEmail = '')
    {
        $this->token = $token;
        $this->apiEndpoint = $apiEndpoint;
        $this->merchantEmail = $merchantEmail;
        $this->apiClient = new Zend_Http_Client($apiEndpoint, array(
            'maxredirects' => 0,
            'timeout' => 300
        ));
    }

    /**
     * Set InPost API token
     *
     * @param
     *            $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Set InPost API endpoint.
     * Please use one of the following const: Inpost_Api_Client::PRODUCTION_API_ENDPOINT or Inpost_Api_Client::SANDBOX_API_ENDPOINT
     *
     * @param $apiEndpoint
     * @return $this
     */
    public function setEndpoint($apiEndpoint) {
        if ($apiEndpoint != self::PRODUCTION_API_ENDPOINT && $apiEndpoint != self::SANDBOX_API_ENDPOINT) {
            Throw new InvalidArgumentException('Wrong InPost API endpoint provided');
        }
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    /**
     * Set InPost API merchant email.
     *
     * @param
     *            $email
     * @return $this
     */
    public function setMerchantEmail($email)
    {
        $this->merchantEmail = $email;
        return $this;
    }

    /**
     * Validate InPost API account by merchantEmail and token
     *
     * @return bool
     */
    public function validateAccount()
    {
        try {
            $this->getCustomerParcels('sender', 1, 1);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve customer's created parcels by merchantEmail
     * By using $page and $limitPerPage you can filter results, count of parcels etc.
     *
     * @param string $as
     * @param int $page
     * @param int $limitPerPage
     * @return string
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getCustomerParcels($as = 'sender', $page = 1, $limitPerPage = 100)
    {
        $path = "/customers/{$this->merchantEmail}/parcels";
        $params = array(
            'as' => $as,
            'page' => $page,
            'per_page' => $limitPerPage
        );
        $response = $this->getFromEndpoint($path, $params);
        return $response;
    }

    /**
     * Retrieve label of return parcel by Return ID
     * Can return ZPL or PDF string
     * Use $returnPng = true to return PNG image string.
     * Require Imagick installed on you server.
     *
     * @param
     *            $returnId
     * @param string $fileType
     * @param string $labelSize
     * @param bool $returnPng
     * @return string
     * @throws ImagickException
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getReturnLabel($returnId, $fileType = self::LABEL_FILE_FORMAT_PDF, $labelSize = self::LABEL_SIZE_A6, $returnPng = false)
    {
        $path = "/returns/{$returnId}/sticker";
        $params = array(
            'sticker_format' => ucfirst($fileType)
        );
        $label = $this->getFromEndpoint($path, $params, true);
        if ($fileType === self::LABEL_FILE_FORMAT_PDF) {
            switch ($labelSize) {
                case self::LABEL_SIZE_A6:
                    $pdf = new TCPDI('L', 'mm', 'LETTER');
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                    $pdf->AddPage('P', 'A6');
                    $pdf->setSourceData($label);
                    $page = $pdf->ImportPage(1);
                    $pdf->useTemplate($page, - 10, 10, 210, 280, false);
                    $pdf->Rect(0, 140, 200, 200, 'DF', array(
                        'L' => 0,
                        'T' => 0,
                        'R' => 0,
                        'B' => 0
                    ), array(
                        255,
                        255,
                        255
                    ));
                    $label = $pdf->Output("$returnId.pdf", 's');
                    break;
            }
            if ($returnPng) {
                $image = new Imagick();
                $image->setResolution(300, 300);
                $image->readImageBlob($label);
                $image->setImageBackgroundColor('white');
                $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                $image->setImageFormat("png");
                $label = $image->getImageBlob();
            }
        }
        return $label;
    }

    /**
     * Create Return parcel
     *
     *
     * @param
     *            $senderPhone
     * @param
     *            $senderEmail
     * @param
     *            $parcelSize
     * @param array $targetAddress
     *            $targetAddress example: {"company_name":"InPost UK Ltd","city":"Hackney","street":"74 Rivington st","first_name":"John","last_name":"Doe","post_code":"EC2A 3AY","building_no":"The Office Group - Black & White","province":"","phone":"7712345678","email":"john.doe@inpost.co.uk"}
     * @param bool $customerReference
     * @param string $expirationDate
     * @return Inpost_Models_Parcel
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function createReturn($senderPhone, $senderEmail, $parcelSize, $targetAddress = array(), $customerReference = false, $expirationDate = '')
    {
        if (! in_array(strtoupper($parcelSize), self::$allowedParcelSizes, false)) {
            Throw new Inpost_Exception('createReturn: Parcel size is not valid. Allowed: A, B, C.');
        }

        $parcel = new Inpost_Models_Parcel();
        $path = "customers/{$this->merchantEmail}/returns";

        $senderPhone = $parcel->preparePhone($senderPhone);

        // set expiration date to 2 years ahead by default
        if (! strlen($expirationDate))
            $expirationDate = date("Y-m-d", strtotime("+725 days"));

        // set target address if present
        if (count($targetAddress)) {
            $targetAddress = $parcel->prepareTargetAddress($targetAddress);

            if (! $parcel->validateTargetAddress($targetAddress))
                throw new Exception("Destination address isn't formatted correctly");

            $params = array(
                'sender_phone' => $parcel->preparePhone($senderPhone),
                'sender_email' => $senderEmail,
                'attach_sticker' => false,
                'parcel' => array(
                    'size' => strtoupper($parcelSize)
                ),
                'code' => array(
                    'expires_at' => $expirationDate
                ),
                'target_address' => $targetAddress
            );
        } else {
            $params = array(
                'sender_phone' => $parcel->preparePhone($senderPhone),
                'sender_email' => $senderEmail,
                'attach_sticker' => false,
                'parcel' => array(
                    'size' => strtoupper($parcelSize)
                ),
                'code' => array(
                    'expires_at' => $expirationDate
                )
            );
        }

        if ($customerReference) {
            $params['rma'] = $customerReference;
        }

        $response = (array) $this->postOnEndpoint($path, $params);
        $parcel->addData($response);
        if (! array_key_exists('_embedded', $parcel->getData()) || ! array_key_exists('return_parcel', $parcel['_embedded']) || ! isset($parcel['_embedded']['return_parcel']->id)) {
            Throw new Inpost_Exception('Packcode is empty, check carrier configuration within InPost account.');
        }
        return $parcel;
    }

    /**
     * Retrieve outbound label string by Parcel ID
     * Can return ZPL or PDF string
     * Use $returnPng = true to return PNG image string.
     * Require Imagick installed on you server.
     *
     * @param
     *            $parcelId
     * @param string $fileType
     * @param string $labelSize
     * @param bool $returnPng
     * @return string
     * @throws ImagickException
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getOutboundLabel($parcelId, $fileType = self::LABEL_FILE_FORMAT_PDF, $labelSize = self::LABEL_SIZE_A6, $returnPng = false)
    {
        $path = "/parcels/{$parcelId}/sticker";
        $params = array(
            'sticker_format' => ucfirst($fileType),
            'type' => $labelSize
        );

        $label = $this->getFromEndpoint($path, $params, true);
        if ($returnPng) {
            $image = new Imagick();
            $image->setResolution(300, 299);
            $image->readImageBlob($label);
            $image->setImageBackgroundColor('white');
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageFormat("png");
            $label = $image->getImageBlob();
        }

        return $label;
    }

    /**
     * Retrieve parcel data by Parcel ID
     *
     * @param
     *            $parcelId
     * @return string
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getParcelData($parcelId)
    {
        $path = "/parcels/{$parcelId}";
        return $this->getFromEndpoint($path, array());
    }

    /**
     * Pay for parcel by Parcel ID.
     * Return true if success.
     *
     * @param
     *            $parcelId
     * @return bool
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function pay($parcelId)
    {
        $path = "/parcels/{$parcelId}/pay";
        $response = $this->postOnEndpoint($path);
        if ($response->status === 'prepared') {
            return true;
        }
        return false;
    }

    /**
     * Create Parcel
     * $weight in grams
     *
     * @param
     *            $receiverPhone
     * @param
     *            $machineId
     * @param
     *            $size
     * @param
     *            $weight
     * @param
     *            $receiverEmail
     * @return Inpost_Models_Parcel
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function createParcel($receiverPhone, $machineId, $size, $weight, $receiverEmail = false, $customerReference = false)
    {
        if (! in_array(strtoupper($size), self::$allowedParcelSizes, false)) {
            Throw new Inpost_Exception('createParcel: Parcel size is not valid. Allowed: A, B, C.');
        }

        $parcel = new Inpost_Models_Parcel();
        $path = "customers/{$this->merchantEmail}/parcels";
        $receiverPhone = $parcel->preparePhone($receiverPhone);

        if (strlen($receiverPhone) != 10)
            Throw new Inpost_Exception('Receiver number is not valid');

        $params = array(
            'receiver_phone' => $receiverPhone,
            'target_machine_id' => $machineId,
            'size' => strtoupper($size),
            'weight' => $weight
        );
        if ($receiverEmail)
            $params['receiver_email'] = $receiverEmail;

        if ($customerReference) {
            $params['customer_reference'] = $customerReference;
        }

        $response = $this->postOnEndpoint($path, $params);

        $body = (array) $response;

        $parcel->addData($body);

        return $parcel;
    }

    /**
     * Retrieve list of all machine/lockers in UK
     *
     * @return array
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getMachinesList()
    {
        $path = 'machines';
        $machineList = array();
        $response = $this->getFromEndpoint($path);
        if (is_object($response)) {
            if (property_exists($response, '_embedded')) {
                if (property_exists($response->_embedded, 'machines')) {
                    $machinesListJson = $response->_embedded->machines;
                    foreach ($machinesListJson as $machineArray) {
                        $object = new Inpost_Models_Machine();
                        $object->addData((array) $machineArray);
                        $machineList[] = $object;
                    }
                }
            }
        }

        return $machineList;
    }

    /**
     * Get request to InPost API
     *
     * @param
     *            $path
     * @param array $params
     * @param bool $returnJsonBody
     * @return mixed|string
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function getFromEndpoint($path, $params = array(), $returnJsonBody = false)
    {
        $this->apiClient->resetParameters();
        $this->apiClient->setUri($this->apiEndpoint . $path);
        $this->apiClient->setMethod(Zend_Http_Client::GET);
        $this->apiClient->setHeaders("Authorization", "Bearer {$this->token}");
        $this->apiClient->setParameterGet($params);
        $response = $this->apiClient->request();

        if ($response->getStatus() != 200)
            Throw new Inpost_Exception($response);

        if ($returnJsonBody) {
            return $response->getBody();
        }
        return json_decode($response->getBody());
    }

    /**
     * Post request to InPost API
     *
     * @param
     *            $path
     * @param array $params
     * @return mixed
     * @throws Inpost_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function postOnEndpoint($path, array $params = array())
    {
        $this->apiClient->setUri($this->apiEndpoint . $path);
        $this->apiClient->setMethod(Zend_Http_Client::POST);
        $this->apiClient->setHeaders("Authorization", "Bearer {$this->token}");
        $this->apiClient->setHeaders("Content-Type", "application/json");
        $this->apiClient->setRawData(json_encode($params));
        $response = $this->apiClient->request();

        if ($response->getStatus() != 201 && $response->getStatus() != 200)
            Throw new Inpost_Exception($response);

        return json_decode($response->getBody());
    }
}