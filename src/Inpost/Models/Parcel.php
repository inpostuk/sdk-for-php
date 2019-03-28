<?php

/**
 * (c) InPost UK Ltd <it_support@inpost.co.uk>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by NMedia Systems Ltd, <info@nmediasystems.com>
 */

/**
 * Class Parcel.
 *
 * Represents InPost parcel sent to a machine or a POP, picked up by a courier.
 */
class Inpost_Models_Parcel extends Varien_Object
{
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
        foreach ($arr as $index => $value) {
            if (is_object($value)) {
                $value = (array)$value;
            }
            $this->setData($index, $value);
        }
        return $this;
    }

    /**
     * Prepare phone for InPost API
     *
     * @param $phone
     * @return null|string|string[]
     */
    public function preparePhone($phone)
    {
        $phone = trim($phone);
        $phone = preg_replace('/(^\+44)|(^0044)|(^44)|(^0)|(\s+)/', '', $phone);
        return $phone;
    }

    public function prepareTargetAddress($targetAddress)
    {
        $targetAddress['phone'] = $this->preparePhone($targetAddress['phone']);
        $targetAddress['post_code'] = $this->preparePostcode($targetAddress['post_code']);

        if (array_key_exists('street1', $targetAddress)) $targetAddress['building_no'] = $targetAddress['street1'];
        if (array_key_exists('street2', $targetAddress)) $targetAddress['street'] = $targetAddress['street2'];

        return $targetAddress;
    }


    /**
     * Validate target address to check required fields
     *
     * @param $targetAddress
     * @return bool
     */
    public function validateTargetAddress($targetAddress)
    {
        return (is_array($targetAddress) &&
                array_key_exists('first_name', $targetAddress) &&
                array_key_exists('last_name', $targetAddress) &&
                array_key_exists('building_no', $targetAddress) &&
                array_key_exists('street', $targetAddress)) &&
            array_key_exists('city', $targetAddress) &&
            array_key_exists('post_code', $targetAddress) &&
            array_key_exists('phone', $targetAddress);
    }

    public function preparePostcode($postcode)
    {
        $postcode = str_replace(' ', '', $postcode);
        switch (strlen($postcode)) {
            case 5:
                $postcode = $this->stringInsert($postcode, ' ', 2);
                break;
            case 6:
                $postcode = $this->stringInsert($postcode, ' ', 3);
                break;
            case 7:
                $postcode = $this->stringInsert($postcode, ' ', 4);
                break;
        }

        return strtoupper($postcode);
    }

    protected function stringInsert($string, $insert, $pos)
    {
        return substr($string, 0, $pos) . $insert . substr($string, $pos);
    }
}