<be-head>
    <?php
    $wwwUrl = \Be\Be::getProperty('App.Shop')->getWwwUrl();
    ?>
    <link rel="stylesheet" href="<?php echo $wwwUrl; ?>/css/user-center/user-center.css" />

    <script>
        const userAddress_editShippingAddressSaveUrl = "<?php echo beUrl('Shop.UserAddress.editShippingAddressSave'); ?>";
        const userAddress_addressesUrl = "<?php echo beUrl('Shop.UserAddress.addresses'); ?>";
        const userAddress_shippingGetStateKeyValuesUrl = "<?php echo beUrl('Shop.Shipping.getStateKeyValues'); ?>";

        var stateId = "<?php echo $this->address ? $this->address->state_id : ''; ?>";
    </script>
    <script src="<?php echo $wwwUrl; ?>/js/user-address/edit-shipping-address.js"></script>
</be-head>


<be-page-content>
    <?php
    $countryKeyValues = \Be\Be::getService('App.Shop.Shipping')->getCountryIdNameKeyValues();
    ?>
    <h4 class="be-h4">
        <a href="<?php echo beURL('Shop.UserAddress.addresses') ;?>"><i class="bi-chevron-left"></i></a>
        <?php echo $this->address ? 'Edit Shipping Address' : 'Add a New Shipping Address'; ?>
    </h4>

    <form id="user-address-edit-shipping-address-form">
        <input type="hidden" name="id" value="<?php echo $this->address ? $this->address->id : ''; ?>" />

        <div class="be-row">
            <div class="be-col-24 be-md-col-11 be-mt-150">
                <div class="be-floating">
                    <input type="text" name="first_name" class="be-input" placeholder="First Name" value="<?php echo $this->address ? $this->address->first_name : ''; ?>">
                    <label class="be-floating-label">First Name <span class="be-c-red">*</span></label>
                </div>
            </div>
            <div class="be-col-0 be-md-col-2"></div>
            <div class="be-col-24 be-md-col-11 be-mt-150">
                <div class="be-floating">
                    <input type="text" name="last_name" class="be-input"  placeholder="Last Name" value="<?php echo $this->address ? $this->address->last_name : ''; ?>">
                    <label class="be-floating-label">Last Name <span class="be-c-red">*</span></label>
                </div>
            </div>
        </div>

        <div class="be-mt-150">
            <div class="be-floating">
                <input type="text" name="address" class="be-input" placeholder="Address" value="<?php echo $this->address ? $this->address->address : ''; ?>" />
                <label class="be-floating-label">Address <span class="be-c-red">*</span></label>
            </div>
        </div>

        <div class="be-mt-150">
            <div class="be-floating">
                <input type="text" name="address2" class="be-input" placeholder="Apartment, suite, etc. (optional)" value="<?php echo $this->address ? $this->address->address2 : ''; ?>">
                <label class="be-floating-label">Apartment, suite, etc. (optional)</label>
            </div>
        </div>

        <div class="be-mt-150">
            <div class="be-floating">
                <input type="text" name="city" class="be-input" placeholder="City" value="<?php echo $this->address ? $this->address->city : ''; ?>">
                <label class="be-floating-label">City <span class="be-c-red">*</span></label>
            </div>
        </div>

        <div class="be-mt-150">
            <div class="be-floating">
                <select name="country_id" id="country-id" class="be-select" onchange="updateState();">
                    <?php
                    foreach ($countryKeyValues as $key => $val) {
                        echo '<option value="' . $key . '"';
                        if ($this->address) {
                            if ($this->address->country_id == $key) {
                                echo ' selected';
                            }
                        }
                        echo '>' . $val . '</option>';
                    }
                    ?>
                </select>
                <label class="be-floating-label">Country/Region <span class="be-c-red">*</span></label>
            </div>
        </div>

        <div class="be-mt-150 shipping-address-state" style="display: none;">
            <div class="be-floating">
                <select name="state_id" id="state-id" class="be-select">
                    <option value="0">Select</option>
                </select>
                <label class="be-floating-label">State <span class="be-c-red">*</span></label>
            </div>
        </div>

        <div class="be-mt-150">
            <div class="be-floating">
                <input type="text" name="zip_code" class="be-input" placeholder="Zip code" value="<?php echo $this->address ? $this->address->zip_code : ''; ?>">
                <label class="be-floating-label">Zip code <span class="be-c-red">*</span></label>
            </div>
        </div>

        <div class="be-mt-150">
            <div class="be-floating">
                <input type="text" name="mobile" class="be-input" placeholder="Mobile phone number" value="<?php echo $this->address ? $this->address->mobile : ''; ?>">
                <label class="be-floating-label">Mobile phone number <span class="be-c-red">*</span></label>
            </div>
        </div>

        <div class="be-mt-150">
            <input class="be-checkbox" type="checkbox" value="1" id="is_default" <?php if($this->address  && $this->address->is_default) echo ' checked'; ?>>
            <label for="is_default">
                As Default
            </label>
        </div>

        <div class="be-row">
            <div class="be-col-24 be-md-col-11 be-lg-col-6 be-mt-150">
                <input type="submit" class="be-btn be-btn-major be-btn-lg be-w-100" value="Save">
            </div>
            <div class="be-col-0 be-md-col-2 be-lg-col-1"></div>
            <div class="be-col-24 be-md-col-11 be-lg-col-6 be-mt-150">
                <a href="<?php echo beURL('Shop.UserAddress.addresses') ;?>" class="be-btn be-btn-lg be-w-100">Back</a>
            </div>
        </div>

    </form>
</be-page-content>