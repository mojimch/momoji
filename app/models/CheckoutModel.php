<?php

class CheckoutModel extends Model
{
    public function address(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT ca.*, sz.zone_name, sz.cost
             FROM customer_addresses ca
             LEFT JOIN shipping_zones sz ON sz.id = ca.shipping_zone_id
             WHERE ca.user_id = :user_id
             ORDER BY ca.is_primary DESC, ca.id DESC
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: null;
    }

    public function orderSummary(int $userId, string $invoice): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, invoice_number, recipient_name, shipping_method, shipping_cost,
                    discount_amount, subtotal, total_amount, status, payment_status, payment_method
             FROM orders
             WHERE invoice_number = :invoice AND user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['invoice' => $invoice, 'user_id' => $userId]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }
        $items = $this->db->prepare(
            'SELECT product_name, variant_name, quantity, unit_price, subtotal
             FROM order_items WHERE order_id = :order_id ORDER BY id'
        );
        $items->execute(['order_id' => (int) $order['id']]);
        $order['items'] = $items->fetchAll();
        return $order;
    }

    public function shippingZones(): array
    {
        $statement = $this->db->prepare(
            'SELECT id, zone_name, description, cost
             FROM shipping_zones
             WHERE is_active = 1
             ORDER BY cost ASC'
        );
        $statement->execute();
        return $statement->fetchAll();
    }

    public function saveAddress(int $userId, array $data): void
    {
        $existing = $this->address($userId);
        if ($existing) {
            $statement = $this->db->prepare(
                'UPDATE customer_addresses SET recipient_name=:recipient_name,
                 phone=:phone, address_line=:address_line, city=:city,
                 postal_code=:postal_code, shipping_zone_id=:shipping_zone_id,
                 is_primary=1
                 WHERE id=:id'
            );
            $statement->execute([
                'recipient_name' => trim($data['recipient_name']),
                'phone' => trim($data['phone']),
                'address_line' => trim($data['address_line']),
                'city' => trim($data['city']),
                'postal_code' => trim($data['postal_code']),
                'shipping_zone_id' => (int) $data['shipping_zone_id'] ?: null,
                'id' => (int) $existing['id'],
            ]);
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO customer_addresses
                 (user_id, recipient_name, phone, address_line, city, postal_code, shipping_zone_id, is_primary)
                 VALUES
                 (:user_id, :recipient_name, :phone, :address_line, :city, :postal_code, :shipping_zone_id, 1)'
            );
            $statement->execute([
                'user_id' => $userId,
                'recipient_name' => trim($data['recipient_name']),
                'phone' => trim($data['phone']),
                'address_line' => trim($data['address_line']),
                'city' => trim($data['city']),
                'postal_code' => trim($data['postal_code']),
                'shipping_zone_id' => (int) $data['shipping_zone_id'] ?: null,
            ]);
        }
    }

    public function validateVoucher(string $code, float $subtotal): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM vouchers
             WHERE code = :code AND is_active = 1
               AND (start_at IS NULL OR start_at <= NOW())
               AND (end_at IS NULL OR end_at >= NOW())
             LIMIT 1'
             . ' FOR UPDATE'
        );
        $statement->execute(['code' => strtoupper(trim($code))]);
        $voucher = $statement->fetch();
        if (!$voucher) {
            return ['valid' => false, 'message' => 'Voucher tidak ditemukan atau tidak aktif.', 'discount' => 0.0];
        }
        if ($voucher['quota'] !== null && (int) $voucher['used_count'] >= (int) $voucher['quota']) {
            return ['valid' => false, 'message' => 'Kuota voucher sudah habis.', 'discount' => 0.0];
        }
        if ($subtotal < (float) $voucher['minimum_purchase']) {
            return ['valid' => false, 'message' => 'Belanja belum memenuhi minimum voucher.', 'discount' => 0.0];
        }

        $discount = 0.0;
        if ($voucher['discount_type'] === 'percentage') {
            $discount = $subtotal * ((float) $voucher['discount_value'] / 100);
            if ($voucher['max_discount'] !== null) {
                $discount = min($discount, (float) $voucher['max_discount']);
            }
        } elseif ($voucher['discount_type'] === 'fixed') {
            $discount = (float) $voucher['discount_value'];
        }
        $discount = min($discount, $subtotal);
        return [
            'valid' => true,
            'voucher' => $voucher,
            'discount' => $discount,
            'free_shipping' => $voucher['discount_type'] === 'free_shipping',
            'message' => 'Voucher berlaku.',
        ];
    }

    public function zonesAdmin(): array
    {
        $statement = $this->db->prepare('SELECT * FROM shipping_zones ORDER BY zone_name');
        $statement->execute();
        return $statement->fetchAll();
    }

    public function zoneSave(array $data, ?int $id = null): void
    {
        if (trim($data['zone_name']) === '' || (float) $data['cost'] < 0) {
            throw new InvalidArgumentException('Nama zona wajib diisi dan ongkir tidak boleh negatif.');
        }
        if ($id) {
            $statement = $this->db->prepare(
                'UPDATE shipping_zones SET zone_name=:zone_name, description=:description,
                 cost=:cost, is_active=:is_active WHERE id=:id'
            );
            $statement->execute([
                'zone_name' => trim($data['zone_name']),
                'description' => trim($data['description']),
                'cost' => (float) $data['cost'],
                'is_active' => (int) $data['is_active'],
                'id' => $id,
            ]);
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO shipping_zones (zone_name, description, cost, is_active)
                 VALUES (:zone_name, :description, :cost, :is_active)'
            );
            $statement->execute([
                'zone_name' => trim($data['zone_name']),
                'description' => trim($data['description']),
                'cost' => (float) $data['cost'],
                'is_active' => (int) $data['is_active'],
            ]);
        }
    }

    public function zoneDelete(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM shipping_zones WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function voucherAdmin(): array
    {
        $statement = $this->db->prepare('SELECT * FROM vouchers ORDER BY created_at DESC');
        $statement->execute();
        return $statement->fetchAll();
    }

    public function voucherSave(array $data, ?int $id = null): void
    {
        $allowedTypes = ['percentage', 'fixed', 'free_shipping'];
        if (
            trim($data['code']) === ''
            || !in_array($data['discount_type'], $allowedTypes, true)
            || (float) $data['discount_value'] < 0
            || (float) $data['minimum_purchase'] < 0
            || ($data['max_discount'] !== '' && (float) $data['max_discount'] < 0)
            || ($data['quota'] !== '' && (int) $data['quota'] < 0)
        ) {
            throw new InvalidArgumentException('Data voucher tidak valid.');
        }

        $sql = $id
            ? 'UPDATE vouchers SET code=:code, description=:description, discount_type=:discount_type,
               discount_value=:discount_value, minimum_purchase=:minimum_purchase,
               max_discount=:max_discount, quota=:quota, start_at=:start_at, end_at=:end_at,
               is_active=:is_active WHERE id=:id'
            : 'INSERT INTO vouchers
               (code, description, discount_type, discount_value, minimum_purchase,
                max_discount, quota, start_at, end_at, is_active)
               VALUES
               (:code, :description, :discount_type, :discount_value, :minimum_purchase,
                :max_discount, :quota, :start_at, :end_at, :is_active)';
        $params = [
            'code' => strtoupper(trim($data['code'])),
            'description' => trim($data['description']),
            'discount_type' => $data['discount_type'],
            'discount_value' => (float) $data['discount_value'],
            'minimum_purchase' => (float) $data['minimum_purchase'],
            'max_discount' => $data['max_discount'] !== '' ? (float) $data['max_discount'] : null,
            'quota' => $data['quota'] !== '' ? (int) $data['quota'] : null,
            'start_at' => $data['start_at'] !== '' ? $data['start_at'] : null,
            'end_at' => $data['end_at'] !== '' ? $data['end_at'] : null,
            'is_active' => (int) $data['is_active'],
        ];
        if ($id) {
            $params['id'] = $id;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    public function voucherDelete(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM vouchers WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function createOrder(int $userId, array $items, array $data): array
    {
        $this->db->beginTransaction();
        try {
            $subtotal = 0.0;
            $lockedItems = [];

            $groupedItems = [];
            foreach ($items as $item) {
                $key = (int) $item['product_id'] . ':' . (int) ($item['variant_id'] ?: 0);
                if (!isset($groupedItems[$key])) {
                    $groupedItems[$key] = $item;
                } else {
                    $groupedItems[$key]['quantity'] += (int) $item['quantity'];
                }
            }

            foreach ($groupedItems as $item) {
                $productId = (int) $item['product_id'];
                $variantId = (int) ($item['variant_id'] ?: 0);
                $quantity = (int) $item['quantity'];

                $productStatement = $this->db->prepare(
                    'SELECT id, name, slug, price, promo_price, stock, weight_grams
                     FROM products
                     WHERE id = :id AND is_active = 1
                     FOR UPDATE'
                );
                $productStatement->execute(['id' => $productId]);
                $product = $productStatement->fetch();
                if (!$product) {
                    throw new RuntimeException('Produk tidak tersedia.');
                }

                $unitPrice = (float) ($product['promo_price'] ?: $product['price']);
                $availableStock = (int) $product['stock'];
                $variantName = null;

                if ($variantId) {
                    $variantStatement = $this->db->prepare(
                        'SELECT id, variant_name, additional_price, stock
                         FROM product_variants
                         WHERE id = :id AND product_id = :product_id
                         FOR UPDATE'
                    );
                    $variantStatement->execute(['id' => $variantId, 'product_id' => $productId]);
                    $variant = $variantStatement->fetch();
                    if (!$variant) {
                        throw new RuntimeException('Varian tidak tersedia.');
                    }
                    $unitPrice += (float) $variant['additional_price'];
                    $availableStock = (int) $variant['stock'];
                    $variantName = $variant['variant_name'];
                }

                if ($quantity < 1 || $quantity > $availableStock) {
                    throw new RuntimeException('Stok ' . $product['name'] . ' tidak mencukupi.');
                }

                $subtotal += $unitPrice * $quantity;
                $lockedItems[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId ?: null,
                    'product_name' => $product['name'],
                    'variant_name' => $variantName,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $quantity,
                    'weight_grams' => (int) $product['weight_grams'],
                ];
            }

            $shippingCost = 0.0;
            $shippingZoneId = null;
            if (($data['shipping_method'] ?? '') === 'courier') {
                $zoneStatement = $this->db->prepare(
                    'SELECT id, cost FROM shipping_zones WHERE id = :id AND is_active = 1'
                );
                $zoneStatement->execute(['id' => (int) $data['shipping_zone_id']]);
                $zone = $zoneStatement->fetch();
                if (!$zone) {
                    throw new RuntimeException('Zona pengiriman tidak valid.');
                }
                $shippingZoneId = (int) $zone['id'];
                $shippingCost = (float) $zone['cost'];
            }

            $discount = 0.0;
            $voucherId = null;
            $voucherCode = null;
            if (trim((string) ($data['voucher_code'] ?? '')) !== '') {
                $voucherResult = $this->validateVoucher((string) $data['voucher_code'], $subtotal);
                if (!$voucherResult['valid']) {
                    throw new RuntimeException($voucherResult['message']);
                }
                $discount = (float) $voucherResult['discount'];
                $voucherId = (int) $voucherResult['voucher']['id'];
                $voucherCode = (string) $voucherResult['voucher']['code'];
                if ($voucherResult['voucher']['discount_type'] === 'free_shipping') {
                    $discount = 0.0;
                    $shippingCost = 0.0;
                }
            }

            $total = max(0, $subtotal + $shippingCost - $discount);
            $paymentMethod = ($data['payment_method'] ?? '') === 'QRIS' ? 'QRIS' : 'COD';
            $orderStatus = $paymentMethod === 'QRIS' ? 'Menunggu pembayaran' : 'Menunggu konfirmasi';
            $paymentStatus = 'Belum dibayar';
            $invoice = 'MMJ-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $orderStatement = $this->db->prepare(
                'INSERT INTO orders
                 (invoice_number, user_id, shipping_zone_id, recipient_name, recipient_phone,
                  shipping_address, shipping_method, shipping_cost, voucher_code,
                  discount_amount, subtotal, total_amount, status, payment_status,
                  payment_method, notes)
                 VALUES
                 (:invoice_number, :user_id, :shipping_zone_id, :recipient_name, :recipient_phone,
                  :shipping_address, :shipping_method, :shipping_cost, :voucher_code,
                  :discount_amount, :subtotal, :total_amount, :status, :payment_status,
                  :payment_method, :notes)'
            );
            $orderStatement->execute([
                'invoice_number' => $invoice,
                'user_id' => $userId,
                'shipping_zone_id' => $shippingZoneId,
                'recipient_name' => trim($data['recipient_name']),
                'recipient_phone' => trim($data['recipient_phone']),
                'shipping_address' => trim($data['address_line']),
                'shipping_method' => $data['shipping_method'] === 'courier' ? 'courier' : 'pickup',
                'shipping_cost' => $shippingCost,
                'voucher_code' => $voucherCode,
                'discount_amount' => $discount,
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'notes' => trim($data['notes']),
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $itemStatement = $this->db->prepare(
                'INSERT INTO order_items
                 (order_id, product_id, variant_id, product_name, variant_name,
                  quantity, unit_price, subtotal, weight_grams)
                 VALUES
                 (:order_id, :product_id, :variant_id, :product_name, :variant_name,
                  :quantity, :unit_price, :subtotal, :weight_grams)'
            );
            $movementStatement = $this->db->prepare(
                'INSERT INTO stock_movements
                 (product_id, variant_id, user_id, movement_type, quantity_change,
                  stock_before, stock_after, reason, reference_type, reference_id)
                 VALUES
                 (:product_id, :variant_id, :user_id, :movement_type, :quantity_change,
                  :stock_before, :stock_after, :reason, :reference_type, :reference_id)'
            );

            foreach ($lockedItems as $locked) {
                $itemStatement->execute([
                    'order_id' => $orderId,
                    'product_id' => $locked['product_id'],
                    'variant_id' => $locked['variant_id'],
                    'product_name' => $locked['product_name'],
                    'variant_name' => $locked['variant_name'],
                    'quantity' => $locked['quantity'],
                    'unit_price' => $locked['unit_price'],
                    'subtotal' => $locked['subtotal'],
                    'weight_grams' => $locked['weight_grams'],
                ]);

                if ($locked['variant_id']) {
                    $stockStatement = $this->db->prepare(
                        'SELECT stock FROM product_variants WHERE id = :id FOR UPDATE'
                    );
                    $stockStatement->execute(['id' => $locked['variant_id']]);
                    $before = (int) $stockStatement->fetchColumn();
                    $after = $before - $locked['quantity'];
                    $update = $this->db->prepare(
                        'UPDATE product_variants SET stock = :stock WHERE id = :id'
                    );
                    $update->execute(['stock' => $after, 'id' => $locked['variant_id']]);
                } else {
                    $stockStatement = $this->db->prepare(
                        'SELECT stock FROM products WHERE id = :id FOR UPDATE'
                    );
                    $stockStatement->execute(['id' => $locked['product_id']]);
                    $before = (int) $stockStatement->fetchColumn();
                    $after = $before - $locked['quantity'];
                    $update = $this->db->prepare(
                        'UPDATE products SET stock = :stock WHERE id = :id'
                    );
                    $update->execute(['stock' => $after, 'id' => $locked['product_id']]);
                }

                $movementStatement->execute([
                    'product_id' => $locked['product_id'],
                    'variant_id' => $locked['variant_id'],
                    'user_id' => $userId,
                    'movement_type' => 'reservation',
                    'quantity_change' => -$locked['quantity'],
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reason' => 'Reservasi stok pesanan ' . $invoice,
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                ]);
            }

            $paymentStatement = $this->db->prepare(
                'INSERT INTO payments (order_id, payment_method, amount, status)
                 VALUES (:order_id, :payment_method, :amount, :status)'
            );
            $paymentStatement->execute([
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'amount' => $total,
                'status' => $paymentStatus,
            ]);

            if ($voucherId) {
                $usageStatement = $this->db->prepare(
                    'INSERT INTO voucher_usages (voucher_id, order_id, user_id, discount_amount)
                     VALUES (:voucher_id, :order_id, :user_id, :discount_amount)'
                );
                $usageStatement->execute([
                    'voucher_id' => $voucherId,
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'discount_amount' => $discount,
                ]);
                $quotaStatement = $this->db->prepare(
                    'UPDATE vouchers SET used_count = used_count + 1
                     WHERE id = :id AND (quota IS NULL OR used_count < quota)'
                );
                $quotaStatement->execute(['id' => $voucherId]);
                if ($quotaStatement->rowCount() !== 1) {
                    throw new RuntimeException('Kuota voucher baru saja habis.');
                }
            }

            $adminStatement = $this->db->prepare(
                'SELECT id FROM users WHERE role = :role LIMIT 1'
            );
            $adminStatement->execute(['role' => 'admin']);
            $admin = $adminStatement->fetch();
            $notificationStatement = $this->db->prepare(
                'INSERT INTO notifications (user_id, title, message, link, type)
                 VALUES (:user_id, :title, :message, :link, :type)'
            );
            $notificationStatement->execute([
                'user_id' => $userId,
                'title' => 'Pesanan dibuat',
                'message' => 'Pesanan ' . $invoice . ' berhasil dibuat.',
                'link' => '/orders/detail/' . $invoice,
                'type' => 'order',
            ]);
            if ($admin) {
                $notificationStatement->execute([
                    'user_id' => (int) $admin['id'],
                    'title' => 'Pesanan baru',
                    'message' => 'Pesanan baru ' . $invoice . ' menunggu diproses.',
                    'link' => '/admin/orders/detail/' . $invoice,
                    'type' => 'order',
                ]);
            }

            $deleteCartItem = $this->db->prepare(
                'DELETE ci FROM cart_items ci
                 INNER JOIN carts c ON c.id = ci.cart_id
                 WHERE ci.id = :id AND c.user_id = :user_id'
            );
            foreach ($items as $item) {
                $deleteCartItem->execute([
                    'id' => (int) $item['id'],
                    'user_id' => $userId,
                ]);
            }

            $this->db->commit();
            return ['invoice' => $invoice, 'order_id' => $orderId, 'payment_method' => $paymentMethod];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
