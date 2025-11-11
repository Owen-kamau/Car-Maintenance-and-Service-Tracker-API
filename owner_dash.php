require_once 'email.php';

echo sendServiceReminder(
    'client@example.com',
    'John Doe',
    'Toyota',
    'Corolla',
    'Oil Change',
    '2025-12-01'
);
