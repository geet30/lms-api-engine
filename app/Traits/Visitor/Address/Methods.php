<?php

namespace App\Traits\Visitor\Address;

use App\Models\{VisitorAddress, Lead};

/**
 * Visitor Address Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    /**
     * Add address.
     * Author: Sandeep Bangarh
     */
    static function addAddress($request, $visitor)
    {
        $data = $request->all();
        $addressData = $leadData = [];
        $serviceId = Lead::getService(true);
        if ($request->current_address_type == 1) {
            if ($serviceId != 2) {
                $savedAddress = self::select('state', 'postcode')->find($visitor->connection_address_id);

                $rules = $message = [];
                if (isset($data['current_state']) && $savedAddress->state != $data['current_state']) {
                    $rules['current_state'] = 'equal_to:' . trim($savedAddress->state);
                    $message['current_state.equal_to'] = 'Your selected address does not belong to ' . $savedAddress->state . '  state code';
                }
                if (isset($data['current_postcode']) && $savedAddress->postcode != $data['current_postcode']) {
                    $rules['current_postcode'] = 'equal_to:' . trim($savedAddress->postcode);
                    $message['current_postcode.equal_to'] = 'Your selected address does not belong to ' . $savedAddress->postcode . '  postcode';
                }

                if (!empty($rules)) {
                    return ['rules' => $rules, 'message' => $message];
                }
            }

            $addressData = static::setAddressData($data, $visitor, 'current');

            if ($addressData) {
                $addressData = self::updateOrCreate(['id' => $visitor->connection_address_id, 'address_type' => 1], $addressData);
                $leadData['connection_address_id'] = $addressData->id;
            }
        }

        if ($request->has('prev_conn_addr') && !empty($request->prev_conn_addr)) {
            static::setPreviousAddress($request, $visitor);
        }

        if ($request->has('billing_conn_addr') && !empty($request->billing_conn_addr)) {
            $addressData = static::setBillingAddress($request, $visitor);
            if ($addressData) {
                $leadData['billing_address_id'] = $addressData->id;
            }
        }

        if ($request->has('delivery_conn_addr') && !empty($request->delivery_conn_addr)) {
            $addressData = static::setDeliveryAddress($request, $visitor);
            if ($addressData) {
                $leadData['delivery_address_id'] = $addressData->id;
            }
        }

        $leadData['delivery_preference'] = request('delivery_preference', null);
        $leadData['billing_preference'] = request('billing_preference', null);
        $leadData['delivery_date'] = request('delivery_date', '');
        $leadData['australia_resident_status'] = request('australia_resident_status', 0);
        $leadData['delivery_instruction_details'] = request('delivery_instruction', '');

        return Lead::updateData(['lead_id' => $visitor->lead_id], $leadData);
    }

    /**
     * Set previous address.
     * Author: Sandeep Bangarh
     */
    static function setPreviousAddress($request, $visitor)
    {
        $prevAddressData = [];
        foreach ($request->prev_conn_addr as $address) {
            $prevData = static::setAddressData($address, $visitor, 'previous');
            if ($prevData) {
                $prevAddressData[] = $prevData;
            }
        }
        if (!empty($prevAddressData)) {
            self::where('visitor_id', $visitor->visitor_id)->where('address_type', 2)->delete();
            self::insert($prevAddressData);
        }
    }

    /**
     * Set billing address.
     * Author: Sandeep Bangarh
     */
    static function setBillingAddress($request, $visitor)
    {
        $billingAddressData = null;

        if ($request->filled('billing_preference') && $request->billing_preference == 3) {

            foreach ($request->billing_conn_addr as $address) {
                $billData = static::setAddressData($address, $visitor, 'billing');
                if ($billData) {
                    $billingAddressData[] = $billData;
                }
            }
            
            if (!empty($billingAddressData)) {
                $firstRow = end($billingAddressData);
                return self::updateOrCreate(['id' => $visitor->billing_address_id, 'address_type' => 3], $firstRow);
            }
        }
        return $billingAddressData;
    }

    /**
     * Set delivery address.
     * Author: Sandeep Bangarh
     */
    static function setDeliveryAddress($request, $visitor)
    {
        $deliveryAddressData = null;
        if ($request->filled('delivery_preference') && $request->delivery_preference == 2) {
            foreach ($request->delivery_conn_addr as $address) {
                $delData = static::setAddressData($address, $visitor, 'delivery');
                if ($delData) {
                    $deliveryAddressData[] = $delData;
                }
            }
            if (!empty($deliveryAddressData)) {
                $firstRow = end($deliveryAddressData);
                return self::updateOrCreate(['id' => $visitor->delivery_address_id, 'address_type' => 4], $firstRow);
            }
        }
        return $deliveryAddressData;
    }


    /**
     * Mapping address data.
     * Author: Sandeep Bangarh
     */
    static function setAddressData($data, $visitor, $type)
    {
        $visitorAddress = new VisitorAddress;
        $addressData = [];
        $needToSave = false;
        foreach ($visitorAddress->fillable as $fillable) {
            $addressData[$fillable] = null;
            if (isset($data[$type . '_' . $fillable])) {
                $needToSave = true;
                $addressData[$fillable] = $data[$type . '_' . $fillable];

                if ($type == 'previous')
                    $addressData['visitor_id'] = $visitor->visitor_id;
            }
        }
        return $needToSave ? $addressData : false;
    }
}
