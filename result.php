<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
requireOpenActivity();

$result = $_SESSION['manual_result'] ?? null;
if (!is_array($result)) {
    header('Location: activity.php');
    exit;
}

$role = (string) ($result['role'] ?? '');
$accountStatus = (string) ($result['account_status'] ?? '');
$sessionStatus = (string) ($result['session_status'] ?? '');
$requestedRoute = (string) ($result['requested_route'] ?? '');
$accessGranted = ($result['granted'] ?? false) === true;

$validRoles = ['SUPERADMIN', 'ADMIN', 'FACULTY'];
$validAccounts = ['ACTIVE', 'DISABLED'];
$validSessions = ['VALID', 'EXPIRED'];
$validRoutes = ['/superadmin/dashboard', '/admin/dashboard', '/faculty/dashboard'];
if (!in_array($role, $validRoles, true) || !in_array($accountStatus, $validAccounts, true) || !in_array($sessionStatus, $validSessions, true) || !in_array($requestedRoute, $validRoutes, true)) {
    header('Location: activity.php');
    exit;
}

$roleLabel = ucfirst(strtolower($role));
$accountLabel = ucfirst(strtolower($accountStatus));
$sessionLabel = ucfirst(strtolower($sessionStatus));
$resultTitle = $accessGranted ? 'Access Granted' : 'Access Denied';
$resultClass = $accessGranted ? 'result-success' : 'result-danger';
$resultIcon = $accessGranted ? 'bi-check-lg' : 'bi-x-lg';
$allowedRoutes = [
    'SUPERADMIN' => '/superadmin/dashboard',
    'ADMIN' => '/admin/dashboard',
    'FACULTY' => '/faculty/dashboard',
];
$allowedRoute = $allowedRoutes[$role] ?? '';
$routeMatches = $allowedRoute === $requestedRoute;

if ($accessGranted) {
    $reason = "{$roleLabel} was allowed to open {$requestedRoute}.";
} elseif ($accountStatus === 'DISABLED') {
    $reason = "The {$roleLabel} account is disabled.";
} elseif ($sessionStatus === 'EXPIRED') {
    $reason = "The {$roleLabel} session has expired.";
} elseif (!$routeMatches) {
    $reason = "{$roleLabel} is not authorized for {$requestedRoute}.";
} else {
    $reason = 'The current code denied this request.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($resultTitle) ?> | EduSchedX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <header class="brand-header"><a href="index.php" class="brand-link"><img src="images/eduschedx-logo.svg" alt="" class="brand-logo"><span class="brand-name">EduSched<span>X</span></span></a></header>
    <main class="activity-shell">
        <section class="activity-card result-page">
            <div class="screen-panel">
                <div class="result-profile <?= escape($resultClass) ?>" role="status">
                    <div class="profile-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i><span class="profile-decision"><i class="bi <?= escape($resultIcon) ?>" aria-hidden="true"></i></span></div>
                    <p class="profile-label">User Role</p>
                    <h1><?= escape($roleLabel) ?></h1>
                    <span class="result-badge"><?= escape($resultTitle) ?></span>
                    <p class="result-reason"><?= escape($reason) ?></p>
                    <div class="profile-status">
                        <div><i class="bi bi-person-check" aria-hidden="true"></i><span>Account</span><strong><?= escape($accountLabel) ?></strong></div>
                        <div><i class="bi bi-clock-history" aria-hidden="true"></i><span>Session</span><strong><?= escape($sessionLabel) ?></strong></div>
                        <div class="profile-route">
                            <i class="bi bi-signpost-split" aria-hidden="true"></i>
                            <span>Requested Route</span>
                            <strong><?= escape($requestedRoute) ?></strong>
                            <small class="route-check <?= $routeMatches ? 'route-match' : 'route-mismatch' ?>">
                                <i class="bi <?= $routeMatches ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>" aria-hidden="true"></i>
                                <?= $routeMatches ? 'Route matched' : 'Route does not match' ?>
                            </small>
                        </div>
                        <div class="profile-route expected-route">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            <span>Allowed Route for <?= escape($roleLabel) ?></span>
                            <strong><?= escape($allowedRoute) ?></strong>
                        </div>
                    </div>
                </div>
                <a href="activity.php" class="btn btn-eduschedx btn-label-centered icon-start w-100 mt-4"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Try Another Scenario</span></a>
            </div>
        </section>
    </main>
</body>
</html>
