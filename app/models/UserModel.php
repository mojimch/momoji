<?php

class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, email, phone, password, role, status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, email, phone, role, status
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        return (bool) $statement->fetchColumn();
    }

    public function createCustomer(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, phone, password, role, status)
             VALUES (:name, :email, :phone, :password, :role, :status)'
        );
        $statement->execute([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => 'customer',
            'status' => 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function createPasswordReset(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $statement = $this->db->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
        ]);
        return $token;
    }

    public function resetPassword(string $token, string $password): bool
    {
        $tokenHash = hash('sha256', $token);
        $statement = $this->db->prepare(
            'SELECT id, user_id
             FROM password_reset_tokens
             WHERE token_hash = :token_hash
               AND used_at IS NULL
               AND expires_at >= NOW()
             LIMIT 1'
        );
        $statement->execute(['token_hash' => $tokenHash]);
        $reset = $statement->fetch();

        if (!$reset) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $updatePassword = $this->db->prepare(
                'UPDATE users
                 SET password = :password
                 WHERE id = :id'
            );
            $updatePassword->execute([
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'id' => (int) $reset['user_id'],
            ]);

            $markUsed = $this->db->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE id = :id'
            );
            $markUsed->execute(['id' => (int) $reset['id']]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
