<?php
declare(strict_types=1);

final class Application_Model_UserRepository
{
    private Application_Model_DbTable_Users $table;

    public function __construct(?Application_Model_DbTable_Users $table = null)
    {
        $this->table = $table ?? new Application_Model_DbTable_Users();
    }

    public function findByEmail(string $email): ?Application_Model_User
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            return null;
        }

        try {
            $select = $this->table->select()->where('email = ?', $normalizedEmail)->limit(1);
            $row = $this->table->fetchRow($select);
        } catch (Zend_Db_Exception $exception) {
            error_log('User lookup failed: ' . $exception->getMessage());

            return null;
        }

        if ($row === null) {
            return null;
        }

        return new Application_Model_User(
            (int) $row->id,
            (string) $row->email,
            (string) $row->name,
            (string) $row->password_hash,
        );
    }
}
