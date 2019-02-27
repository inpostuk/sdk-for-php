<?php

/**
 * (c) InPost UK Ltd <it_support@inpost.co.uk>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by NMedia Systems Ltd, <info@nmediasystems.com>
 */

class Inpost_Exception extends Exception
{
    /**
     * Inpost_Exception constructor.
     * @param $message
     */
    public function __construct($message)
    {
        if (is_object($message)) {
            $exceptionMessage = $message->getMessage();
            if ($message->getStatus() === 422) {
                $response = (array)json_decode($message->getBody());
                if (array_key_exists('status_code', $response) && $response['status_code'] == 422) {
                    if (array_key_exists('message', $response) &&
                        array_key_exists('errors', $response) &&
                        count((array)$response['errors'])) {
                        $exceptionMessage = $response['message'] . ": ";
                        foreach ($response['errors'] as $key => $error) {
                            $exceptionMessage .= "$key ";
                        }
                    } else {
                        $exceptionMessage = $response['message'];
                    }
                }
            }
            parent::__construct($exceptionMessage, $message->getStatus());
        } else if (is_string($message)) {
            parent::__construct($message);
        }
    }
}