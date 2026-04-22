<?php
/**
 * api/index.php — Complete API Router
 * ─────────────────────────────────────────────────────────────────────────────
 * Replaces every Flask route from app.py.
 * All JSON response shapes are identical so the existing JS works untouched.
 *
 * Apache rewrites /api/* → this file via root .htaccess.
 * REQUEST_URI still contains the original path (e.g. /api/events/5).
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

// ── Derive path ───────────────────────────────────────────────────────────────
// REQUEST_URI = /api/events/5   →   path = /events/5
$uri_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
// Find where /api appears and strip it
$api_pos = strpos($uri_path, '/api');
$path    = ($api_pos !== false) ? substr($uri_path, $api_pos + 4) : $uri_path;
$path    = rtrim($path, '/');
if ($path === '') $path = '/';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Handle CORS preflight (Hostinger may need this for local dev)
if ($method === 'OPTIONS') { http_response_code(200); exit; }

// ── Dispatch ──────────────────────────────────────────────────────────────────
try {
    dispatch($method, $path);
} catch (PDOException $e) {
    // Hide raw SQL errors from UI for security/cleanliness
    json_out(['error' => 'Database connection failed. Please verify your credentials in config.php.'], 500);
} catch (Throwable $e) {
    json_out(['error' => 'An unexpected error occurred.'], 500);
}

// ═════════════════════════════════════════════════════════════════════════════
// ROUTER
// ═════════════════════════════════════════════════════════════════════════════
function dispatch(string $method, string $path): void {

    // ── Public stats ────────────────────────────────────────────────────────
    if ($path === '/stats' && $method === 'GET')          { api_stats();          return; }

    // ── Public winners ──────────────────────────────────────────────────────
    if ($path === '/winners' && $method === 'GET')         { winners_public();    return; }

    // ── Admin auth ───────────────────────────────────────────────────────────
    if ($path === '/admin/login'  && $method === 'POST')   { admin_login();       return; }
    if ($path === '/admin/logout' && $method === 'POST')   { admin_logout();      return; }
    if ($path === '/admin/status' && $method === 'GET')    { admin_status();      return; }

    // ── Runner auth ──────────────────────────────────────────────────────────
    if ($path === '/runner/login'     && $method === 'POST') { runner_login();    return; }
    if ($path === '/runner/logout'    && $method === 'POST') { runner_logout();   return; }
    if ($path === '/runner/status'    && $method === 'GET')  { runner_status();   return; }
    if ($path === '/runner/submit-km' && $method === 'POST') { runner_submit_km(); return; }

    // ── Events ────────────────────────────────────────────────────────────────
    if ($path === '/events') {
        if ($method === 'GET')  { events_get_all();  return; }
        if ($method === 'POST') { events_add();       return; }
    }
    if (preg_match('#^/events/(\d+)$#', $path, $m)) {
        if ($method === 'DELETE') { events_delete((int)$m[1]); return; }
        if ($method === 'PUT')    { events_update((int)$m[1]); return; }
    }

    // ── Runners (public GET, admin POST) ─────────────────────────────────────
    if ($path === '/runners') {
        if ($method === 'GET')  { runners_get_all(); return; }
        if ($method === 'POST') { runners_add();      return; }
    }

    // ── Runner KM log (runner or admin) ─────────────────────────────────────
    if (preg_match('#^/runners/(\d+)/km/log$#', $path, $m) && $method === 'GET') {
        runner_km_log((int)$m[1]); return;
    }

    // ── Admin: runner management ─────────────────────────────────────────────
    if (preg_match('#^/admin/runners/(\d+)$#', $path, $m)) {
        if ($method === 'DELETE') { admin_delete_runner((int)$m[1]); return; }
    }
    if (preg_match('#^/admin/runners/(\d+)/km$#', $path, $m)) {
        if ($method === 'PATCH') { admin_update_runner_km((int)$m[1]); return; }
    }

    // ── Admin: KM verification ───────────────────────────────────────────────
    if ($path === '/admin/pending-logs' && $method === 'GET') { admin_pending_logs(); return; }
    if (preg_match('#^/admin/verify-km/(\d+)$#', $path, $m) && $method === 'PATCH') {
        admin_verify_km((int)$m[1]); return;
    }

    // ── Admin: Registration approval ─────────────────────────────────────────
    if (preg_match('#^/admin/approve-runner/(\d+)$#', $path, $m) && $method === 'POST') {
        admin_approve_runner((int)$m[1]); return;
    }

    // ── Admin: Winners management ────────────────────────────────────────────
    if ($path === '/admin/winners/manage') {
        if ($method === 'GET')  { admin_winners_get();  return; }
        if ($method === 'POST') { admin_winners_add();  return; }
    }
    if (preg_match('#^/admin/winners/delete/(\d+)$#', $path, $m) && $method === 'DELETE') {
        admin_winners_delete((int)$m[1]); return;
    }

    // ── Registrations ────────────────────────────────────────────────────────
    if ($path === '/registrations') {
        if ($method === 'GET')  { registrations_get_all(); return; }
        if ($method === 'POST') { registrations_add();      return; }
    }
    if (preg_match('#^/registrations/(\d+)/status$#', $path, $m) && $method === 'PATCH') {
        registrations_update_status((int)$m[1]); return;
    }

    // ── 404 fallback ─────────────────────────────────────────────────────────
    json_out(['error' => 'Route not found', 'path' => $path, 'method' => $method], 404);
}


// ═════════════════════════════════════════════════════════════════════════════
//  STATS   GET /api/stats
// ═════════════════════════════════════════════════════════════════════════════
function api_stats(): void {
    $pdo = db();
    $total_runners = (int) $pdo->query("SELECT COUNT(*) FROM runners WHERE status='Active'")->fetchColumn();
    $total_events  = (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $total_km      = (float) $pdo->query("SELECT COALESCE(SUM(km),0) FROM runners WHERE status='Active'")->fetchColumn();
    $total_regs    = (int) $pdo->query("SELECT COUNT(*) FROM registrations WHERE status='Pending'")->fetchColumn();
    json_out([
        'total_runners'       => $total_runners,
        'total_events'        => $total_events,
        'total_km'            => round($total_km, 1),
        'total_registrations' => $total_regs,
    ]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  ADMIN AUTH
//  POST /api/admin/login
//  POST /api/admin/logout
//  GET  /api/admin/status
// ═════════════════════════════════════════════════════════════════════════════
function admin_login(): void {
    $d = get_json();
    if (($d['username'] ?? '') === ADMIN_USER && ($d['password'] ?? '') === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        json_out(['success' => true]);
    }
    json_out(['error' => 'Invalid credentials'], 401);
}

function admin_logout(): void {
    unset($_SESSION['admin']);
    json_out(['success' => true]);
}

function admin_status(): void {
    json_out(['logged_in' => is_admin()]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  RUNNER AUTH
//  POST /api/runner/login
//  POST /api/runner/logout
//  GET  /api/runner/status
// ═════════════════════════════════════════════════════════════════════════════
function runner_login(): void {
    $d    = get_json();
    $phone = trim($d['phone'] ?? '');
    $pwd   = trim($d['password'] ?? '');
    if (!$phone || !$pwd) json_out(['error' => 'Phone and password required'], 400);

    $pdo  = db();
    $stmt = $pdo->prepare("SELECT * FROM runners WHERE phone = ? AND password = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$phone, $pwd]);
    $runner = $stmt->fetch();
    if (!$runner) json_out(['error' => 'Invalid credentials or account pending approval'], 401);

    $_SESSION['runner_id'] = $runner['id'];
    json_out(['success' => true, 'runner' => $runner]);
}

function runner_logout(): void {
    unset($_SESSION['runner_id']);
    json_out(['success' => true]);
}

function runner_status(): void {
    $rid = $_SESSION['runner_id'] ?? null;
    if (!$rid) { json_out(['logged_in' => false]); }
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT * FROM runners WHERE id = ? LIMIT 1");
    $stmt->execute([$rid]);
    $runner = $stmt->fetch();
    if (!$runner) { unset($_SESSION['runner_id']); json_out(['logged_in' => false]); }
    json_out(['logged_in' => true, 'runner' => $runner]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  RUNNER: SUBMIT KM PROOF
//  POST /api/runner/submit-km   (multipart/form-data)
// ═════════════════════════════════════════════════════════════════════════════
function runner_submit_km(): void {
    require_runner();
    $rid  = (int)$_SESSION['runner_id'];
    $km   = (float)($_POST['km']   ?? 0);
    $note = trim($_POST['note']    ?? '');

    if ($km <= 0) json_out(['error' => 'Enter a valid KM amount'], 400);

    // Handle file upload
    $proof_url = '';
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['proof'];
        if (!allowed_file($file['name'])) {
            json_out(['error' => 'Invalid file type. Use PNG, JPG, JPEG, or GIF.'], 400);
        }
        // Ensure upload dir exists and is writable
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        $filename = safe_filename($file['name'], $rid);
        $dest     = UPLOAD_DIR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            json_out(['error' => 'File upload failed — check server permissions on static/uploads/'], 500);
        }
        $proof_url = $filename;
    } else {
        // Proof is mandatory
        json_out(['error' => 'Please upload a proof screenshot!'], 400);
    }

    $pdo  = db();
    $stmt = $pdo->prepare(
        "INSERT INTO km_log (runner_id, km_added, note, proof_url, status, log_date)
         VALUES (?, ?, ?, ?, 'Pending', CURDATE())"
    );
    $stmt->execute([$rid, $km, $note, $proof_url]);

    json_out(['success' => true, 'message' => 'Submitted for verification']);
}


// ═════════════════════════════════════════════════════════════════════════════
//  EVENTS
//  GET    /api/events
//  POST   /api/events         [admin]
//  DELETE /api/events/{id}    [admin]
//  PUT    /api/events/{id}    [admin]
// ═════════════════════════════════════════════════════════════════════════════
function events_get_all(): void {
    $pdo  = db();
    $rows = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();
    json_out($rows);
}

function events_add(): void {
    require_admin();
    $d = get_json();
    if (empty($d['name']) || empty($d['event_date']) || empty($d['venue'])) {
        json_out(['error' => 'name, event_date, venue required'], 400);
    }
    $pdo  = db();
    $stmt = $pdo->prepare(
        "INSERT INTO events (name, event_date, venue, distances, reg_link, organizer, disc_code)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $d['name'], $d['event_date'], $d['venue'],
        $d['distances']  ?? '',
        $d['reg_link']   ?? '',
        $d['organizer']  ?? '',
        $d['disc_code']  ?? '',
    ]);
    $id  = (int)$pdo->lastInsertId();
    $row = $pdo->prepare("SELECT * FROM events WHERE id = ?")->execute([$id]);
    $ev  = $pdo->query("SELECT * FROM events WHERE id = $id")->fetch();
    json_out($ev, 201);
}

function events_delete(int $id): void {
    require_admin();
    $pdo  = db();
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
    json_out(['success' => true]);
}

function events_update(int $id): void {
    require_admin();
    $d   = get_json();
    $pdo = db();
    $pdo->prepare(
        "UPDATE events SET name=?, event_date=?, venue=?, distances=?, reg_link=?, organizer=?, disc_code=?
         WHERE id=?"
    )->execute([
        $d['name'], $d['event_date'], $d['venue'],
        $d['distances']  ?? '',
        $d['reg_link']   ?? '',
        $d['organizer']  ?? '',
        $d['disc_code']  ?? '',
        $id,
    ]);
    json_out(['success' => true]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  RUNNERS (public list + admin add)
//  GET  /api/runners
//  POST /api/runners   [admin]
// ═════════════════════════════════════════════════════════════════════════════
function runners_get_all(): void {
    $pdo     = db();
    $year    = date('Y');
    $runners = $pdo->query("SELECT * FROM runners WHERE status='Active' ORDER BY km DESC")->fetchAll();

    $result = [];
    // Monthly KM query — same logic as Flask
    $km_stmt = $pdo->prepare(
        "SELECT MONTH(log_date) AS month, SUM(km_added) AS total
         FROM km_log
         WHERE runner_id = ? AND YEAR(log_date) = ? AND status = 'Verified'
         GROUP BY MONTH(log_date)"
    );

    foreach ($runners as $r) {
        $km_stmt->execute([$r['id'], $year]);
        $logs = $km_stmt->fetchAll();

        // Build 12-element array (Jan=index 0 … Dec=index 11)
        $monthly = array_fill(0, 12, 0.0);
        foreach ($logs as $log) {
            $idx = (int)$log['month'] - 1;
            if ($idx >= 0 && $idx < 12) {
                $monthly[$idx] = round((float)$log['total'], 1);
            }
        }
        $r['monthly_kms'] = $monthly;

        // Cast numeric fields to correct types (PDO returns strings)
        $r['id']           = (int)   $r['id'];
        $r['km']           = (float) $r['km'];
        $r['events_count'] = (int)   $r['events_count'];
        $r['is_head']      = (int)   $r['is_head'];
        $result[]          = $r;
    }
    json_out($result);
}

function runners_add(): void {
    require_admin();
    $d    = get_json();
    if (empty($d['name'])) json_out(['error' => 'name required'], 400);
    $pdo  = db();
    $stmt = $pdo->prepare(
        "INSERT INTO runners (name, phone, level, join_date, km, events_count, is_head)
         VALUES (?, ?, ?, ?, 0, 0, 0)"
    );
    $stmt->execute([
        $d['name'],
        $d['phone']     ?? '',
        $d['level']     ?? 'Beginner',
        $d['join_date'] ?? date('Y-m-d'),
    ]);
    $id  = (int)$pdo->lastInsertId();
    $row = $pdo->query("SELECT * FROM runners WHERE id = $id")->fetch();
    json_out($row, 201);
}


// ═════════════════════════════════════════════════════════════════════════════
//  RUNNER KM LOG  (runner owns it OR admin can see it)
//  GET /api/runners/{id}/km/log
// ═════════════════════════════════════════════════════════════════════════════
function runner_km_log(int $runner_id): void {
    if (!is_admin() && (int)($_SESSION['runner_id'] ?? 0) !== $runner_id) {
        json_out(['error' => 'Unauthorized'], 401);
    }
    $pdo  = db();
    $stmt = $pdo->prepare(
        "SELECT * FROM km_log WHERE runner_id = ? ORDER BY log_date DESC"
    );
    $stmt->execute([$runner_id]);
    $rows = $stmt->fetchAll();
    // Cast types
    $rows = array_map(function ($r) {
        $r['id']        = (int)   $r['id'];
        $r['runner_id'] = (int)   $r['runner_id'];
        $r['km_added']  = (float) $r['km_added'];
        return $r;
    }, $rows);
    json_out($rows);
}


// ═════════════════════════════════════════════════════════════════════════════
//  ADMIN: DELETE RUNNER
//  DELETE /api/admin/runners/{id}
// ═════════════════════════════════════════════════════════════════════════════
function admin_delete_runner(int $id): void {
    require_admin();
    $pdo = db();
    // km_log has ON DELETE CASCADE via FK, but delete explicitly for safety
    $pdo->prepare("DELETE FROM km_log  WHERE runner_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM runners WHERE id = ?")->execute([$id]);
    json_out(['success' => true]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  ADMIN: UPDATE RUNNER KM (manual override)
//  PATCH /api/admin/runners/{id}/km
// ═════════════════════════════════════════════════════════════════════════════
function admin_update_runner_km(int $id): void {
    require_admin();
    $d   = get_json();
    $km  = (float)($d['km'] ?? 0);
    $pdo = db();
    $pdo->prepare("UPDATE runners SET km = ? WHERE id = ?")->execute([$km, $id]);
    json_out(['success' => true]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  ADMIN: PENDING KM LOGS
//  GET /api/admin/pending-logs
// ═════════════════════════════════════════════════════════════════════════════
function admin_pending_logs(): void {
    require_admin();
    $pdo  = db();
    $rows = $pdo->query(
        "SELECT l.*, r.name AS runner_name
         FROM km_log l
         JOIN runners r ON l.runner_id = r.id
         WHERE l.status = 'Pending'
         ORDER BY l.log_date DESC"
    )->fetchAll();
    $rows = array_map(function ($r) {
        $r['id']        = (int)   $r['id'];
        $r['runner_id'] = (int)   $r['runner_id'];
        $r['km_added']  = (float) $r['km_added'];
        return $r;
    }, $rows);
    json_out($rows);
}


// ═════════════════════════════════════════════════════════════════════════════
//  ADMIN: VERIFY / REJECT KM SUBMISSION
//  PATCH /api/admin/verify-km/{id}
// ═════════════════════════════════════════════════════════════════════════════
function admin_verify_km(int $log_id): void {
    require_admin();
    $d      = get_json();
    $action = $d['action'] ?? ''; // 'Verify' or 'Reject'
    $pdo    = db();

    $stmt = $pdo->prepare("SELECT * FROM km_log WHERE id = ? LIMIT 1");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch();
    if (!$log) json_out(['error' => 'Log not found'], 404);

    if ($action === 'Verify') {
        $pdo->prepare("UPDATE km_log SET status = 'Verified' WHERE id = ?")->execute([$log_id]);
        // Add the KMs to the runner's total
        $pdo->prepare("UPDATE runners SET km = km + ? WHERE id = ?")->execute([$log['km_added'], $log['runner_id']]);
    } else {
        $pdo->prepare("UPDATE km_log SET status = 'Rejected' WHERE id = ?")->execute([$log_id]);
    }
    json_out(['success' => true]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  ADMIN: APPROVE / REJECT REGISTRATION
//  POST /api/admin/approve-runner/{id}
// ═════════════════════════════════════════════════════════════════════════════
function admin_approve_runner(int $reg_id): void {
    require_admin();
    $d      = get_json();
    $action = $d['action'] ?? ''; // 'Approve' or 'Reject'
    $pdo    = db();

    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ? LIMIT 1");
    $stmt->execute([$reg_id]);
    $reg = $stmt->fetch();
    if (!$reg) json_out(['error' => 'Registration not found'], 404);

    if ($action === 'Approve') {
        $pwd = !empty($reg['password']) ? $reg['password'] : '1234';
        // Create runner from registration data
        $pdo->prepare(
            "INSERT INTO runners (name, phone, email, level, password, status, join_date)
             VALUES (?, ?, ?, ?, ?, 'Active', ?)"
        )->execute([
            $reg['name'], $reg['phone'], $reg['email'] ?? '',
            $reg['level'] ?? 'Beginner', $pwd, date('Y-m-d'),
        ]);
        $pdo->prepare("UPDATE registrations SET status = 'Approved' WHERE id = ?")->execute([$reg_id]);
    } else {
        $pdo->prepare("UPDATE registrations SET status = 'Rejected' WHERE id = ?")->execute([$reg_id]);
    }
    json_out(['success' => true]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  ADMIN: WINNERS
//  GET  /api/admin/winners/manage
//  POST /api/admin/winners/manage
// ═════════════════════════════════════════════════════════════════════════════
function admin_winners_get(): void {
    require_admin();
    $pdo  = db();
    $rows = $pdo->query(
        "SELECT w.*, r.name AS runner_name, r.km AS runner_total_km
         FROM winners w
         JOIN runners r ON w.runner_id = r.id
         ORDER BY w.award_year DESC, w.id DESC"
    )->fetchAll();
    json_out($rows);
}

function admin_winners_add(): void {
    require_admin();
    $d   = get_json();
    $pdo = db();
    $pdo->prepare(
        "INSERT INTO winners (runner_id, category, award_year) VALUES (?, ?, ?)"
    )->execute([
        (int)($d['runner_id'] ?? 0),
        $d['category']  ?? 'Gold',
        (int)($d['award_year'] ?? date('Y')),
    ]);
    json_out(['success' => true]);
}

// DELETE /api/admin/winners/delete/{id}
function admin_winners_delete(int $id): void {
    require_admin();
    $pdo = db();
    $pdo->prepare("DELETE FROM winners WHERE id = ?")->execute([$id]);
    json_out(['success' => true]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  PUBLIC WINNERS
//  GET /api/winners
// ═════════════════════════════════════════════════════════════════════════════
function winners_public(): void {
    $pdo  = db();
    $rows = $pdo->query(
        "SELECT w.*, r.name AS runner_name, r.km AS runner_total_km
         FROM winners w
         JOIN runners r ON w.runner_id = r.id
         ORDER BY w.award_year DESC"
    )->fetchAll();
    json_out($rows);
}


// ═════════════════════════════════════════════════════════════════════════════
//  REGISTRATIONS
//  GET   /api/registrations          [admin]
//  POST  /api/registrations          [public]
//  PATCH /api/registrations/{id}/status  [admin]
// ═════════════════════════════════════════════════════════════════════════════
function registrations_get_all(): void {
    require_admin();
    $pdo  = db();
    $rows = $pdo->query("SELECT * FROM registrations ORDER BY created_at DESC")->fetchAll();
    json_out($rows);
}

function registrations_add(): void {
    $d = get_json();
    if (empty($d['name']) || empty($d['phone'])) {
        json_out(['error' => 'name and phone required'], 400);
    }
    $pdo  = db();
    $stmt = $pdo->prepare(
        "INSERT INTO registrations (name, phone, email, level, source, reg_date, password)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $d['name'],
        $d['phone'],
        $d['email']    ?? '',
        $d['level']    ?? 'Beginner',
        $d['source']   ?? '',
        date('Y-m-d'),
        $d['password'] ?? '1234',
    ]);
    json_out(['success' => true], 201);
}

function registrations_update_status(int $id): void {
    require_admin();
    $d   = get_json();
    $pdo = db();
    $pdo->prepare("UPDATE registrations SET status = ? WHERE id = ?")->execute([$d['status'] ?? 'Pending', $id]);
    json_out(['success' => true]);
}
