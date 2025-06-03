<?php











function custom_redirect_pages()
{
    if (is_page('checkout') || is_page('prehlad-cenovej-ponuky')) {
        wp_redirect(site_url('/Poslat-dotaz/')); // Adjust the URL if necessary
        exit;
    }
}
add_action('template_redirect', 'custom_redirect_pages');

function decode_unicode_entities($string)
{
    // Use a regular expression to find unicode sequences like u010d and decode them
    return preg_replace_callback('/u([0-9a-fA-F]{4})/', function ($matches) {
        return mb_convert_encoding('&#' . hexdec($matches[1]) . ';', 'UTF-8', 'HTML-ENTITIES');
    }, $string);
}

function clear_the_cart()
{
    global $woocommerce;
    $woocommerce->cart->empty_cart();
}

// Register the 'Inquiry' custom post type
function create_inquiry_post_type()
{
    $args = array(
        'labels' => array(
            'name' => 'Inquiries',
            'singular_name' => 'Inquiry',
            'add_new' => 'Add New Inquiry',
            'add_new_item' => 'Add New Inquiry',
            'edit_item' => 'Edit Inquiry',
            'new_item' => 'New Inquiry',
            'view_item' => 'View Inquiry',
            'search_items' => 'Search Inquiries',
            'not_found' => 'No inquiries found',
            'not_found_in_trash' => 'No inquiries found in Trash',
            'all_items' => 'All Inquiries',
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-email',
        'supports' => array('title', 'editor'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'inquiries'),
        'show_in_rest' => true,
    );
    register_post_type('inquiry', $args);
}
add_action('init', 'create_inquiry_post_type');

// Save inquiry data to the dashboard
function save_inquiry_to_dashboard()
{
    if (!empty($_POST['groups']) && isset($_POST['name'], $_POST['email'], $_POST['description'], $_POST['total_price'])) {
        $inquiry_data = wp_json_encode($_POST['groups']);

        $post_id = wp_insert_post([
            'post_title' => sanitize_text_field($_POST['name']),
            'post_content' => $inquiry_data,
            'post_status' => 'publish',
            'post_type' => 'inquiry',
            'meta_input' => [
                'name' => sanitize_text_field($_POST['name']),
                'email' => sanitize_email($_POST['email']),
                'description' => sanitize_textarea_field($_POST['description']),
                'total_price' => sanitize_text_field($_POST['total_price']),
            ],
        ]);

        if ($post_id) {
            // Send email notification
            $to = 'info@smart-textil.sk'; // Or set to a custom email address
            $subject = 'New Inquiry Submitted';
            $user_name = sanitize_text_field($_POST['name']);
            $user_email = sanitize_email($_POST['email']);
            $description = sanitize_textarea_field($_POST['description']);
            $total_price = sanitize_text_field($_POST['total_price']);

            $groups = json_decode($inquiry_data, true);
            $message = "A new inquiry has been submitted:\n\n";
            $message .= "Name: $user_name\n";
            $message .= "Email: $user_email\n";
            $message .= "Description: $description\n";
            $message .= "Cena spolu: €$total_price\n\n";

            foreach ($groups as $group) {
                $message .= "Group: " . $group['name'] . " (€" . number_format($group['price'], 2) . ")\n";
                foreach ($group['products'] as $product) {
                    $message .= "- " . $product['name'] . " (Qty: " . $product['quantity'] . ", Price: €" . number_format($product['price'], 2) . ")\n";
                }
                if (!empty($group['graphics']['front'])) {
                    $message .= "  Front Graphics:\n";
                    foreach ($group['graphics']['front'] as $front) {
                        $message .= "    - $front\n";
                    }
                }
                if (!empty($group['graphics']['back'])) {
                    $message .= "  Back Graphics:\n";
                    foreach ($group['graphics']['back'] as $back) {
                        $message .= "    - $back\n";
                    }
                }
                $message .= "\n";
            }

            $headers = ['Content-Type: text/plain; charset=UTF-8'];

            wp_mail($to, $subject, $message, $headers);

            // Clear the cart after successful inquiry submission
            clear_the_cart();
            wp_send_json_success("Inquiry saved and email sent successfully!");
        } else {
            wp_send_json_error("Error saving inquiry.");
        }
    }
    wp_die();
}

add_action('wp_ajax_save_inquiry', 'save_inquiry_to_dashboard');
add_action('wp_ajax_nopriv_save_inquiry', 'save_inquiry_to_dashboard');

// Handle graphic uploads
function handle_graphic_upload()
{
    if (!empty($_FILES['graphic'])) {
        $file = $_FILES['graphic'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['file'])) {
            $file_url = $upload['url'];
            wp_send_json_success($file_url);
        } else {
            wp_send_json_error('Upload failed.');
        }
    } else {
        wp_send_json_error('No file uploaded.');
    }
}
add_action('wp_ajax_upload_graphic', 'handle_graphic_upload');
add_action('wp_ajax_nopriv_upload_graphic', 'handle_graphic_upload');

// Handle cart quantity updates
function handle_cart_quantity_update()
{
    if (isset($_POST['cart_key'], $_POST['quantity'])) {
        $cart_key = sanitize_text_field($_POST['cart_key']);
        $quantity = absint($_POST['quantity']);

        if ($quantity < 1) {
            wp_send_json_error('Invalid quantity');
        }

        $cart = WC()->cart->get_cart();

        if (isset($cart[$cart_key])) {
            WC()->cart->set_quantity($cart_key, $quantity);
            wp_send_json_success('Quantity updated successfully');
        } else {
            wp_send_json_error('Cart item not found');
        }
    } else {
        wp_send_json_error('Missing required parameters');
    }
}
add_action('wp_ajax_update_cart_quantity', 'handle_cart_quantity_update');
add_action('wp_ajax_nopriv_update_cart_quantity', 'handle_cart_quantity_update');

// Handle cart item removal
function handle_cart_item_removal()
{
    if (isset($_POST['cart_key'])) {
        $cart_key = sanitize_text_field($_POST['cart_key']);

        $cart = WC()->cart->get_cart();

        if (isset($cart[$cart_key])) {
            WC()->cart->remove_cart_item($cart_key);
            wp_send_json_success('Item removed successfully');
        } else {
            wp_send_json_error('Cart item not found');
        }
    } else {
        wp_send_json_error('Missing cart key');
    }
}
add_action('wp_ajax_remove_cart_item', 'handle_cart_item_removal');
add_action('wp_ajax_nopriv_remove_cart_item', 'handle_cart_item_removal');

// Shortcode for the cart grouping form
function custom_cart_grouping_shortcode()
{
    ob_start();
    ?>
    <style>
        #grouping-form {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .cart-item img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
        }

        .group-box {
            margin-top: 10px;
            padding: 10px;
            background: #e3e3e3;
            border-radius: 5px;
        }

        #total-price {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }

        .print-options {
            margin-top: 10px;
        }

        .print-options label {
            display: block;
            margin: 5px 0;
        }

        .print-options-display {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .empty-cart-message {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>

    <?php if (WC()->cart->is_empty()): ?>
        <div class="empty-cart-message">
            <p>Nemáte žiadne produkty v košíku. <a href="<?php echo wc_get_page_permalink('shop'); ?>">Pokračovať v nákupe</a>
            </p>
        </div>
    <?php else: ?>
        <h3></h3>
        <form id="grouping-form">
            <div class="item-section">
                <div id="cart-items">
                    <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item):
                        // Get print options from the cart item
                        $front_print = '';
                        $back_print = '';
                        if (isset($cart_item['tmcartepo'])) {
                            foreach ($cart_item['tmcartepo'] as $epo) {
                                if ($epo['section_label'] === 'Potlač prednej strany') {
                                    $front_print = $epo['value']; // Front print option
                                }
                                if ($epo['section_label'] === 'Potlač zadnej strany') {
                                    $back_print = $epo['value']; // Back print option
                                }
                            }
                        }
                        ?>
                        <div class="cart-item" data-key="<?= esc_attr($cart_item_key); ?>"
                            data-name="<?= esc_attr($cart_item['data']->get_name()); ?>"
                            data-price="<?= esc_attr($cart_item['data']->get_price()); ?>"
                            data-quantity="<?= esc_attr($cart_item['quantity']); ?>"
                            data-product-url="<?= esc_attr(get_permalink($cart_item['product_id'])); ?>"
                            data-image="<?= esc_attr(get_the_post_thumbnail_url($cart_item['product_id'])); ?>"
                            data-front-print="<?= esc_attr($front_print); ?>" data-back-print="<?= esc_attr($back_print); ?>">
                            <input type="checkbox" name="group_products[]" value="<?= esc_attr($cart_item_key); ?>">
                            <img src="<?= get_the_post_thumbnail_url($cart_item['product_id']); ?>" alt="Product Image">
                            <div class="product-info">
                                <a href="<?php echo get_permalink($cart_item['product_id']); ?>">
                                    <span class="product-name"><?= $cart_item['data']->get_name(); ?></span>
                                </a>
                                <div class="quantity-controls" style="margin: 5px 0;">
                                    <label>Množstvo: </label>
                                    <button type="button" class="qty-decrease"
                                        data-key="<?= esc_attr($cart_item_key); ?>">-</button>
                                    <input type="number" class="qty-input" data-key="<?= esc_attr($cart_item_key); ?>"
                                        value="<?= $cart_item['quantity']; ?>" min="1"
                                        style="width: 50px; text-align: center; margin: 0 5px;">
                                    <button type="button" class="qty-increase"
                                        data-key="<?= esc_attr($cart_item_key); ?>">+</button>
                                    <button type="button" class="remove-item" data-key="<?= esc_attr($cart_item_key); ?>"
                                        style="margin-left: 10px; background: red; color: white; border: none; padding: 2px 8px; border-radius: 3px;">Odstrániť</button>
                                </div>
                                <div class="price-display">
                                    <span
                                        class="item-price">€<?= number_format($cart_item['data']->get_price() * $cart_item['quantity'], 2); ?></span>
                                </div>
                                <?php if ($front_print || $back_print): ?>
                                    <div class="print-options-display">
                                        <?php if ($front_print): ?>
                                            <div><strong>Potlač prednej strany:</strong> <?= esc_html($front_print); ?></div>
                                        <?php endif; ?>
                                        <?php if ($back_print): ?>
                                            <div><strong>Potlač zadnej strany:</strong> <?= esc_html($back_print); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <input type="text" id="group_name" placeholder="Zadajte názov skupiny">
                <button type="button" id="create-group">Vytvoriť skupinu</button>

                <div id="group-uploads"></div>
            </div>

            <div class="details-section">
                <p id="total-price">Cena spolu: €0.00</p>

                <h4>Vaše údaje</h4>
                <input type="text" required id="user_name" placeholder="Meno" required>
                <input type="email" required id="user_email" placeholder="Email" required>
                <textarea id="user_description" placeholder=" Poznámky"></textarea>

                <button type="button" id="send-inquiry">Odoslať</button>
            </div>
        </form>
    <?php endif; ?>

    <script>
        jQuery(document).ready(function ($) {
            let groupedProducts = [];
            let totalPrice = 0;

            // Load saved groups from sessionStorage on page load
            function loadSavedGroups() {
                const savedGroups = sessionStorage.getItem('cart_groups');
                if (savedGroups) {
                    try {
                        groupedProducts = JSON.parse(savedGroups);
                        displaySavedGroups();
                        updateTotalPrice();
                    } catch (e) {
                        console.error('Error loading saved groups:', e);
                        sessionStorage.removeItem('cart_groups');
                    }
                }
            }

            // Save groups to sessionStorage
            function saveGroups() {
                sessionStorage.setItem('cart_groups', JSON.stringify(groupedProducts));
            }

            // Display saved groups
            function displaySavedGroups() {
                groupedProducts.forEach(group => {
                    let groupHtml = `<div class="group-box" data-group-name="${group.name}">
                    <h4>${group.name} (€${group.price.toFixed(2)}) 
                        <button type="button" class="remove-group" data-group="${group.name}" style="margin-left: 10px; background: red; color: white; border: none; padding: 2px 8px; border-radius: 3px; cursor: pointer;">Zrušiť skupinu</button>
                    </h4>
                    <div class="group-products">
                        ${group.products.map(product => `
                            <div class="group-product-item" style="margin: 5px 0; padding: 5px; background: #f0f0f0; border-radius: 3px;">
                                <span>${product.name} x ${product.quantity} - €${(product.price * product.quantity).toFixed(2)}</span>
                                <button type="button" class="remove-product-from-group" data-group="${group.name}" data-product-name="${product.name}" style="margin-left: 10px; background: #ff6b6b; color: white; border: none; padding: 1px 6px; border-radius: 2px; cursor: pointer; font-size: 12px;">Odstrániť</button>
                            </div>
                        `).join('')}
                    </div>
                    <div class="upload-section">
                        <label>Potlač prednej strany:</label>
                        <input type="file" class="upload-graphic" data-group="${group.name}" data-print-type="front">
                        <label>Potlač zadnej strany:</label>
                        <input type="file" class="upload-graphic" data-group="${group.name}" data-print-type="back">
                    </div>
                </div>`;
                    $("#group-uploads").append(groupHtml);

                    // Remove grouped products from cart display
                    group.products.forEach(product => {
                        $(`.cart-item[data-name="${product.name}"]`).remove();
                    });
                });
            }

            function updateTotalPrice() {
                totalPrice = groupedProducts.reduce((sum, group) => sum + group.price, 0);
                $("#total-price").text("Cena spolu: €" + totalPrice.toFixed(2));
            }

            // Handle quantity changes
            $(document).on('click', '.qty-increase', function () {
                let cartKey = $(this).data('key');
                let qtyInput = $(`.qty-input[data-key="${cartKey}"]`);
                let currentQty = parseInt(qtyInput.val());
                let newQty = currentQty + 1;

                updateCartQuantity(cartKey, newQty);
            });

            $(document).on('click', '.qty-decrease', function () {
                let cartKey = $(this).data('key');
                let qtyInput = $(`.qty-input[data-key="${cartKey}"]`);
                let currentQty = parseInt(qtyInput.val());

                if (currentQty > 1) {
                    let newQty = currentQty - 1;
                    updateCartQuantity(cartKey, newQty);
                }
            });

            $(document).on('change', '.qty-input', function () {
                let cartKey = $(this).data('key');
                let newQty = parseInt($(this).val());

                if (newQty >= 1) {
                    updateCartQuantity(cartKey, newQty);
                } else {
                    $(this).val(1);
                }
            });

            // Handle item removal
            $(document).on('click', '.remove-item', function () {
                if (confirm('Naozaj chcete odstrániť túto položku z košíka?')) {
                    let cartKey = $(this).data('key');
                    removeCartItem(cartKey);
                }
            });

            // Handle group removal
            $(document).on('click', '.remove-group', function () {
                if (confirm('Naozaj chcete odstrániť túto skupinu? Produkty budú vrátené do košíka.')) {
                    let groupName = $(this).data('group');
                    removeGroup(groupName);
                }
            });

            // Handle product removal from group
            $(document).on('click', '.remove-product-from-group', function () {
                if (confirm('Odstrániť tento produkt zo skupiny? Bude vrátený do košíka.')) {
                    let groupName = $(this).data('group');
                    let productName = $(this).data('product-name');
                    removeProductFromGroup(groupName, productName);
                }
            });

            // Update cart quantity via AJAX
            function updateCartQuantity(cartKey, quantity) {
                $.post("<?= admin_url('admin-ajax.php'); ?>", {
                    action: "update_cart_quantity",
                    cart_key: cartKey,
                    quantity: quantity
                }, function (response) {
                    if (response.success) {
                        // Update the display
                        let cartItem = $(`.cart-item[data-key="${cartKey}"]`);
                        let price = parseFloat(cartItem.data('price'));
                        let newTotal = price * quantity;

                        cartItem.data('quantity', quantity);
                        cartItem.find('.qty-input').val(quantity);
                        cartItem.find('.item-price').text('€' + newTotal.toFixed(2));
                    } else {
                        alert('Error updating quantity: ' + response.data);
                    }
                });
            }

            // Remove cart item via AJAX
            function removeCartItem(cartKey) {
                $.post("<?= admin_url('admin-ajax.php'); ?>", {
                    action: "remove_cart_item",
                    cart_key: cartKey
                }, function (response) {
                    if (response.success) {
                        $(`.cart-item[data-key="${cartKey}"]`).remove();

                        // Check if cart is empty
                        if ($('#cart-items .cart-item').length === 0) {
                            location.reload(); // Reload to show empty cart message
                        }
                    } else {
                        alert('Error removing item: ' + response.data);
                    }
                });
            }

            // Remove entire group
            function removeGroup(groupName) {
                // Find the group and restore products to cart
                let groupIndex = groupedProducts.findIndex(g => g.name === groupName);
                if (groupIndex !== -1) {
                    let group = groupedProducts[groupIndex];

                    // Create cart items HTML for restored products
                    group.products.forEach(product => {
                        let cartItemHtml = `<div class="cart-item" 
                         data-key="restored_${Date.now()}_${Math.random()}"
                         data-name="${product.name}"
                         data-price="${product.price}"
                         data-quantity="${product.quantity}"
                         data-product-url="${product.product_url}"
                         data-image="${product.image}"
                         data-front-print="${product.print_options.front}"
                         data-back-print="${product.print_options.back}">
                        <input type="checkbox" name="group_products[]" value="restored_${Date.now()}">
                        <img src="${product.image}" alt="Product Image">
                        <div class="product-info">
                            <a href="${product.product_url}">
                                <span class="product-name">${product.name}</span>
                            </a>
                            <div class="quantity-controls" style="margin: 5px 0;">
                                <label>Množstvo: </label>
                                <button type="button" class="qty-decrease">-</button>
                                <input type="number" class="qty-input" value="${product.quantity}" min="1" style="width: 50px; text-align: center; margin: 0 5px;">
                                <button type="button" class="qty-increase">+</button>
                                <button type="button" class="remove-item" style="margin-left: 10px; background: red; color: white; border: none; padding: 2px 8px; border-radius: 3px;">Odstrániť</button>
                            </div>
                            <div class="price-display">
                                <span class="item-price">€${(product.price * product.quantity).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>`;
                        $('#cart-items').append(cartItemHtml);
                    });

                    // Remove group from array and UI
                    groupedProducts.splice(groupIndex, 1);
                    $(`.group-box[data-group-name="${groupName}"]`).remove();

                    updateTotalPrice();
                    saveGroups();
                }
            }

            // Remove product from group
            function removeProductFromGroup(groupName, productName) {
                let group = groupedProducts.find(g => g.name === groupName);
                if (group) {
                    let productIndex = group.products.findIndex(p => p.name === productName);
                    if (productIndex !== -1) {
                        let product = group.products[productIndex];

                        // Create cart item HTML for restored product
                        let cartItemHtml = `<div class="cart-item" 
                         data-key="restored_${Date.now()}_${Math.random()}"
                         data-name="${product.name}"
                         data-price="${product.price}"
                         data-quantity="${product.quantity}"
                         data-product-url="${product.product_url}"
                         data-image="${product.image}"
                         data-front-print="${product.print_options.front}"
                         data-back-print="${product.print_options.back}">
                        <input type="checkbox" name="group_products[]" value="restored_${Date.now()}">
                        <img src="${product.image}" alt="Product Image">
                        <div class="product-info">
                            <a href="${product.product_url}">
                                <span class="product-name">${product.name}</span>
                            </a>
                            <div class="quantity-controls" style="margin: 5px 0;">
                                <label>Množstvo: </label>
                                <button type="button" class="qty-decrease">-</button>
                                <input type="number" class="qty-input" value="${product.quantity}" min="1" style="width: 50px; text-align: center; margin: 0 5px;">
                                <button type="button" class="qty-increase">+</button>
                                <button type="button" class="remove-item" style="margin-left: 10px; background: red; color: white; border: none; padding: 2px 8px; border-radius: 3px;">Odstrániť</button>
                            </div>
                            <div class="price-display">
                                <span class="item-price">€${(product.price * product.quantity).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>`;
                        $('#cart-items').append(cartItemHtml);

                        // Remove product from group
                        group.price -= product.price * product.quantity;
                        group.products.splice(productIndex, 1);

                        // Update group display
                        let groupBox = $(`.group-box[data-group-name="${groupName}"]`);
                        groupBox.find('h4').html(`${groupName} (€${group.price.toFixed(2)}) 
                        <button type="button" class="remove-group" data-group="${groupName}" style="margin-left: 10px; background: red; color: white; border: none; padding: 2px 8px; border-radius: 3px; cursor: pointer;">Zrušiť skupinu</button>`);

                        // Update products display in group
                        let productsHtml = group.products.map(p => `
                        <div class="group-product-item" style="margin: 5px 0; padding: 5px; background: #f0f0f0; border-radius: 3px;">
                            <span>${p.name} x ${p.quantity} - €${(p.price * p.quantity).toFixed(2)}</span>
                            <button type="button" class="remove-product-from-group" data-group="${groupName}" data-product-name="${p.name}" style="margin-left: 10px; background: #ff6b6b; color: white; border: none; padding: 1px 6px; border-radius: 2px; cursor: pointer; font-size: 12px;">Odstrániť</button>
                        </div>
                    `).join('');
                        groupBox.find('.group-products').html(productsHtml);

                        // If group is empty, remove it
                        if (group.products.length === 0) {
                            let groupIndex = groupedProducts.findIndex(g => g.name === groupName);
                            if (groupIndex !== -1) {
                                groupedProducts.splice(groupIndex, 1);
                                groupBox.remove();
                            }
                        }

                        updateTotalPrice();
                        saveGroups();
                    }
                }
            }

            $("#create-group").on("click", function () {
                let selected = $("input[name='group_products[]']:checked");
                let groupName = $("#group_name").val().trim();

                if (selected.length === 0) {
                    alert("Prosím, vyberte aspoň jeden produkt pre zoskupenie.");
                    return;
                }

                if (!groupName) {
                    alert(" Prosím, zadajte názov skupiny.");
                    return;
                }

                let group = {
                    name: groupName,
                    products: [],
                    graphics: { front: [], back: [] },
                    price: 0
                };

                selected.each(function () {
                    let cartItem = $(this).closest(".cart-item");

                    // Extract data from data attributes
                    let productName = cartItem.data('name');
                    let productPrice = parseFloat(cartItem.data('price'));
                    let productQuantity = parseInt(cartItem.data('quantity'));
                    let productUrl = cartItem.data('product-url');
                    let productImage = cartItem.data('image');
                    let frontPrint = cartItem.data('front-print') || '';
                    let backPrint = cartItem.data('back-print') || '';

                    let productData = {
                        name: productName,
                        price: productPrice,
                        quantity: productQuantity,
                        product_url: productUrl,
                        image: productImage,
                        print_options: {
                            front: frontPrint,
                            back: backPrint
                        }
                    };

                    group.products.push(productData);
                    group.price += productPrice * productQuantity;

                    // Remove the cart item from the display
                    cartItem.remove();
                });

                console.log('Created group:', group); // Debug log

                let groupHtml = `<div class="group-box" data-group-name="${groupName}">
                <h4>${groupName} (€${group.price.toFixed(2)}) 
                    <button type="button" class="remove-group" data-group="${groupName}" style="margin-left: 10px; background: red; color: white; border: none; padding: 2px 8px; border-radius: 3px; cursor: pointer;">Zrušiť skupinu</button>
                </h4>
                <div class="group-products">
                    ${group.products.map(product => `
                        <div class="group-product-item" style="margin: 5px 0; padding: 5px; background: #f0f0f0; border-radius: 3px;">
                            <span>${product.name} x ${product.quantity} - €${(product.price * product.quantity).toFixed(2)}</span>
                            <button type="button" class="remove-product-from-group" data-group="${groupName}" data-product-name="${product.name}" style="margin-left: 10px; background: #ff6b6b; color: white; border: none; padding: 1px 6px; border-radius: 2px; cursor: pointer; font-size: 12px;">Odstrániť</button>
                        </div>
                    `).join('')}
                </div>
                <div class="upload-section">
                    <label>Potlač prednej strany:</label>
                    <input type="file" class="upload-graphic" data-group="${groupName}" data-print-type="front">
                    <label>Potlač zadnej strany:</label>
                    <input type="file" class="upload-graphic" data-group="${groupName}" data-print-type="back">
                </div>
            </div>`;

                $("#group-uploads").append(groupHtml);
                groupedProducts.push(group);
                updateTotalPrice();
                saveGroups(); // Save after creating group
                $("#group_name").val("");
            });

            $(document).on("change", ".upload-graphic", function () {
                let groupName = $(this).data("group");
                let printType = $(this).data("print-type");
                let file = this.files[0];

                if (file) {
                    let formData = new FormData();
                    formData.append("action", "upload_graphic");
                    formData.append("graphic", file);
                    formData.append("group", groupName);
                    formData.append("print_type", printType);

                    $.ajax({
                        url: "<?= admin_url('admin-ajax.php'); ?>",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                groupedProducts.forEach(g => {
                                    if (g.name === groupName) {
                                        g.graphics[printType].push(response.data);
                                    }
                                });
                            } else {
                                alert("Nahrávanie zlyhalo: " + response.data);
                            }
                        },
                        error: function () {
                            alert("Nahrávanie zlyhalo. Skúste to prosím znova.");
                        }
                    });
                }
            });

            $("#send-inquiry").on("click", function () {
                // Validate required fields
                let userName = $("#user_name").val().trim();
                let userEmail = $("#user_email").val().trim();

                if (!userName) {
                    alert("Prosím, zadajte svoje meno.");
                    return;
                }

                if (!userEmail) {
                    alert("Prosím, zadajte svoj email.");
                    return;
                }

                if (groupedProducts.length === 0) {
                    alert("Prosím, vytvorte aspoň jednu skupinu produktov pred odoslaním dopytu.");
                    return;
                }

                console.log('Sending inquiry with groups:', groupedProducts); // Debug log

                $.post("<?= admin_url('admin-ajax.php'); ?>", {
                    action: "save_inquiry",
                    groups: groupedProducts,
                    name: userName,
                    email: userEmail,
                    description: $("#user_description").val(),
                    total_price: totalPrice
                }, function (response) {
                    console.log('Server response:', response); // Debug log

                    if (response.success) {
                        alert("Dopyt bol odoslaný!");
                        // Clear saved groups
                        sessionStorage.removeItem('cart_groups');
                        // Reload the page to show the empty cart message
                        location.reload();
                    } else {
                        alert("Chyba pri odosielaní dopytu: " + (response.data || 'Unknown error'));
                    }
                }).fail(function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert("Chyba pri odosielaní dopytu. Skúste to prosím znova.");
                });
            });

            // Initialize saved groups
            loadSavedGroups();
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('cart_grouping', 'custom_cart_grouping_shortcode');

// Meta box to display inquiry details in the admin panel
function custom_inquiry_meta_box()
{
    global $post;

    $inquiry_data = json_decode(get_post_field('post_content', $post->ID), true);
    $user_email = get_post_meta($post->ID, 'email', true);
    $user_name = get_post_meta($post->ID, 'name', true);
    $user_description = get_post_meta($post->ID, 'description', true);
    $total_price = get_post_meta($post->ID, 'total_price', true);

    echo '<div class="user-details">';
    echo '<h3>Vaše údaje</h3>';
    echo '<p><strong>Meno:</strong> ' . esc_html($user_name) . '</p>';
    echo '<p><strong>Email:</strong> ' . esc_html($user_email) . '</p>';
    echo '<p><strong>Poznámky:</strong> ' . esc_html($user_description) . '</p>';
    echo '<p><strong>Cena spolu:</strong> ' . wc_price($total_price) . '</p>';
    echo '</div>';

    if (!$inquiry_data) {
        echo "No inquiry data found.";
        return;
    }

    echo '<div id="accordion">';

    foreach ($inquiry_data as $index => $group) {
        echo '<h3 class="group-name" style="cursor:pointer;">' . esc_html(html_entity_decode($group['name'])) . ' - ' . wc_price($group['price']) . '</h3>';
        echo '<div class="group-content">';

        // Display Graphics
        if (!empty($group['graphics']['front'])) {
            echo '<h4>Potlač prednej strany</h4>';
            foreach ($group['graphics']['front'] as $graphic) {
                echo '<div class="graphic-item">';
                echo '<a href="' . esc_url($graphic) . '" target="_blank">View Front Graphic</a>';
                echo '<img src="' . esc_url($graphic) . '" alt="Front Graphic" style="max-width: 100px; display: block; margin-top: 10px;">';
                echo '</div>';
            }
        }

        if (!empty($group['graphics']['back'])) {
            echo '<h4>Potlač zadnej strany</h4>';
            foreach ($group['graphics']['back'] as $graphic) {
                echo '<div class="graphic-item">';
                echo '<a href="' . esc_url($graphic) . '" target="_blank">View Back Graphic</a>';
                echo '<img src="' . esc_url($graphic) . '" alt="Back Graphic" style="max-width: 100px; display: block; margin-top: 10px;">';
                echo '</div>';
            }
        }

        // Display Linked Products and Print Options
        echo '<h4>Products:</h4>';
        if (!empty($group['products'])) {
            foreach ($group['products'] as $product) {
                $product_name = strip_tags(decode_unicode_entities($product['name']));

                $quantity = isset($product['quantity']) ? $product['quantity'] : 1;

                echo '<p><a href="' . esc_url($product['product_url']) . '" target="_blank">';
                echo esc_html($product_name) . ' x ' . esc_html($quantity) . ' - ' . wc_price($product['price'] * $quantity);
                echo '</a></p>';

                // Display Print Options
                if (isset($product['print_options'])) {
                    echo '<div class="print-options-display">';
                    if (!empty($product['print_options']['front'])) {
                        echo '<div><strong>Potlač prednej strany:</strong> ' . esc_html($product['print_options']['front']) . '</div>';
                    }
                    if (!empty($product['print_options']['back'])) {
                        echo '<div><strong>Potlač zadnej strany:</strong> ' . esc_html($product['print_options']['back']) . '</div>';
                    }
                    echo '</div>';
                }
            }
        }

        echo '</div>'; // Close group content
    }

    echo '</div>'; // Close accordion

    ?>
    <script>
        jQuery(document).ready(function ($) {
            $(".group-name").on("click", function () {
                $(this).next(".group-content").slideToggle();
            });
        });
    </script>
    <?php
}
add_action('add_meta_boxes', function () {
    add_meta_box(
        'inquiry_groups',
        'Inquiry Details',
        'custom_inquiry_meta_box',
        'inquiry',
        'normal',
        'default'
    );
});

// Add custom styles for the admin panel
function custom_inquiry_styles()
{
    echo '<style>
        .user-details {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .user-details h3 {
            margin-top: 0;
        }
        #accordion h3 {
            background-color: #f4f4f4;
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
            font-size: 18px;
        }
        #accordion .group-content {
            padding: 15px;
            display: none;
            background-color: #f9f9f9;
            margin-bottom: 10px;
        }
        #accordion .group-content a {
            color: #ff5b22!important;
            font-size:16px;
            text-decoration:none;
            color:black;
        }
        .print-options {
            margin-top: 10px;
        }
        .print-options label {
            display: block;
            margin: 5px 0;
        }
    </style>';
}
add_action('admin_head', 'custom_inquiry_styles');

// Remove the default editor for the 'inquiry' post type
function remove_inquiry_editor()
{
    remove_post_type_support('inquiry', 'editor');
}
add_action('init', 'remove_inquiry_editor');