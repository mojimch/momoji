<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h1 class="h4 mb-0">Profil & Alamat Utama</h1></div>
            <div class="card-body">
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/profile">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="profile-name">Nama akun</label>
                            <input class="form-control" id="profile-name" name="name" value="<?= e($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="profile-email">Email</label>
                            <input class="form-control" id="profile-email" value="<?= e($user['email'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="recipient-name">Nama penerima</label>
                            <input class="form-control" id="recipient-name" name="recipient_name" value="<?= e($address['recipient_name'] ?? $user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="recipient-phone">Nomor telepon</label>
                            <input class="form-control" id="recipient-phone" name="phone" value="<?= e($address['phone'] ?? $user['phone'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address-line">Alamat</label>
                            <textarea class="form-control" id="address-line" name="address_line" rows="3" required><?= e($address['address_line'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="address-city">Kota</label>
                            <input class="form-control" id="address-city" name="city" value="<?= e($address['city'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="postal-code">Kode pos</label>
                            <input class="form-control" id="postal-code" name="postal_code" value="<?= e($address['postal_code'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="shipping-zone">Zona pengiriman</label>
                            <select class="form-select" id="shipping-zone" name="shipping_zone_id">
                                <option value="">Pilih zona</option>
                                <?php foreach ($zones as $zone): ?>
                                    <option value="<?= (int) $zone['id'] ?>" <?= (int) ($address['shipping_zone_id'] ?? 0) === (int) $zone['id'] ? 'selected' : '' ?>>
                                        <?= e($zone['zone_name']) ?> - <?= format_rupiah($zone['cost']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">Simpan Profil</button>
                </form>
            </div>
        </div>
    </div>
</div>
