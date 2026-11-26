<form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/checkout">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4"><div class="card-header"><h1 class="h5 mb-0">Informasi Penerima</h1></div><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="checkout-recipient">Nama penerima</label><input class="form-control" id="checkout-recipient" name="recipient_name" value="<?= e($address['recipient_name'] ?? '') ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="checkout-phone">Nomor telepon</label><input class="form-control" id="checkout-phone" name="recipient_phone" value="<?= e($address['phone'] ?? '') ?>" required></div>
                    <div class="col-md-8"><label class="form-label" for="checkout-address">Alamat</label><textarea class="form-control" id="checkout-address" name="address_line" rows="3" required><?= e($address['address_line'] ?? '') ?></textarea></div>
                    <div class="col-md-4"><label class="form-label" for="checkout-city">Kota</label><input class="form-control" id="checkout-city" name="city" value="<?= e($address['city'] ?? '') ?>" required></div>
                </div>
            </div></div>

            <div class="card mb-4"><div class="card-header"><h2 class="h5 mb-0">Pengiriman & Pembayaran</h2></div><div class="card-body">
                <div class="mb-3">
                    <label class="form-label d-block">Metode pengiriman</label>
                    <div class="form-check"><input class="form-check-input" id="shipping-pickup" type="radio" name="shipping_method" value="pickup" checked><label class="form-check-label" for="shipping-pickup">Pickup di toko - Rp 0</label></div>
                    <div class="form-check"><input class="form-check-input" id="shipping-courier" type="radio" name="shipping_method" value="courier"><label class="form-check-label" for="shipping-courier">Kurir Momoji</label></div>
                </div>
                <div class="mb-3"><label class="form-label" for="checkout-zone">Zona pengiriman</label><select class="form-select" id="checkout-zone" name="shipping_zone_id"><option value="">Pilih zona</option><?php foreach ($zones as $zone): ?><option value="<?= (int) $zone['id'] ?>" data-cost="<?= e($zone['cost']) ?>" <?= (int) ($address['shipping_zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>><?= e($zone['zone_name']) ?> - <?= format_rupiah($zone['cost']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label" for="checkout-voucher">Kode voucher</label><div class="input-group"><input class="form-control" id="checkout-voucher" name="voucher_code"><button class="btn btn-outline-primary" id="validate-voucher" type="button">Validasi</button></div><div class="small mt-2" id="voucher-result"></div></div>
                <div class="mb-3"><label class="form-label" for="checkout-notes">Catatan pesanan</label><textarea class="form-control" id="checkout-notes" name="notes" rows="2"></textarea></div>
                <div>
                    <label class="form-label d-block">Metode pembayaran</label>
                    <div class="form-check"><input class="form-check-input" id="payment-cod" type="radio" name="payment_method" value="COD" checked><label class="form-check-label" for="payment-cod">COD</label></div>
                    <div class="form-check"><input class="form-check-input" id="payment-qris" type="radio" name="payment_method" value="QRIS"><label class="form-check-label" for="payment-qris">QRIS Static</label></div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card card-body sticky-top" style="top:90px;">
                <h2 class="h5">Ringkasan Pesanan</h2>
                <?php $subtotal = 0.0; foreach ($items as $item):
                    $price = (float) ($item['promo_price'] ?: $item['price']) + (float) ($item['additional_price'] ?: 0);
                    $subtotal += $price * (int) $item['quantity'];
                ?>
                    <div class="d-flex justify-content-between small mb-2"><span><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></span><span><?= format_rupiah($price * (int) $item['quantity']) ?></span></div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="checkout-subtotal" data-subtotal="<?= e($subtotal) ?>"><?= format_rupiah($subtotal) ?></strong></div>
                <div class="d-flex justify-content-between mt-2"><span>Ongkir</span><strong id="checkout-shipping">Rp 0</strong></div>
                <div class="d-flex justify-content-between mt-2"><span>Diskon</span><strong id="checkout-discount">Rp 0</strong></div>
                <hr>
                <div class="d-flex justify-content-between"><span>Total</span><strong id="checkout-total"><?= format_rupiah($subtotal) ?></strong></div>
                <button class="btn btn-primary w-100 mt-3" type="submit">Konfirmasi Pesanan</button>
            </div>
        </div>
    </div>
</form>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const subtotal = Number(document.querySelector("#checkout-subtotal").dataset.subtotal);
    const zone = document.querySelector("#checkout-zone");
    const shippingEl = document.querySelector("#checkout-shipping");
    const discountEl = document.querySelector("#checkout-discount");
    const totalEl = document.querySelector("#checkout-total");
    let discount = 0;
    let freeShipping = false;
    function rupiah(value) { return "Rp " + new Intl.NumberFormat("id-ID").format(value); }
    function shipping() {
        if (document.querySelector("#shipping-courier").checked) {
            const option = zone.options[zone.selectedIndex];
            return freeShipping ? 0 : Number(option ? option.dataset.cost : 0);
        }
        return 0;
    }
    function refresh() {
        const cost = shipping();
        shippingEl.textContent = rupiah(cost);
        totalEl.textContent = rupiah(Math.max(0, subtotal + cost - discount));
    }
    document.querySelectorAll("[name='shipping_method'], #checkout-zone").forEach(function (input) {
        input.addEventListener("change", refresh);
    });
    document.querySelector("#validate-voucher").addEventListener("click", function () {
        const body = new URLSearchParams();
        body.append("_csrf", document.querySelector("[name='_csrf']").value);
        body.append("code", document.querySelector("#checkout-voucher").value);
        fetch("<?= e(rtrim(APP_URL, '/')) ?>/checkout/voucher", {method:"POST", body:body, headers:{Accept:"application/json"}})
            .then(function (response) { return response.json(); })
            .then(function (result) {
                document.querySelector("#voucher-result").textContent = result.message;
                discount = result.valid ? Number(result.discount || 0) : 0;
                freeShipping = Boolean(result.valid && result.free_shipping);
                discountEl.textContent = rupiah(discount);
                refresh();
            })
            .catch(function () {
                document.querySelector("#voucher-result").textContent = "Voucher tidak dapat diverifikasi. Coba lagi.";
                discount = 0;
                freeShipping = false;
                discountEl.textContent = rupiah(0);
                refresh();
            });
    });
    refresh();
});
</script>
