<?php

require_once __DIR__ . '/../config/database_connection.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../admin/includes/media.php';
require_once __DIR__ . '/../includes/review_media.php';

$assertions = 0;

function check(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectException(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (RuntimeException $exception) {
        check(true, $message);
        return;
    }
    check(false, $message);
}

$passwordHash = hashPassword('correct horse battery staple');
check(verifyPassword('correct horse battery staple', $passwordHash), 'Password hashes should verify.');
check(!verifyPassword('wrong password', $passwordHash), 'Incorrect passwords should not verify.');

loginUser([
    'id' => 42,
    'full_name' => 'Test Customer',
    'email' => 'customer@example.test',
    'role' => 'user',
]);
check(isLoggedIn(), 'Login should populate the session.');
check(!isAdmin(), 'Customer sessions should not have admin privileges.');
check(currentUser()['email'] === 'customer@example.test', 'Current user details should come from the session.');
logoutUser();
check(!isLoggedIn(), 'Logout should clear the session.');

expectException(
    static function (): void {
        uploadAdminImage([], 'invalid');
    },
    'Admin image upload should reject an unsupported upload type.'
);

$tooManyAdminFiles = [
    'name' => array_fill(0, 11, 'image.jpg'),
    'error' => array_fill(0, 11, UPLOAD_ERR_OK),
    'size' => array_fill(0, 11, 1),
];
expectException(
    static function () use ($tooManyAdminFiles): void {
        validateAdminImageBatch($tooManyAdminFiles);
    },
    'Admin image batches should enforce the file-count limit.'
);

$tooManyReviewImages = [
    'name' => array_fill(0, 6, 'image.jpg'),
    'error' => array_fill(0, 6, UPLOAD_ERR_OK),
    'size' => array_fill(0, 6, 1),
];
expectException(
    static function () use ($tooManyReviewImages): void {
        uploadReviewImages($tooManyReviewImages);
    },
    'Review image uploads should enforce the five-image limit.'
);

foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $key) {
    putenv($key);
}
expectException(
    static function (): void {
        connectDatabase([
            'host' => '',
            'port' => '',
            'dbname' => '',
            'username' => '',
            'password' => '',
        ], __DIR__ . '/missing-database-config.php');
    },
    'Database connection should fail clearly when configuration is missing.'
);

$schema = file_get_contents(__DIR__ . '/../database.sql');
check($schema !== false && preg_match('/^\s*(DELETE|TRUNCATE)\s+/im', $schema) !== 1, 'Schema SQL must not contain destructive data statements.');

echo "Passed {$assertions} assertions.\n";
