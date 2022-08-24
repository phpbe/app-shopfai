<be-head>
    <?php
    $wwwUrl = \Be\Be::getProperty('App.ShopFai')->getWwwUrl();
    ?>
    <script src="<?php echo $wwwUrl; ?>/js/user/popLogin.js"></script>
    <script>
        const userPopLogin_loginCheckUrl = "<?php echo beUrl('ShopFai.User.loginCheck'); ?>";
    </script>
</be-head>

<be-page-content>
    <div class="be-row">
        <div class="be-col-0 be-col-md-2 be-col-lg-4 be-col-xl-6">
        </div>
        <div class="be-col-24 be-col-md-20 be-col-lg-16 be-col-xl-10">
            <h4 class="be-h4">Login</h4>
            <form id="user-pop-login-form">
                <div class="be-floating be-mt-200">
                    <input type="text" name="email" class="be-input" placeholder="Email" />
                    <label class="be-floating-label">Email <span class="be-c-red">*</span></label>
                </div>

                <div class="be-floating be-mt-150">
                    <input type="password" name="password" class="be-input" placeholder="Password" />
                    <label class="be-floating-label">Password <span class="be-c-red">*</span></label>
                </div>

                <div class="be-mt-150 be-ta-right"><a href="<?php echo beUrl('ShopFai.User.forgotPassword'); ?>">Forgot Password?</a></div>

                <button type="submit" class="be-btn be-btn-lg be-mt-150">Login</button>
                <a href="<?php echo beUrl('ShopFai.User.register'); ?>" class="be-btn be-btn-outline be-btn-lg be-mt-150">Create Account</a>
            </form>
        </div>
    </div>
</be-page-content>