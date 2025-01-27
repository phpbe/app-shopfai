<be-head>
    <?php
    $wwwUrl = \Be\Be::getProperty('App.Shop')->getWwwUrl();
    ?>
    <link rel="stylesheet" href="<?php echo $wwwUrl; ?>/css/user-center/user-center.css" />

    <script>
        const userFavorite_deleteFavoriteUrl = "<?php echo beUrl('Shop.UserFavorite.deleteFavorite'); ?>";
    </script>
    <script src="<?php echo $wwwUrl; ?>/js/user-favorite/favorites.js"></script>
</be-head>


<be-page-content>

    <div class="be-d-block be-md-d-none">
        <h4 class="be-h4">
            <a href="<?php echo beURL('Shop.UserCenter.dashboard') ;?>"><i class="bi-chevron-left"></i></a>
            Wish List
        </h4>

        <?php
        if (count($this->products) > 0) {
            foreach ($this->products as $product) {
                ?>
                <div class="be-row be-mt-100">
                    <div style="width: 6rem;" class="be-table-image">
                        <a href="<?php echo $product->url; ?>">
                            <img src="<?php echo $product->image; ?>" alt="<?php echo $product->name; ?>">
                        </a>
                    </div>
                    <div class="be-col">
                        <a href="<?php echo $product->url; ?>">
                            <?php echo $product->name; ?>
                        </a>
                    </div>
                    <div style="width: 2rem;" class="be-ta-right">
                        <a class="be-table-delete" href="javascript:void(0);" onclick="deleteFavorite(<?php echo $product->id; ?>)"></a>
                    </div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="be-my-400 be-py-400 be-ta-center be-c-999">No records</div>
            <?php
        }
        ?>

        <?php
        $my = \Be\Be::getUser();
        if (substr($my->id, 0, 1) !== '-') {
        ?>
        <div class="be-mt-200">
            <a href="<?php echo beURL('Shop.UserCenter.dashboard') ;?>" class="be-btn be-btn-outline be-w-100">Back</a>
        </div>
        <?php
        }
        ?>
    </div>


    <div class="be-d-none be-md-d-block">
        <h4 class="be-h4">Wish List</h4>

        <table class="be-mt-200 be-table">
            <thead>
            <tr>
                <th></th>
                <th class="be-ta-left">Products</th>
                <th class="be-ta-center">Price</th>
                <th class="be-ta-center"></th>
            </tr>
            </thead>
            <?php
            if (count($this->products) > 0) {
                ?>
                <tbody>
                <?php
                foreach ($this->products as $product) {
                    ?>
                    <tr>
                        <td class="be-table-image">
                            <a href="<?php echo $product->url; ?>" target="_blank">
                                <img src="<?php echo $product->image; ?>" alt="<?php echo $product->name; ?>">
                            </a>
                        </td>
                        <td class="be-ta-left">
                            <a href="<?php echo $product->url; ?>" target="_blank">
                                <?php echo $product->name; ?>
                            </a>
                        </td>
                        <td class="be-ta-center">￥<?php echo $product->price; ?></td>
                        <td class="be-ta-center">
                            <a class="be-table-delete" href="javascript:void(0);" onclick="deleteFavorite(<?php echo $product->id; ?>)"></a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
                <?php
            } else {
                ?>
                <tbody>
                <tr>
                    <td class="be-table-no-record" colspan="4">No records</td>
                </tr>
                </tbody>
                <?php
            }
            ?>
        </table>

    </div>

</be-page-content>