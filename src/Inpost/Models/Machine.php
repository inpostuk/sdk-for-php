<?php

/**
 * (c) InPost UK Ltd <it_support@inpost.co.uk>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by NMedia Systems Ltd, <info@nmediasystems.com>
 */

/**
 * @method Inpost_Models_Machine getName()
 * @method Inpost_Models_Machine getType()
 * @method Inpost_Models_Machine getPostcode()
 * @method Inpost_Models_Machine getProvince()
 * @method Inpost_Models_Machine getStreet()
 * @method Inpost_Models_Machine getBuildingNumber()
 * @method Inpost_Models_Machine getTown()
 * @method Inpost_Models_Machine getLatitude()
 * @method Inpost_Models_Machine getLongitude()
 * @method Inpost_Models_Machine getOperatingHours()
 * @method Inpost_Models_Machine getLocationDescription()
 * @method Inpost_Models_Machine getPaymentPointDescription()
 * @method Inpost_Models_Machine getPartnerId()
 * @method Inpost_Models_Machine getPaymentType()
 *
 *
 * @method Inpost_Models_Machine setName()
 * @method Inpost_Models_Machine setType()
 * @method Inpost_Models_Machine setPostcode()
 * @method Inpost_Models_Machine setProvince()
 * @method Inpost_Models_Machine setStreet()
 * @method Inpost_Models_Machine setBuildingNumber()
 * @method Inpost_Models_Machine setTown()
 * @method Inpost_Models_Machine setLatitude()
 * @method Inpost_Models_Machine setLongitude()
 * @method Inpost_Models_Machine setOperatingHours()
 * @method Inpost_Models_Machine setLocationDescription()
 * @method Inpost_Models_Machine setPaymentPointDescription()
 * @method Inpost_Models_Machine setPartnerId()
 * @method Inpost_Models_Machine setPaymentType()
 *
 * @method Inpost_Models_Machine isPaymentAvailable()
 */

class Inpost_Models_Machine extends Varien_Object
{
    const TYPE_POK = 'POK';
    const TYPE_PACK_MACHINE = 'Pack Machine';

    const PAYMENT_TYPE_NONE = 0;
    const PAYMENT_TYPE_CASH = 1;
    const PAYMENT_TYPE_CARD = 2;


    /**
     * Machine object.
     *
     * @param $name
     * @param $type
     * @param $postcode
     * @param $province
     * @param $street
     * @param $buildingNumber
     * @param $town
     * @param $latitude
     * @param $longitude
     * @param $paymentAvailable
     * @param $operatingHours
     * @param $locationDescription
     * @param $paymentPointDescription
     * @param $partnerId
     * @param $paymentType
     */


    /**
     * Add data to the object.
     *
     * Retains previous data in the object.
     *
     * @param array $arr
     * @return Varien_Object
     */
    public function addData(array $arr)
    {
        $arr = $this->prepareData($arr);
        foreach ($arr as $index => $value) {
            $this->setData($index, $value);
        }
        return $this;
    }

    /**
     * Prepare Machine response data for return
     *
     * @param array $arr
     * @return array
     */
    protected function prepareData(array $arr)
    {
        if (array_key_exists('_links', $arr)) {
            $links = (array)$arr['_links'];
            unset($arr['_links']);
            if (array_key_exists('self', $links)) {
                $arr['self'] = $links['self']->href;
            }
            if (array_key_exists('minimap', $links)) {
                $arr['minimap'] = $links['minimap']->href;
            }
        }
        if (array_key_exists('address', $arr)) {
            $address = (array)$arr['address'];
            unset($arr['address']);
            $arr = array_merge($arr, $address);
        }
        if (array_key_exists('location', $arr)) {
            if (count($arr['location']) == 2) {
                $arr['latitude'] = $arr['location'][0];
                $arr['longitude'] = $arr['location'][1];
                $arr['location'] = json_encode($arr['location']);
            }
        }
        if (array_key_exists('functions', $arr)) {
            $arr['functions'] = json_encode($arr['functions']);
        }
        return $arr;
    }
}
