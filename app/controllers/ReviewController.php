<?php

class ReviewController extends Controller
{
    private ReviewModel $reviews;

    public function __construct()
    {
        $this->reviews = new ReviewModel();
    }

    public function submit(): void
    {
        AuthMiddleware::requireCustomer();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/orders');
        }

        $invoice = trim((string) ($_POST['invoice'] ?? ''));
        try {
            $this->reviews->submit(
                (int) AuthMiddleware::check()['id'],
                (int) ($_POST['order_item_id'] ?? 0),
                (int) ($_POST['rating'] ?? 0),
                trim((string) ($_POST['comment'] ?? ''))
            );
            flash('success', 'Ulasan dikirim dan menunggu moderasi admin.');
        } catch (RuntimeException|InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Ulasan tidak dapat disimpan. Coba kembali.');
        }
        $this->redirect('/orders/detail/' . rawurlencode($invoice));
    }

    public function adminIndex(): void
    {
        AuthMiddleware::requireAdmin();
        $status = (string) ($_GET['status'] ?? '');
        if (!in_array($status, ['', 'pending', 'approved', 'rejected'], true)) {
            $status = '';
        }
        $this->view('admin/reviews/index', [
            'reviews' => $this->reviews->adminReviews($status),
            'status' => $status,
        ], 'admin_layout');
    }

    public function updateStatus(int $id): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/reviews');
        }
        try {
            if (!$this->reviews->setStatus($id, (string) ($_POST['status'] ?? ''))) {
                flash('error', 'Ulasan tidak ditemukan.');
            } else {
                flash('success', 'Moderasi ulasan berhasil diperbarui.');
            }
        } catch (RuntimeException|InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Status ulasan tidak dapat disimpan.');
        }
        $this->redirect('/admin/reviews');
    }
}
