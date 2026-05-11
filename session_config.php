<?php
// OCRS/session_config.php - (Robust, Custom Handler Version)

// --- Force Error Reporting for Debugging ---
// This will help diagnose issues like permissions problems.
ini_set('display_errors', 1);
error_reporting(E_ALL);

class FileSessionHandler implements SessionHandlerInterface {
    private $savePath;

    public function open($savePath, $sessionName): bool {
        $this->savePath = rtrim($savePath, '/') . '/';
        if (!is_dir($this->savePath)) {
            if (!mkdir($this->savePath, 0777, true)) {
                // If it fails, it's likely a permission issue.
                trigger_error("Session path '{$this->savePath}' could not be created.", E_USER_ERROR);
                return false;
            }
        }
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        $file = $this->savePath . 'sess_' . $id;
        if (file_exists($file)) {
            return (string) file_get_contents($file);
        }
        return "";
    }

    public function write($id, $data): bool {
        return file_put_contents($this->savePath . 'sess_' . $id, $data) !== false;
    }

    public function destroy($id): bool {
        $file = $this->savePath . 'sess_' . $id;
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    public function gc($maxlifetime): int|false {
        $count = 0;
        foreach (glob($this->savePath . 'sess_*') as $file) {
            if (filemtime($file) + $maxlifetime < time()) {
                unlink($file);
                $count++;
            }
        }
        return $count;
    }
}

// 1. Define the private session directory INSIDE your project.
$session_save_path = __DIR__ . '/sessions';

// 2. Instantiate our custom handler.
$handler = new FileSessionHandler();

// 3. Set our custom handler as the new way to manage sessions.
// This command effectively overrides php.ini's session settings.
session_set_save_handler($handler, true);

// 4. Set the session lifetime to a full day (86400 seconds).
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);

// 5. Apply standard security settings for session cookies.
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.cookie_samesite', 'Lax');

// 6. Start the session only if it has not already been started.
if (session_status() === PHP_SESSION_NONE) {
    // We pass our custom path to the session_start function via an option
    session_start([
        'save_path' => $session_save_path,
    ]);
}
?>